# ADP Design Specification

Reference prototype: `docs/design/prototypes/zen-nav-b-floating-sidebar.html`

## Layout Architecture

```
┌───────────────────────────────────────────────────────────┐
│  TopBar (48px, full-width, fixed top)                     │
│  [Logo] [RequestPill ◂ ▸] ─────── [Search] [Theme] [More]│
├───────────────────────────────────────────────────────────┤
│                                                           │
│        ┌──────────┐  ┌────────────────────────────┐       │
│        │          │  │                            │       │
│        │ Sidebar  │  │      ContentPanel          │       │
│        │ (200px)  │  │      (flex, scrollable)    │       │
│        │ floating │  │      floating card         │       │
│        │ card     │  │                            │       │
│        │          │  │                            │       │
│        │          │  │                            │       │
│        └──────────┘  └────────────────────────────┘       │
│                                                           │
│   ←────────── max-width: 1160px, centered ──────────→     │
└───────────────────────────────────────────────────────────┘
```

- **TopBar**: Spans full viewport width. White bg, bottom border.
- **Main area**: `#F3F4F6` background. 16px padding around content.
- **Sidebar + Content**: Wrapped in a centered `max-width: 1160px` flex container with 16px gap.
- **Sidebar**: 200px wide. Floating card (`Paper variant="outlined"`, border-radius 16px, shadow). Always open. `align-self: flex-start` (does not stretch to full height).
- **ContentPanel**: Flex-grow. Floating card (same style). Scrollable.

## Design Tokens

### Primitive Tokens (defined once, not used directly)

| Token | Value | Usage |
|-------|-------|-------|
| `blue500` | `#2563EB` | Primary accent base |
| `blue50` | `#EFF6FF` | Active background tint |
| `gray50` | `#F3F4F6` | App background |
| `gray100` | `#F5F5F5` | Hover state |
| `gray200` | `#E5E5E5` | Borders, dividers |
| `gray300` | `#F0F0F0` | Light borders, badge bg |
| `gray900` | `#1A1A1A` | Primary text |
| `gray600` | `#666666` | Secondary text |
| `gray400` | `#999999` | Muted text |
| `green600` | `#16A34A` | Success / 2xx |
| `amber600` | `#D97706` | Warning / slow |
| `red600` | `#DC2626` | Error / 5xx |
| `red50` | `#FEE2E2` | Error badge bg |

### Semantic Tokens (MUI theme mapping)

```ts
palette: {
    mode: 'light',
    primary: { main: '#2563EB', light: '#EFF6FF' },
    success: { main: '#16A34A' },
    warning: { main: '#D97706' },
    error: { main: '#DC2626', light: '#FEE2E2' },
    background: { default: '#F3F4F6', paper: '#FFFFFF' },
    text: { primary: '#1A1A1A', secondary: '#666666', disabled: '#999999' },
    divider: '#E5E5E5',
}
```

### Typography

```ts
typography: {
    fontFamily: "'Inter', sans-serif",
    h4: { fontSize: '18px', fontWeight: 600 },      // page titles
    body1: { fontSize: '14px', fontWeight: 400 },    // default
    body2: { fontSize: '13px', fontWeight: 400 },    // secondary
    caption: { fontSize: '11px', fontWeight: 600 },  // section labels
    overline: { fontSize: '12px', fontWeight: 600, letterSpacing: '0.6px', textTransform: 'uppercase' },
}
```

Custom font: `JetBrains Mono` for code values — applied via sx or a `CodeText` component.

### Shape

```ts
shape: {
    borderRadius: 8,   // base = 8px (sm). Use *1.5=12 (md), *2=16 (lg) for cards.
}
```

### Shadows

Use theme.shadows[1] for cards (subtle):
```ts
shadows: {
    1: '0 1px 3px rgba(0,0,0,0.06), 0 1px 2px rgba(0,0,0,0.04)',
    2: '0 4px 12px rgba(0,0,0,0.08)',
}
```

## Component Tree

### 1. `AppShell` — Top-level layout

Replaces current `Layout`. Full page structure.

```
<AppShell>
  <TopBar />
  <MainArea>        ← centered flex container
    <CollectorSidebar />
    <ContentPanel>
      <Outlet />    ← React Router renders page here
    </ContentPanel>
  </MainArea>
</AppShell>
```

**Location**: `packages/yii-dev-panel/src/Application/Component/AppShell.tsx`

### 2. `TopBar` — Application header

48px height, full width. Contains:
- Logo (ADP diamond + text)
- RequestPill (current debug entry summary)
- Navigation arrows (prev/next entry)
- SearchTrigger (Ctrl+K hint)
- ThemeToggle (light/dark)
- MoreMenu

**Location**: `packages/yii-dev-panel-sdk/src/Component/Layout/TopBar.tsx`

