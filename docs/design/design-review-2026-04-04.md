# Design Review — 2026-04-04

Status: Active. Based on Yii2 playground screenshots with fixture data.

## Critical Issues

### 1. Home page is blank
- **Page**: `/` (IndexPage)
- **Problem**: Renders completely white when VITE_BACKEND_URL is set and baseUrl is loaded from env.
  The page conditionally renders a URL input field, but when baseUrl is pre-configured, nothing shows.
- **Fix**: Always show the home page content with connection status, quick links to Debug/Inspector,
  and recent entries summary. Show the URL input as a secondary element.

### 2. /debug shows "No debug entry selected" on fresh load
- **Page**: `/debug`
- **Problem**: When navigating to `/debug` with no entry in Redux state, shows empty screen.
  The `debugEntry` query parameter exists but doesn't trigger entry loading from API.
- **Fix**: Auto-select the latest entry when navigating to `/debug` if no entry is selected.
  Read `debugEntry` from URL params and fetch it if not in Redux state.

## Visual Consistency Issues

### 5. Inconsistent loading indicators
- **Where**: Authorization (linear progress bar at top), HTTP Mock (linear progress bar at top),
  Config/Routes/Events/Storage (centered circular spinner)
- **Fix**: Standardize on one pattern. Use linear progress bar at page top for all data-loading
  pages, keeping the content area clear for skeleton or empty state below.

### 6. Debug entries list visual overload
- **Page**: `/debug/list`
- **Problem**: Dense rows with many red X icons for collectors that have no data.
  No visual hierarchy between entries.
- **Fix**: Hide zero-count collector badges. Add alternating row backgrounds.
  Highlight entries with errors/exceptions. Show only non-zero collector counts.

### 7. Inspector Dashboard skeleton looks broken
- **Page**: `/inspector`
- **Problem**: Gray placeholder blocks without shimmer animation look like broken UI.
- **Fix**: Add skeleton shimmer animation. Or show actual section titles with loading state.

## Implementation Plan

### Phase 1: Remaining Critical UX Blockers
1. Fix Home page rendering when baseUrl is configured (30 min)
2. Auto-select latest entry on /debug load (30 min)

### Phase 2: Remaining Visual Consistency
3. Standardize loading indicators across all pages (30 min)
4. Fix Inspector Dashboard skeleton with shimmer (30 min)
5. Clean up Debug entries list - hide zero counts (30 min)
