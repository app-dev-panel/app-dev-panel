# Frontend Test & Quality Plan

## Current State

- **0 test files** exist across all 3 frontend packages
- **No test runner** configured (no vitest.config.ts)
- **~100+ source files** (TSX/TS) across yii-dev-panel, yii-dev-panel-sdk, yii-dev-toolbar
- **9 Storybook stories** exist for layout components (SDK)
- **Design spec** at `docs/design/SPEC.md` — Variant A "Command Center" approved
- **Design tokens** implemented in `Theme/tokens.ts` and `Theme/DefaultTheme.tsx`
- **Layout components** implemented: TopBar, RequestPill, CollectorSidebar, NavItem, NavBadge, SearchTrigger, ContentPanel

## Found Bugs

1. **`collectorClass.split is not a function`** — `debugEntry.collectors` can contain non-string values; `getCollectorMeta()` crashed. **Fixed** (type guard added).
2. **TopBar hides request info when any of method/path/status/duration is missing** — uses `&&` chain so partial data shows nothing.
3. **`dangerouslySetInnerHTML` in CollectorData** — XSS risk when rendering unknown string data as HTML (line 125 Layout.tsx).
4. **Unused `PropsWithChildren` wrapper** in TopBar — no children are used.
5. **`DebugEntryAutocomplete` component** defined but never rendered in the new Layout.
6. **`postCurlBuildInfo`/`doRequest` handlers** defined but never wired to any UI element.
7. **Missing error handling in SSE `onUpdatesHandler`** — `JSON.parse(event.data)` can throw on malformed data.
8. **`gap: 2` in SidebarRoot** — hardcoded pixel value instead of theme spacing.
9. **`changeEntry` function** not wrapped in `useCallback` — recreated every render, breaks memoization of dependent callbacks.

## Plan

### Phase 1: Test Infrastructure Setup

1. Install vitest + @testing-library/react + jsdom in workspace root
2. Create `vitest.config.ts` for each package (yii-dev-panel-sdk, yii-dev-panel, yii-dev-toolbar)
3. Create test setup file with MUI theme provider, Redux store mock, React Router memory wrapper
4. Add `npm run test` script to root `package.json` (via lerna)
5. Create test utilities: `renderWithProviders()`, mock RTK Query hooks, mock SSE

### Phase 2: SDK Package Tests (`yii-dev-panel-sdk`)

Priority: Pure functions and helpers first (no React rendering), then components.

#### Helpers (unit tests, no DOM needed)
6. `Helper/collectorMeta.ts` — getCollectorMeta, getCollectorLabel, getCollectorIcon, compareCollectorWeight
7. `Helper/collectors.ts` — CollectorsMap enum values
8. `Helper/collectorsTotal.ts` — getCollectedCountByCollector
9. `Helper/buttonColor.ts` — buttonColorHttp
10. `Helper/debugEntry.ts` — isDebugEntryAboutConsole, isDebugEntryAboutWeb
11. `Helper/formatDate.ts` — formatDate, formatMillisecondsAsDuration
12. `Helper/nl2br.tsx` — nl2br conversion
13. `API/createBaseQuery.ts` — base query factory

#### Layout Components (render tests)
14. `Component/Layout/NavBadge.tsx` — renders count, error variant styling
15. `Component/Layout/NavItem.tsx` — renders icon/label/badge, active state, click handler
16. `Component/Layout/SearchTrigger.tsx` — renders search text, keyboard hint, click
17. `Component/Layout/ContentPanel.tsx` — renders children
18. `Component/Layout/RequestPill.tsx` — method/path/status/duration display, status color
19. `Component/Layout/TopBar.tsx` — renders logo, request info, navigation buttons, partial data handling
20. `Component/Layout/CollectorSidebar.tsx` — renders items, active state, overview click, empty state

#### Shared Components
21. `Component/InfoBox.tsx` — renders title, text, severity, icon
22. `Component/ErrorFallback.tsx` — renders error message, reset button
23. `Component/CodeHighlight.tsx` — renders code with syntax highlighting
24. `Component/JsonRenderer.tsx` — renders JSON data
25. `Component/Grid.tsx` — data grid wrapper
26. `Component/DebugChip.tsx` — chip rendering
27. `Component/DebugEntryChip.tsx` — debug entry chip
28. `Component/MenuPanel.tsx` — sidebar menu

