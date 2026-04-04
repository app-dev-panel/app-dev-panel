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

### 3. Inspector pages show infinite spinners
- **Pages**: Routes, Events, Config, Translations, Storage, Environment
- **Problem**: CircularProgress spins indefinitely when API calls fail or timeout.
  No error boundary, no timeout, no retry button.
- **Fix**: Add error state handling to RTK Query hooks. Show error message with retry button
  after 10s timeout. Use consistent `EmptyState` component for connection errors.

### 4. /gen-code shows 404 "Unknown page"
- **Page**: `/gen-code`
- **Problem**: Route is defined but the page component doesn't exist or fails to load.
- **Fix**: Either implement the page or remove the route/sidebar entry until it's ready.

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

### 8. Inconsistent empty states
- **Where**: Open API (duck icon), Debug Live (spinner icon), Commands (lightbulb),
  Code Quality (plain text), LLM (alert box)
- **Fix**: Use the existing `EmptyState` component consistently across all pages.
  Standardize on: icon + title + description + optional action button.

### 9. HTML nesting error in LivePage
- **Page**: `/debug/live`
- **Problem**: React warning: `<div> cannot be a descendant of <p>`. SectionTitle renders
  a `<p>` (MuiTypography-body1) that contains a `<div>` child.
- **Fix**: Change SectionTitle's Typography variant to use `component="div"` instead of `<p>`.

### 10. LLM page has empty gray card
- **Page**: `/llm`
- **Problem**: Gray placeholder card at top with no content, followed by "Connect an LLM provider" alert.
- **Fix**: Remove the empty placeholder. Show a proper setup wizard or configuration form.

## Implementation Plan

### Phase 1: Fix Critical UX Blockers (Priority: High)
1. Fix HTML nesting error in SectionTitle (5 min)
2. Fix Home page rendering when baseUrl is configured (30 min)
3. Auto-select latest entry on /debug load (30 min)
4. Add error/timeout states to Inspector pages (1h)
5. Remove or implement /gen-code route (15 min)

### Phase 2: Visual Consistency (Priority: Medium)
6. Standardize loading indicators across all pages (30 min)
7. Standardize empty states using EmptyState component (1h)
8. Fix Inspector Dashboard skeleton with shimmer (30 min)
9. Clean up Debug entries list - hide zero counts (30 min)
10. Fix LLM page placeholder card (15 min)
