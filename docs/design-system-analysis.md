# Design System Analysis

Comprehensive audit of the ADP frontend design system across all three packages:
`packages/sdk`, `packages/panel`, `packages/toolbar`.

## Architecture Overview

The design system uses a **three-layer token architecture** defined in
`sdk/src/Component/Theme/tokens.ts`:

| Layer | Export | Purpose |
|-------|--------|---------|
| 1. Primitives | `primitives` | Raw values (hex colors, font names, spacing unit, radius). Never used directly in component `sx` or styles. |
| 2. Semantic | `semanticTokens` / `darkSemanticTokens` | Meaning-mapped palette, typography, shadows. Fed into `createTheme()`. |
| 3. Component | `componentTokens` | Layout-specific dimensions (topBar height, sidebar width, gaps). |

Theme factory: `createAdpTheme(mode)` in `sdk/src/Component/Theme/DefaultTheme.tsx`.

**Convention**: Components must reference `theme.palette.*`, `theme.spacing()`, and `theme.typography.*` — never `primitives.*` hex values directly.

---

## Strengths (What Works Well)

### 1. Consistent Styling Approach
- **100% CSS-in-JS** — no CSS modules, no SCSS, no component-scoped CSS files.
- Two patterns used consistently: `styled()` for static/theme-dependent styles, `sx` prop for dynamic/one-off styles.
- **68+ files** use `styled()`, **100+ files** use `sx` prop.

### 2. Well-Structured Token System
- Three-layer separation prevents direct primitive usage in components.
- Dark mode has proper semantic overrides — not just palette inversion.
- Shadows defined at three levels (sm, md, lg) and mapped to MUI's 25-slot shadow array.

### 3. Typography System
- Two font families: Inter (UI) and JetBrains Mono (code/data).
- Five defined variants: h4 (18px), body1 (14px), body2 (13px), caption (11px), overline (12px).
- Consistent use of `fontFamily: primitives.fontFamilyMono` for monospace data across 30+ components.

### 4. Component Library Coverage
- **50+ reusable components** in SDK: layout (TopBar, Sidebar, CommandPalette), data display (KeyValueTable, Grid, StackTrace, SqlHighlight), UI (EmptyState, StatusCard, InfoBox, SectionTitle).
- Panel modules reuse SDK components consistently.

### 5. Responsive Design
- **42 files** use `useMediaQuery` / `theme.breakpoints`.
- Primary breakpoint: `down('md')` for mobile detection.
- Responsive hiding via `sx={{display: {xs: 'none', md: 'flex'}}}`.

### 6. Accessibility
- **21 files** with `aria-label` attributes.
- TopBar has 8+ aria-labels for all interactive elements.
- Native semantic HTML elements used (buttons, links, inputs).

### 7. Theme Override Strategy
- 8 MUI component overrides in theme: MuiButton (no uppercase), MuiPaper (outlined variant shadow), MuiMenu/MuiPopover/MuiAutocomplete (consistent border), MuiCssBaseline (font face), MuiLink/MuiButtonBase (RouterLink integration).

---

## Issues Found

### CRITICAL: Hardcoded Colors (Dark Mode Broken)

#### Issue 1: Collector Icon Colors — 17 hardcoded pairs
**File**: `panel/src/Module/Debug/Pages/IndexPage.tsx:27-46`

```typescript
const iconColors: Record<string, {bg: string; fg: string}> = {
    [CollectorsMap.RequestCollector]: {bg: '#EFF6FF', fg: '#2563EB'},
    [CollectorsMap.LogCollector]: {bg: '#FEF3C7', fg: '#D97706'},
    [CollectorsMap.EventCollector]: {bg: '#F3E8FF', fg: '#9333EA'},
    // ... 14 more hardcoded color pairs
};
```

