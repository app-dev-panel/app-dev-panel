# Plan: Synchronization and Bug Fixes for Collectors Across Adapters

## Overview

This document describes every issue found per-collector and the concrete steps to fix it.
Issues fall into three categories:

- **ID mismatch** — frontend `collectors.ts` references a wrong FQCN; the panel never renders
- **Data format mismatch** — backend collector outputs a different schema than the panel expects
- **Missing panel** — no dedicated frontend component; data falls back to raw JSON dump

---

## Part 1: Frontend Namespace Fixes (`collectors.ts`)

All collectors use `CollectorTrait::getId()` which returns `static::class` (the PHP FQCN).
The frontend `collectors.ts` must match these FQCNs exactly.

### 1.1 Yiisoft Adapter — Broken Mappings (6 entries)

| Current value in `collectors.ts` | Actual FQCN (`getId()`) | Status |
|---|---|---|
| `Yiisoft\Db\Debug\DatabaseCollector` | `AppDevPanel\Adapter\Yiisoft\Collector\Db\DatabaseCollector` | **BROKEN** |
| `Yiisoft\Mailer\Debug\MailerCollector` | `AppDevPanel\Adapter\Yiisoft\Collector\Mailer\MailerCollector` | **BROKEN** |
| `Yiisoft\Queue\Debug\QueueCollector` | `AppDevPanel\Adapter\Yiisoft\Collector\Queue\QueueCollector` | **BROKEN** |
| `Yiisoft\Validator\Debug\ValidatorCollector` | `AppDevPanel\Adapter\Yiisoft\Collector\Validator\ValidatorCollector` | **BROKEN** |
| `Yiisoft\Yii\View\Renderer\Debug\WebViewCollector` | `AppDevPanel\Adapter\Yiisoft\Collector\View\WebViewCollector` | **BROKEN** |
| `Yiisoft\Assets\Debug\AssetCollector` | No equivalent in new adapter | **DEAD CODE** |

### 1.2 Yiisoft MiddlewareCollector — Wrong Sub-namespace

| Current | Actual |
|---|---|
| `AppDevPanel\Adapter\Yiisoft\Collector\Web\MiddlewareCollector` | `AppDevPanel\Adapter\Yiisoft\Collector\Middleware\MiddlewareCollector` |

**Action:** `Web` → `Middleware` in the namespace.

### 1.3 Fix Plan

**File:** `libs/frontend/packages/sdk/src/Helper/collectors.ts`

```typescript
// Replace old Yiisoft namespace entries with correct AppDevPanel namespaces:
DatabaseCollector = 'AppDevPanel\\Adapter\\Yiisoft\\Collector\\Db\\DatabaseCollector',
MailerCollector = 'AppDevPanel\\Adapter\\Yiisoft\\Collector\\Mailer\\MailerCollector',
QueueCollector = 'AppDevPanel\\Adapter\\Yiisoft\\Collector\\Queue\\QueueCollector',
ValidatorCollector = 'AppDevPanel\\Adapter\\Yiisoft\\Collector\\Validator\\ValidatorCollector',
WebViewCollector = 'AppDevPanel\\Adapter\\Yiisoft\\Collector\\View\\WebViewCollector',
MiddlewareCollector = 'AppDevPanel\\Adapter\\Yiisoft\\Collector\\Middleware\\MiddlewareCollector',

// Remove dead entry:
// AssetCollector = 'Yiisoft\\Assets\\Debug\\AssetCollector',  — DELETE
```

**Also update:** `collectorMeta.ts` — replace all old keys with new ones.

**Also update:** `Layout.tsx` — `pages` mapping uses `CollectorsMap.*`, so it will auto-fix once the enum values change.

---

## Part 2: Per-Collector Sync Plan

### 2.1 Database/SQL Queries

Three adapters produce DB query data in **three different formats**:

#### Yiisoft `DatabaseCollector`

```php
// getCollected():
[
    'queries' => [
        ['sql', 'rawSql', 'params', 'line', 'status', 'actions' => [['action', 'time'], ...], 'rowsNumber'],
    ],
    'transactions' => [...]
]
```

#### Symfony `DoctrineCollector`

```php
// getCollected():
[
    'queries' => [
        ['sql', 'params', 'types', 'executionTime', 'backtrace'],
    ],
    'totalTime' => float,
    'queryCount' => int,
]
```

