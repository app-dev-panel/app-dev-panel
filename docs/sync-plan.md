# Plan: Synchronization and Bug Fixes for Collectors Across Adapters

## Overview

This document describes every issue found per-collector and the concrete steps to fix it.
Issues fall into three categories:

- **ID mismatch** — frontend `collectors.ts` references a wrong FQCN; the panel never renders
- **Data format mismatch** — backend collector outputs a different schema than the panel expects
- **Missing panel** — no dedicated frontend component; data falls back to raw JSON dump

---

## Part 1: Frontend Namespace Fixes (`collectors.ts`)

### Status: DONE

All collectors use `CollectorTrait::getId()` which returns `static::class` (the PHP FQCN).
The frontend `collectors.ts` now matches these FQCNs exactly.

**Completed in:** `e4dc8fe` Fix collector namespace mappings, normalize data formats, remove deprecated collectors

Changes made:
- Fixed 6 broken Yiisoft namespace entries
- Fixed MiddlewareCollector namespace (`Web` → `Middleware`)
- Removed dead `AssetCollector` entry
- Updated `collectorMeta.ts` keys
- Added `DoctrineCollector` → `DatabasePanel` mapping
- Added `SymfonyMailerCollector` → `MailerPanel` mapping
- Added `RouterCollector` to `collectors.ts` + `collectorMeta.ts`

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

#### Frontend `DatabasePanel` expects

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

#### Fix — Normalize in backend (recommended)

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

#### Fix Plan

1. **Symfony `MailerCollector`** — Enrich `logMessage()` to capture full email:
   - Extract `from` as `Record<string, string>` from `Email::getFrom()` Address objects
   - Extract `cc`, `bcc`, `replyTo` from corresponding methods
   - Extract `textBody`, `htmlBody`, `raw`, `charset`, `date`

2. **Yii2 `MailerCollector`** — Enrich `logMessage()`:
   - Extract `htmlBody`, `textBody`, `replyTo`, `raw`, `charset`, `date`
   - Normalize `from` to `Record<string, string>` format

3. **MailerPanel** — Add defensive rendering for missing fields

---

### 2.3 HttpStreamCollector (Kernel)

**Status:** All 3 adapters register it. Hidden from sidebar (`hiddenCollectors` set in Layout.tsx). Currently sub-viewed via HttpClientPanel.

#### Fix Plan

1. **Create `HttpStreamPanel.tsx`** — clone `FilesystemPanel.tsx`, replace `path` → `uri`, adjust operation type names
2. **Register in `Layout.tsx`** + optionally unhide from sidebar

---

### 2.4–2.10 Framework-Specific Panels

**Status: ALL DONE** — Panels created and wired in Layout.tsx.

**Completed in:** `592ff57` Add new panels + `c2aae70` Wire panels into Layout.tsx

| Panel | Collector | Status |
|-------|-----------|--------|
| `TwigPanel` | Symfony `TwigCollector` | ✅ Created + wired |
| `SecurityPanel` | Symfony `SecurityCollector` | ✅ Created + wired |
| `MessengerPanel` | Symfony `MessengerCollector` | ✅ Created + wired |
| `QueuePanel` | Yiisoft `QueueCollector` | ✅ Created + wired |
| `RouterPanel` | Yiisoft `RouterCollector` | ✅ Created + wired |
| `ValidatorPanel` | Yiisoft `ValidatorCollector` | ✅ Created + wired |
| `WebViewPanel` | Yiisoft `WebViewCollector` | ✅ Created + wired |
| `AssetBundlePanel` | Core `AssetBundleCollector` | ✅ Created + wired |

---

### 2.11 AssetBundle

**Status: DONE** — Moved from Yii2 adapter to Kernel core.

**Completed in:** `c1d5cba` Move AssetBundleCollector from Yii2 adapter to Kernel core

- Core `AssetBundleCollector` accepts normalized arrays (framework-agnostic)
- Yii2 adapter normalizes bundles in the event hook
- Frontend uses `CollectorsMap.AssetBundleCollector` (core FQCN)
- Any future adapter can reuse the same collector

---

### 2.12 Cache (Symfony only)

**Status:** Working correctly. No action needed.

---

### 2.13 Middleware (Yiisoft only)

**Status: DONE** — Namespace fixed in Part 1.

---

## Part 3: Deprecated/Legacy Cleanup

### Status: DONE

**Completed in:** `e4dc8fe`

- Removed dead `AssetCollector` from `collectors.ts` and `collectorMeta.ts`
- Deprecated Symfony collectors (`SymfonyRequestCollector`, `SymfonyExceptionCollector`) — verify if deleted

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

## Part 5: Execution Priority (Updated)

### Phase 1: Critical Fixes — DONE

| # | Task | Status |
|---|------|--------|
| 1 | Fix `collectors.ts` namespaces | ✅ Done |
| 2 | Fix `collectorMeta.ts` keys | ✅ Done |
| 3 | Fix MiddlewareCollector namespace | ✅ Done |
| 4 | Add `DoctrineCollector` → `DatabasePanel` mapping | ✅ Done |
| 5 | Add Symfony `MailerCollector` mapping | ✅ Done |

### Phase 2: Data Format Normalization — TODO

| # | Task | Impact | Effort |
|---|------|--------|--------|
| 6 | Normalize `DoctrineCollector` output to DatabasePanel schema | **High** — queries render but panel may break on missing fields | M |
| 7 | Normalize `Yii2\DbCollector` output to DatabasePanel schema | **High** — same as above | M |
| 8 | Enrich Symfony `MailerCollector` to full MailerPanel schema | **Medium** — limited mail display | M |
| 9 | Enrich Yii2 `MailerCollector` to full MailerPanel schema | **Medium** — limited mail display | M |

### Phase 3: New Panels — DONE

| # | Task | Status |
|---|------|--------|
| 10 | Create `HttpStreamPanel` | ❌ TODO (low priority — hidden, sub-viewed via HttpClientPanel) |
| 11 | Create `TwigPanel` | ✅ Done |
| 12 | Create `SecurityPanel` | ✅ Done |
| 13 | Create `MessengerPanel` | ✅ Done |
| 14 | Create `QueuePanel` | ✅ Done |
| 15 | Create `RouterPanel` | ✅ Done |
| 16 | Create `ValidatorPanel` | ✅ Done |
| 17 | Create `WebViewPanel` | ✅ Done |
| 18 | Create `AssetBundlePanel` + move collector to core | ✅ Done |

### Phase 4: Cleanup & Playground — PARTIAL

| # | Task | Status |
|---|------|--------|
| 19 | Delete deprecated Symfony collectors | ✅ Done |
| 20 | Remove dead `AssetCollector` entry | ✅ Done |
| 21 | Enrich Symfony playground with full dependencies | ❌ TODO |
| 22 | Add missing Yiisoft playground fixtures | ❌ TODO |

### Phase 5: UX Improvements — DONE

| # | Task | Status |
|---|------|--------|
| 23 | Show inactive collectors toggle (Settings menu) | ✅ Done |

---

## Remaining Work Summary

| Priority | Task | Effort |
|----------|------|--------|
| **High** | Normalize `DoctrineCollector` output format | M |
| **High** | Normalize `Yii2\DbCollector` output format | M |
| **Medium** | Enrich Symfony `MailerCollector` data | M |
| **Medium** | Enrich Yii2 `MailerCollector` data | M |
| **Low** | Create `HttpStreamPanel` | S |
| **Low** | Enrich Symfony playground | L |
| **Low** | Add Yiisoft playground fixtures | M |

**Effort scale:** S = < 1 hour, M = 1–4 hours, L = 4+ hours