#### Theme
29. `Component/Theme/tokens.ts` — token values validation
30. `Component/Theme/DefaultTheme.tsx` — createAdpTheme produces valid MUI theme

### Phase 3: Main App Tests (`yii-dev-panel`)

#### Debug Module
31. `Module/Debug/Pages/Layout.tsx` — main layout orchestration: sidebar building, collector selection, entry navigation, SSE subscription
32. `Module/Debug/Pages/ListPage.tsx` — debug entry list rendering
33. `Module/Debug/Component/Panel/RequestPanel.tsx` — request data display
34. `Module/Debug/Component/Panel/LogPanel.tsx` — log entries display
35. `Module/Debug/Component/Panel/DatabasePanel.tsx` — query display
36. `Module/Debug/Component/Panel/EventPanel.tsx` — event list
37. `Module/Debug/Component/Panel/ExceptionPanel.tsx` — exception rendering
38. `Module/Debug/Component/Panel/MiddlewarePanel.tsx` — middleware chain
39. `Module/Debug/Component/Panel/VarDumperPanel.tsx` — dump rendering
40. `Module/Debug/Component/Panel/TimelinePanel.tsx` — timeline visualization
41. `Module/Debug/Component/Panel/ServicesPanel.tsx` — services list
42. `Module/Debug/Component/Panel/MailerPanel.tsx` — mailer data
43. `Module/Debug/Component/Panel/FilesystemPanel.tsx` — filesystem ops

#### Inspector Module (key pages)
44. `Module/Inspector/Pages/RoutesPage.tsx` — route list rendering
45. `Module/Inspector/Pages/EventsPage.tsx` — event list
46. `Module/Inspector/Pages/CommandsPage.tsx` — command execution

#### Application Shell
47. `Application/Pages/IndexPage.tsx` — landing page
48. `Application/Pages/NotFoundPage.tsx` — 404 page
49. `Application/router.tsx` — route definitions

### Phase 4: Toolbar Tests (`yii-dev-toolbar`)

50. `Module/Toolbar/Pages/Toolbar.tsx` — toolbar rendering
51. `Module/Toolbar/Component/Toolbar/DebugToolbar.tsx` — toolbar items

### Phase 5: Design Compliance Audit

Compare implemented components against `docs/design/SPEC.md`:

52. TopBar: height 48px, full-width, white bg, bottom border, logo + RequestPill + nav arrows + search + theme + more
53. Sidebar: 200px, floating Paper card, border-radius 16px, `align-self: flex-start`
54. ContentPanel: floating Paper card, flex-grow, scrollable
55. Main area: `#F3F4F6` background, centered max-width 1160px, 16px gap
56. NavItem: icon + label + badge, active state with accent bg + left bar
57. Design tokens: all primitives, semantic, and component tokens match spec
58. Typography: Inter font, correct font sizes and weights

### Phase 6: Bug Fixes

59. Fix TopBar partial data display (show what's available instead of hiding everything)
60. Fix XSS vulnerability in CollectorData `dangerouslySetInnerHTML`
61. Fix gap: 2 hardcoded value in SidebarRoot
62. Wrap `changeEntry` in useCallback
63. Fix `JSON.parse` in SSE handler (add try/catch)
64. Wire unused DebugEntryAutocomplete or remove dead code
65. Wire or remove unused `copyCurlHandler`/`repeatRequestHandler`

## Execution Order

1. Phase 1 (infra) — must be first
2. Phase 2 (SDK helpers) — parallel with Phase 6 bug fixes
3. Phase 2 (SDK components) — after helpers
4. Phase 3 (main app) — after SDK tests establish patterns
5. Phase 4 (toolbar) — last, smallest surface area
6. Phase 5 (design audit) — continuous, done alongside component tests

## Test Conventions

- Use Vitest + @testing-library/react + jsdom
- Co-locate tests: `Component.test.tsx` next to `Component.tsx`
- Use `renderWithProviders()` for components needing Redux/Router/Theme
- Mock RTK Query hooks at module level
- No snapshot tests — assert specific DOM elements and behavior
- Data providers via `describe.each` / `it.each` for parametric tests