#### Yii2 `DbCollector`

```php
// getCollected():
[
    'queries' => [
        ['sql', 'params', 'rowCount', 'time', 'type', 'backtrace'],
    ],
    'queryCount' => int,
    'connectionCount' => int,
    'totalTime' => float,
]
```

#### Frontend `DatabasePanel` expects (from `Layout.tsx:14-22`)

```typescript
type Query = {
    sql: string;
    rawSql: string;        // Missing in Doctrine and Yii2
    line: string;           // Missing in Doctrine (has backtrace array), format differs in Yii2
    params: Record<string, number | string>;
    status: 'success';      // Missing in Doctrine and Yii2
    actions: QueryAction[]; // Missing in Doctrine and Yii2
    rowsNumber: number;     // Missing in Doctrine, named 'rowCount' in Yii2
};
```

#### Issues

| Problem | Doctrine | Yii2 |
|---------|:--------:|:----:|
| No `rawSql` field | ❌ | ❌ |
| No `actions[]` array (panel uses it for timing) | ❌ uses `executionTime` | ❌ uses `time` |
| No `status` field | ❌ | ❌ |
| No `rowsNumber` (Yii2 has `rowCount`) | ❌ | ❌ name differs |
| No `line` string (Doctrine has `backtrace` array) | ❌ | ✅ |
| No `transactions` key | ❌ | ❌ |

#### Fix Options

**Option A — Normalize in backend (recommended):**

Adapt `DoctrineCollector::getCollected()` and `Yii2\DbCollector::getCollected()` to emit the same schema as `Yiisoft\DatabaseCollector`:

```php
// Both should transform to:
[
    'queries' => [
        [
            'sql' => $sql,
            'rawSql' => $sql,                         // same as sql when no prepared/raw distinction
            'params' => $params,
            'line' => $this->formatBacktrace(...),     // 'file:line' string
            'status' => 'success',
            'actions' => [
                ['action' => 'query.start', 'time' => $startTime],
                ['action' => 'query.end', 'time' => $endTime],
            ],
            'rowsNumber' => $rowCount,
        ],
    ],
    'transactions' => [],
]
```

Changes needed:
1. **`DoctrineCollector`** — store `$startTime` before query, compute `$endTime = $startTime + $executionTime`, format backtrace array → `file:line` string, add `rawSql` = `sql`, add `status`, add `transactions` key
2. **`Yii2\DbCollector`** — convert `time` float to `actions[]` array, rename `rowCount` → `rowsNumber`, add `rawSql` = `sql`, add `status`, add `transactions` key

**Option B — Make DatabasePanel flexible:**

Teach `DatabasePanel` to detect the format and handle all three schemas. More fragile but doesn't require backend changes.

**Recommendation: Option A.** Standardize the output schema across all adapters. The panel stays simple.

#### Also fix Layout.tsx mapping for Doctrine

Add `DoctrineCollector` → `DatabasePanel`:

```typescript
[CollectorsMap.DoctrineCollector]: (data: any) => <DatabasePanel data={data} />,
```

---

### 2.2 Mailer

Three adapters, three different formats:

#### Yiisoft `MailerCollector` (reference format)

```php
['from' => array, 'to' => array, 'subject', 'textBody', 'htmlBody', 'replyTo', 'cc', 'bcc', 'charset', 'date', 'raw']
```

#### Symfony `MailerCollector`

```php
['from' => string, 'to' => array, 'subject', 'transport']
// Missing: textBody, htmlBody, replyTo, cc, bcc, charset, date, raw
```

#### Yii2 `MailerCollector`

```php
['from' => array, 'to' => array, 'cc', 'bcc', 'subject', 'isSuccessful']
// Missing: textBody, htmlBody, replyTo, charset, date, raw
```

#### Frontend `MailerPanel` expects

```typescript
{from: Record<string,string>, to: Record<string,string>, subject, date, textBody, htmlBody,
 raw, charset, replyTo: Record<string,string>, cc: Record<string,string>, bcc: Record<string,string>}
```

The panel uses `serializeSender()` on `from`/`to` which expects `Record<string, string>` (email→name mapping).
It renders `htmlBody` in a dialog and `raw` in a raw preview.

#### Issues