### 3. `RequestPill` — Debug entry summary chip

Displays: `[METHOD] /path — [STATUS] — [DURATION]`
Clickable → opens entry selector dropdown.

**Location**: `packages/yii-dev-panel-sdk/src/Component/Layout/RequestPill.tsx`

### 4. `CollectorSidebar` — Floating navigation panel

Always visible, 200px wide. Floating `Paper` card. Lists all collectors for the current debug entry.

Each item: icon + label + optional badge (count).
Active item: accent bg + left accent bar.

Items come from `debugEntry.collectors` array (dynamic, API-driven).

**Location**: `packages/yii-dev-panel-sdk/src/Component/Layout/CollectorSidebar.tsx`

#### Sub-components:
- `NavItem` — Single sidebar navigation item (icon, label, badge, active state)
- `NavBadge` — Count badge (default gray, error variant red)

### 5. `ContentPanel` — Main content area

Floating `Paper` card, flex-grow, scrollable. Renders `<Outlet />`.

**Location**: `packages/yii-dev-panel-sdk/src/Component/Layout/ContentPanel.tsx`

### 6. `SectionTitle` — Content section header

Uppercase, small, muted label with bottom divider. Used for "Request Headers", "Query Parameters" etc.

**Location**: `packages/yii-dev-panel-sdk/src/Component/SectionTitle.tsx`

### 7. `KeyValueTable` — Two-column data display

Left column: muted label. Right column: monospace value. Thin row dividers.

**Location**: `packages/yii-dev-panel-sdk/src/Component/KeyValueTable.tsx`

### 8. `SearchTrigger` — Search bar button

Displays search icon + "Search..." + kbd shortcut hint. Opens command palette on click.

**Location**: `packages/yii-dev-panel-sdk/src/Component/Layout/SearchTrigger.tsx`

## Collector → Icon Mapping

| Collector | Icon (Material) | Label |
|-----------|----------------|-------|
| Overview/Cards | `grid_view` | Overview |
| RequestCollector | `http` | Request |
| LogCollector | `description` | Log |
| DatabaseCollector | `storage` | Database |
| EventCollector | `bolt` | Events |
| ExceptionCollector | `warning` | Exception |
| MiddlewareCollector | `filter_list` | Middleware |
| ServiceCollector | `inventory_2` | Service |
| TimelineCollector | `timeline` | Timeline |
| VarDumperCollector | `data_object` | Dump |
| MailerCollector | `mail` | Mailer |
| FilesystemCollector | `folder` | Filesystem |

Map is defined in `packages/yii-dev-panel-sdk/src/Helper/collectorMeta.ts`.

## Reuse from Existing Codebase

| Existing | Reuse / Replace |
|----------|----------------|
| `MenuPanel.tsx` | **Replace** with `CollectorSidebar` (new floating design) |
| `DefaultTheme.tsx` | **Rewrite** with full token system |
| `Layout.tsx` (Application) | **Replace** with `AppShell` |
| `Layout.tsx` (Debug/Pages) | **Refactor** — extract entry selector, keep collector logic |
| `ErrorFallback.tsx` | **Keep** as-is |
| `CodeHighlight.tsx` | **Keep**, use in code display areas |
| `JsonRenderer.tsx` | **Keep**, use for JSON data |
| `InfoBox.tsx` | **Keep**, update styling to match new theme |
| Debug Panel components | **Redesigned** all 11 panels to zen-minimal style (expandable rows, tokens, badges) |
| RTK Query APIs | **Keep** all — no API changes needed |
| Redux store | **Keep** structure — add sidebar state if needed |

## Storybook Setup

Install Storybook 8 in `libs/yii-dev-panel/`:
```bash
npx storybook@latest init --type react --builder vite
```

Stories to create:
1. `TopBar.stories.tsx` — Default, with long URL, error status
2. `RequestPill.stories.tsx` — GET/POST/DELETE variants, status colors
3. `CollectorSidebar.stories.tsx` — Full list, few items, with error badge
4. `NavItem.stories.tsx` — Default, active, with badge, error badge
5. `ContentPanel.stories.tsx` — With sample content
6. `SectionTitle.stories.tsx` — Default
7. `KeyValueTable.stories.tsx` — With sample data
8. `AppShell.stories.tsx` — Full layout composition

## Implementation Order

1. **Theme** — Rewrite `DefaultTheme.tsx` with full token system
2. **Layout primitives** — `TopBar`, `ContentPanel`, `CollectorSidebar`, `NavItem`
3. **AppShell** — Compose layout, wire to router
4. **Storybook** — Set up and write stories for all new components
5. **Integration** — Replace old `Layout` + `MenuPanel` with new components
6. **Collector pages** — Verify all existing panels render correctly in new layout