**Impact**: These colors are **not theme-aware**. In dark mode, light backgrounds (#EFF6FF, #FEF3C7, etc.) will clash with the dark paper background (#1E293B). Cards will appear as bright white rectangles.

**Fix**: Move to `tokens.ts` as collector-specific semantic tokens with light/dark variants, or derive from `theme.palette.*` (e.g., `primary.light` for RequestCollector bg, `primary.main` for fg).

#### Issue 2: Performance Metric Colors — 5 hardcoded hex values
**File**: `panel/src/Module/Debug/Pages/IndexPage.tsx:173-184`

```typescript
{label: 'Request', ..., color: '#42A5F5'},
{label: 'Preload', ..., color: '#AB47BC'},
{label: 'Emit', ..., color: '#66BB6A'},
{label: 'Peak Mem', ..., color: '#FFA726'},
{label: 'Mem Usage', ..., color: '#26C6DA'},
```

**Impact**: Chart colors don't adapt to dark mode. Some may have poor contrast on dark backgrounds.

**Fix**: Define a `chartColors` token array in `tokens.ts` with light/dark variants.

#### Issue 3: Timeline Bar Colors — 10 hardcoded hex values
**File**: `panel/src/Module/Debug/Component/Panel/TimelinePanel.tsx:23-34`

```typescript
const barColors = ['#42A5F5', '#AB47BC', '#66BB6A', '#FFA726', '#26C6DA',
                   '#EC407A', '#8D6E63', '#78909C', '#FFEE58', '#7C3AED'];
```

**Impact**: Same as Issue 2. Yellow (#FFEE58) will be invisible on light backgrounds.

**Fix**: Consolidate with Issue 2 into shared `chartColors` tokens.

#### Issue 4: Exception Preview Highlight
**File**: `panel/src/Module/Debug/Component/Exception/ExceptionPreview.tsx:77`

```typescript
highlightColor={'#ffcccc'}
```

**Impact**: Red highlight on white works for light mode only. In dark mode needs darker red variant.

#### Issue 5: Database Panel NULL Color
**File**: `panel/src/Module/Debug/Component/Panel/DatabasePanel.tsx:309`

```typescript
<em style={{color: '#999'}}>NULL</em>
```

**Impact**: `#999` should be `theme.palette.text.disabled` for dark mode support.

---

### HIGH: primitives.* Used Directly in Components

The convention states components must never use `primitives.*` directly. However, `primitives.fontFamilyMono` is used in **30+ files** across both SDK and panel packages.

**Files** (partial list):
- `sdk/src/Component/KeyValueTable.tsx:22`
- `sdk/src/Component/Layout/EntrySelector.tsx:111`
- `sdk/src/Component/Layout/CommandPalette.tsx:57,64`
- `sdk/src/Component/Layout/RequestPill.tsx:61`
- `panel/src/Module/Debug/Component/DebugEntryList.tsx:44,52,60,71`
- `panel/src/Module/Debug/Component/Panel/QueuePanel.tsx:94,141,149` (6 more)
- `panel/src/Module/Debug/Component/Panel/TimelinePanel.tsx:49,94`
- `panel/src/Module/Debug/Pages/IndexPage.tsx:68,105,134`

**Root cause**: `theme.typography.fontFamily` is defined for the base font (Inter), but there is no `theme.typography.fontFamilyMono` equivalent. Components fall back to `primitives.fontFamilyMono`.

**Fix**: Add `fontFamilyMono` to the theme's custom properties (via module augmentation) so components can use `theme.typography.fontFamilyMono` instead of importing primitives.

---

### MEDIUM: Hardcoded Font Sizes Outside Typography Scale

The typography scale defines 5 sizes: 18px (h4), 14px (body1), 13px (body2), 12px (overline), 11px (caption). However, many components use sizes **outside this scale** or repeat scale values as raw strings instead of referencing the theme:

| Size | Count | Where |
|------|------:|-------|
| `'10px'` | 12+ | NavBadge, AppNavSidebar, EntrySelector, DebugEntryList, CommandPalette |
| `'9px'` | 2 | DebugEntryList:259,329 |
| `'22px'` | 1 | CodeCoveragePanel:47 |
| `'16px'` | 1 | CommandPalette:56 |
| `'14px'` | 5+ | IndexPage:68,134, Application/IndexPage:180 |
| `'13px'` | 8+ | KeyValueTable:15, EntrySelector:112,121, DebugEntryList:53,194 |
| `'12px'` | 10+ | KeyValueTable:20,22, EntrySelector:127,181, SearchFilter:193 |
| `'11px'` | 8+ | IndexPage:70,99,224,283, DebugEntryList:45, EntrySelector:117,130 |

**Issues**:
- `10px` and `9px` are below the smallest defined variant (caption: 11px) — no typography token exists.
- `14px`, `13px`, `12px`, `11px` match existing variants but are hardcoded instead of referencing `theme.typography.body1.fontSize`, etc.

**Fix**:
1. Add a `micro` typography variant (10px) for badges and metadata.
2. Replace hardcoded sizes matching existing variants with `theme.typography.*.fontSize` references.

---

### MEDIUM: Inconsistent Container Patterns (Box vs Paper)

Panel components use both `Box` and `Paper` for card-like containers without a clear rule:

- **SummaryBar** (IndexPage): `styled(Box)` with border + bg + hover — acts like a Paper
- **MetricCard** (IndexPage): `styled(Box)` with border + bg — acts like a Paper
- **QueuePanel**: All `Box` containers, no Paper usage
- **ConnectionCard** (LLM module): Uses `Paper variant="outlined"` correctly
- **UnifiedSidebar**: Uses `styled(Paper)` correctly

**Fix**: Establish and document a pattern — use `Paper variant="outlined"` for elevated card containers, `Box` for pure layout wrappers.

---

### MEDIUM: `rgba()` Overlays Not Theme-Aware

**Files**:
- `sdk/src/Component/Layout/CommandPalette.tsx:22` — `backgroundColor: 'rgba(0,0,0,0.4)'`
- `sdk/src/Component/Layout/CommandPalette.tsx:35` — `boxShadow: '0 25px 50px -12px rgba(0,0,0,0.25)'`
- `panel/src/Module/Debug/Component/Panel/TimelinePanel.tsx:87` — `boxShadow: '0 0 0 2px rgba(255,255,255,0.2)'`

**Impact**: Fixed overlay opacities may need tuning for dark mode (dark overlay on dark bg is less visible).

**Fix**: Use `theme.palette.action.disabledBackground` or define overlay tokens.

---

### LOW: Inline `style` Attribute Usage

A few components use the `style` attribute instead of `sx`:

- `toolbar/src/.../DebugToolbar.tsx:41` — `style={{height: '100%', width: '100%'}}`
- `toolbar/src/.../DebugToolbar.tsx:276` — `style={{height: position, overflow: 'hidden'}}`
- `sdk/src/Component/FileLink.tsx:58` — `style={{color: 'inherit', textDecoration: 'inherit'}}`
- `sdk/src/Component/StackTrace.tsx:143` — `style={{fontSize: ${fontSize}pt}}`
- `sdk/src/Component/Layout/EntrySelector.tsx:480` — `<span style={{fontSize: '10px', opacity: 0.8}}>`

**Impact**: Minor. Inline styles bypass the theme and can't respond to dark mode. Most of these are for layout-only properties (height, width) which is acceptable.

**Fix**: Migrate color/opacity values to `sx` prop; layout-only inline styles are acceptable.

---

### LOW: SVG Icon Colors (DuckIcon, YiiIcon)

**Files**: `sdk/src/Component/SvgIcon/DuckIcon.tsx`, `sdk/src/Component/SvgIcon/YiiIcon.tsx`

These contain brand-specific hardcoded hex values (duck body: `#FCD34D`, beak: `#FB923C`, Yii logo colors). These are **static illustrations** and do not need to be theme-aware.

**Impact**: None — acceptable for brand icons.

---

### LOW: Duplicate DuckIcon Component

Two identical `DuckIcon` implementations exist:
- `sdk/src/Component/DuckIcon.tsx`
- `sdk/src/Component/SvgIcon/DuckIcon.tsx`

**Fix**: Remove the duplicate and consolidate imports.

---

## Resolved Issues

The following issues were identified and fixed in this refactoring:

1. **`theme.adp.fontFamilyMono`** — Added via MUI module augmentation. Eliminates all `primitives.fontFamilyMono` imports from 45+ component files. Also exported `monoFontFamily` constant for safe use in `sx` props.
2. **Chart color tokens** — `semanticTokens.chartColors` (10 colors) with `darkSemanticTokens.chartColors` variants. Used by TimelinePanel and performance metrics.
3. **Collector color tokens** — `semanticTokens.collectorColors` (17 collectors + default) with dark mode variants. Used by IndexPage debug overview cards.
4. **`micro` typography variant** — 10px/600 weight, registered as MUI typography variant via module augmentation.
5. **Highlight color** — `theme.adp.highlightColor` with dark mode variant (`#ffcccc` light, `#5C2020` dark). CodeHighlight uses it as default.
6. **Duplicate DuckIcon** — Consolidated to `SvgIcon/DuckIcon.tsx` (with monochrome support), old path re-exports.
7. **DatabasePanel NULL color** — Replaced `#999` with `opacity: 0.5` (theme-agnostic).
8. **ExceptionPreview highlight** — Removed hardcoded `#ffcccc`, now uses CodeHighlight's theme-aware default.

---

## Remaining Issues

### 1. No Documented Component Usage Guidelines
Which container to use (Box vs Paper vs Card), when to use `styled()` vs `sx`, how to handle responsive patterns — none of this is formally documented beyond the CLAUDE.md convention note.

### 2. Limited MUI Component Overrides
Only 8 components are overridden in the theme. Common components like TextField, Dialog, Chip, and Tooltip rely entirely on `sx` prop overrides per instance, leading to duplication.

### 3. Hardcoded Font Sizes
~50 instances of hardcoded `fontSize` values that match existing typography variants (`14px`=body1, `13px`=body2, `12px`=overline, `11px`=caption, `10px`=micro) but use raw strings instead of theme references. These still work correctly in both themes since font sizes don't change between light/dark mode.

### 4. Box vs Paper Inconsistency
Card-like containers use both `Box` (with border/bg) and `Paper variant="outlined"` without a clear rule. Not a functional issue — just inconsistent.

---

## Statistics

| Metric | Count |
|--------|------:|
| Files using `styled()` | 68 |
| Files using `sx` prop | 100+ |
| Files using `useTheme` / `useMediaQuery` | 42 |
| Files with `aria-label` | 21 |
| Reusable SDK components | 50+ |
| MUI component overrides in theme | 8 |
| Hardcoded hex colors in components (non-SVG) | ~0 (was ~50) |
| Direct `primitives.*` usage in components | 0 (was 30+ files) |
| CSS files (all legacy/empty) | 5 |
| CSS modules | 0 |