| Problem | Symfony | Yii2 |
|---------|:-------:|:----:|
| `from` is string, not `Record<string,string>` | ❌ | ✅ (array) |
| No `cc`, `bcc` | ❌ | ✅ |
| No `replyTo` | ❌ | ❌ |
| No `textBody`, `htmlBody` | ❌ | ❌ |
| No `raw` (raw message source) | ❌ | ❌ |
| No `date`, `charset` | ❌ | ❌ |
| Not mapped in Layout.tsx | ❌ | ✅ (mapped) |

#### Fix Plan

1. **Symfony `MailerCollector`** — Enrich `logMessage()` to capture full email:
   - Extract `from` as `Record<string, string>` from `Email::getFrom()` Address objects
   - Extract `cc`, `bcc`, `replyTo` from corresponding methods
   - Extract `textBody` from `Email::getTextBody()`
   - Extract `htmlBody` from `Email::getHtmlBody()`
   - Extract `raw` via `MessageConverter::toByteStream()` or `toString()`
   - Extract `charset` from headers
   - Extract `date` from `Date` header
   - Remove `transport` field (not used by panel) or keep as extra field

2. **Yii2 `MailerCollector`** — Enrich `logMessage()`:
   - Extract `htmlBody` from `$message->getSwiftMessage()->getBody()` or equivalent
   - Extract `textBody` similarly
   - Extract `replyTo` from message
   - Add `raw` via `$message->toString()`
   - Add `charset` from message
   - Add `date` from message or use `date('r')`
   - Normalize `from` to `Record<string, string>` format (email → name)

3. **Layout.tsx** — Add Symfony MailerCollector mapping:
   ```typescript
   [CollectorsMap.SymfonyMailerCollector]: (data: any) => <MailerPanel data={data} />,
   ```
   (Requires adding `SymfonyMailerCollector` to `collectors.ts`)

4. **MailerPanel** — Add defensive rendering:
   - Handle missing fields gracefully (e.g., `entry.htmlBody && ...` — already done for buttons)
   - Handle `from` being a string (Symfony current format) until backend is fixed

---

### 2.3 HttpStreamCollector (Kernel)

**Status:** All 3 adapters register it, no dedicated panel.

#### Issue

Data format: `{[operation]: [{uri, args}, ...]}` — similar to `FilesystemStreamCollector` but uses `uri` instead of `path`.

`HttpClientPanel` already lazy-loads HttpStream data as a sub-view, but the collector has no standalone panel in `Layout.tsx`.

#### Fix Plan

1. **Create `HttpStreamPanel.tsx`** — clone `FilesystemPanel.tsx`, replace `path` → `uri`, remove file-inspector link, adjust operation type names
2. **Register in `Layout.tsx`:**
   ```typescript
   [CollectorsMap.HttpStreamCollector]: (data: any) => <HttpStreamPanel data={data} />,
   ```

---

### 2.4 Twig (Symfony only)

**Status:** Collector exists, sidebar meta exists, no panel, not mapped in Layout.tsx.

#### Data format

```php
['renders' => [['template', 'renderTime'], ...], 'totalTime' => float, 'renderCount' => int]
```

#### Fix Plan

1. **Create `TwigPanel.tsx`** — list of template renders with timing:
   - Show each template name + render time
   - Summary header: `N renders · Xms total`
   - Filter by template name
2. **Register in `Layout.tsx`:**
   ```typescript
   [CollectorsMap.TwigCollector]: (data: any) => <TwigPanel data={data} />,
   ```

---

### 2.5 Security (Symfony only)

**Status:** Collector exists, sidebar meta exists, no panel, not mapped in Layout.tsx.

#### Data format

```php
[
    'username' => ?string,
    'roles' => [],
    'firewallName' => ?string,
    'authenticated' => bool,
    'accessDecisions' => [['attribute', 'subject', 'result', 'voters'], ...]
]
```

#### Fix Plan

1. **Create `SecurityPanel.tsx`:**
   - Header section: user info (username, roles, firewall, auth status)
   - Table: access decisions (attribute, subject, result, voters)
2. **Register in `Layout.tsx`:**
   ```typescript
   [CollectorsMap.SecurityCollector]: (data: any) => <SecurityPanel data={data} />,
   ```

---

### 2.6 Messenger (Symfony only)

**Status:** Collector exists, sidebar meta exists, no panel, not mapped in Layout.tsx.

#### Data format

```php
[
    'messages' => [['messageClass', 'bus', 'transport', 'dispatched', 'handled', 'failed', 'duration'], ...],
    'messageCount' => int,
    'failedCount' => int,
]
```

#### Fix Plan

1. **Create `MessengerPanel.tsx`:**
   - Summary: total messages, failed count
   - List: message class, bus, transport, status (dispatched/handled/failed), duration
   - Color-code failed messages
2. **Register in `Layout.tsx`:**
   ```typescript
   [CollectorsMap.MessengerCollector]: (data: any) => <MessengerPanel data={data} />,
   ```

---

### 2.7 Queue (Yiisoft only)

**Status:** Collector exists, sidebar meta exists (old namespace — broken), no panel.

#### Fix Plan

1. **Fix namespace** in `collectors.ts` (Part 1)
2. **Create `QueuePanel.tsx`:**
   - Inspect `QueueCollector::getCollected()` to determine data shape
   - Render message list with push/process status, timing
3. **Register in `Layout.tsx`:**
   ```typescript
   [CollectorsMap.QueueCollector]: (data: any) => <QueuePanel data={data} />,
   ```

---

### 2.8 Router (Yiisoft only)

**Status:** Collector exists, NO sidebar meta, no panel, not in `collectors.ts`.

#### Fix Plan

1. **Add to `collectors.ts`:**
   ```typescript
   RouterCollector = 'AppDevPanel\\Adapter\\Yiisoft\\Collector\\Router\\RouterCollector',
   ```
2. **Add to `collectorMeta.ts`:**
   ```typescript
   [CollectorsMap.RouterCollector]: {label: 'Router', icon: 'alt_route', weight: 13},
   ```
3. **Create `RouterPanel.tsx`:**
   - Inspect `RouterCollector::getCollected()` to determine data shape
   - Show matched route, pattern, method, handler
4. **Register in `Layout.tsx`**

---

### 2.9 Validator (Yiisoft only)

**Status:** Collector exists, sidebar meta exists (old namespace — broken), no panel.

#### Fix Plan

1. **Fix namespace** in `collectors.ts` (Part 1)
2. **Create `ValidatorPanel.tsx`:**
   - Inspect `ValidatorCollector::getCollected()` to determine data shape
   - Show validation rules, results, errors per field
3. **Register in `Layout.tsx`**

---

### 2.10 WebView (Yiisoft only)

**Status:** Collector exists, sidebar meta exists (old namespace — broken), no panel.

#### Fix Plan

1. **Fix namespace** in `collectors.ts` (Part 1)
2. **Create `WebViewPanel.tsx`** — similar structure to TwigPanel:
   - Template name + render timing
   - Summary of total renders
3. **Register in `Layout.tsx`**

---

### 2.11 AssetBundle (Yii2 only)

**Status:** Collector exists, sidebar meta exists, no panel.

#### Data format

```php
['bundles' => [...], 'bundleCount' => int]
```

#### Fix Plan

1. **Create `AssetBundlePanel.tsx`:**
   - Show bundle class, source/base paths, CSS files, JS files, dependencies
   - Tree or list view
2. **Register in `Layout.tsx`:**
   ```typescript
   [CollectorsMap.Yii2AssetBundleCollector]: (data: any) => <AssetBundlePanel data={data} />,
   ```

---

### 2.12 Cache (Symfony only — panel exists)

**Status:** Panel and mapping exist. Working correctly for Symfony.

#### Fix Plan

No action needed. Consider adding cache collectors to Yiisoft and Yii2 adapters in the future.

---

### 2.13 Middleware (Yiisoft only — panel exists but namespace broken)

**Status:** Panel exists, mapping exists but with WRONG namespace (`Web` instead of `Middleware`).

#### Fix Plan

Fix namespace in `collectors.ts` (Part 1.2). Panel and data format are already compatible.

---

## Part 3: Deprecated/Legacy Cleanup

### 3.1 Symfony `SymfonyRequestCollector` and `SymfonyExceptionCollector`

**Status:** Unused, superseded by Kernel's `RequestCollector` and `ExceptionCollector`. Not registered in Extension.

#### Fix Plan

1. Delete `SymfonyRequestCollector.php` and `SymfonyExceptionCollector.php`
2. Delete their tests
3. Or mark them `@deprecated` with a note

---

### 3.2 Old `AssetCollector` in `collectors.ts`

`Yiisoft\Assets\Debug\AssetCollector` — no equivalent in new adapter.

#### Fix Plan

Remove from `collectors.ts` and `collectorMeta.ts`.

---

## Part 4: Playground Enrichment

### 4.1 Symfony Playground — Enable Framework-Specific Collectors

The symfony-basic-app has all framework-specific collectors disabled because dependencies are missing.

#### Fix Plan (optional, for full testing)

1. Add packages to `playground/symfony-basic-app/composer.json`:
   - `doctrine/orm` + `doctrine/doctrine-bundle`
   - `twig/twig` + `symfony/twig-bundle`
   - `symfony/security-bundle`
   - `symfony/mailer`
   - `symfony/messenger`
2. Enable collectors in `config/packages/app_dev_panel.yaml`
3. Add test fixtures for: Doctrine queries, Twig renders, security checks, sending mail, dispatching messages

### 4.2 Yiisoft Playground — Add Missing Test Fixtures

Currently missing test fixtures for: Queue, Router, Validator, WebView.

#### Fix Plan

Add test fixture actions that exercise these collectors so they can be verified in the playground.

---

## Part 5: Execution Priority

### Phase 1: Critical Fixes (broken functionality)

| # | Task | Impact | Effort |
|---|------|--------|--------|
| 1 | Fix `collectors.ts` namespaces (Part 1) | **High** — 7 collectors can't render | S |
| 2 | Fix `collectorMeta.ts` keys (Part 1) | **High** — sidebar items broken for 7 collectors | S |
| 3 | Fix MiddlewareCollector namespace `Web` → `Middleware` | **High** — MiddlewarePanel never renders | S |
| 4 | Add `DoctrineCollector` → `DatabasePanel` mapping in Layout.tsx | **Medium** — Doctrine queries render as JSON | S |
| 5 | Add Symfony `MailerCollector` to Layout.tsx + collectors.ts | **Medium** — mailer data renders as JSON | S |

### Phase 2: Data Format Normalization (correctness)

| # | Task | Impact | Effort |
|---|------|--------|--------|
| 6 | Normalize `DoctrineCollector` output to DatabasePanel schema | **High** — queries render but panel will break | M |
| 7 | Normalize `Yii2\DbCollector` output to DatabasePanel schema | **High** — same as above | M |
| 8 | Enrich Symfony `MailerCollector` to full MailerPanel schema | **Medium** — limited mail display | M |
| 9 | Enrich Yii2 `MailerCollector` to full MailerPanel schema | **Medium** — limited mail display | M |

### Phase 3: New Panels (feature gaps)

| # | Task | Impact | Effort |
|---|------|--------|--------|
| 10 | Create `HttpStreamPanel` | **Low** — data visible via HttpClientPanel sub-view | S |
| 11 | Create `TwigPanel` | **Medium** — Symfony template debugging | M |
| 12 | Create `SecurityPanel` | **Medium** — Symfony security debugging | M |
| 13 | Create `MessengerPanel` | **Medium** — Symfony message bus debugging | M |
| 14 | Create `QueuePanel` | **Medium** — Yiisoft queue debugging | M |
| 15 | Create `RouterPanel` + add to collectors.ts/meta | **Low** — router data visible in Inspector | M |
| 16 | Create `ValidatorPanel` | **Low** — validation debugging | M |
| 17 | Create `WebViewPanel` | **Low** — view template debugging | M |
| 18 | Create `AssetBundlePanel` | **Low** — Yii2 asset debugging | M |

### Phase 4: Cleanup & Playground (polish)

| # | Task | Impact | Effort |
|---|------|--------|--------|
| 19 | Delete deprecated Symfony collectors | **None** — cleanup only | S |
| 20 | Remove dead `AssetCollector` entry | **None** — cleanup only | S |
| 21 | Enrich Symfony playground with full dependencies | **Testing** | L |
| 22 | Add missing Yiisoft playground fixtures | **Testing** | M |

**Effort scale:** S = < 1 hour, M = 1–4 hours, L = 4+ hours
