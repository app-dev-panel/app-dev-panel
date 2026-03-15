# DX Engineer Evaluation

Evaluator perspective: PHP backend developer, 50+ daily uses of the debug panel, running PhpStorm on a 27" primary monitor with the debug panel on a secondary 24" monitor. Priorities: speed of diagnosis, low cognitive load, and zero-friction repetitive workflows.

## Scoring Matrix

| Criterion                   | A: Command Center | B: Split Focus | C: Timeline-Driven | D: Minimal Zen | E: Dashboard Grid |
|-----------------------------|:-:|:-:|:-:|:-:|:-:|
| 1. Keystroke Efficiency     | 9 | 8 | 6 | 7 | 5 |
| 2. Muscle Memory            | 9 | 8 | 6 | 7 | 4 |
| 3. Glanceability            | 8 | 7 | 9 | 5 | 7 |
| 4. Context Switching Cost   | 7 | 9 | 5 | 6 | 6 |
| 5. Power User Features      | 9 | 7 | 7 | 8 | 6 |
| 6. URL Shareability         | 9 | 7 | 7 | 6 | 5 |
| 7. Multi-monitor Workflow   | 7 | 8 | 7 | 6 | 9 |
| 8. Error Surfacing          | 8 | 8 | 9 | 5 | 7 |
| 9. Customizability          | 5 | 6 | 5 | 4 | 10 |
| 10. Integration with IDE    | 8 | 8 | 7 | 7 | 6 |
| **Total**                   | **79** | **76** | **68** | **61** | **65** |

### Score Justifications

**1. Keystroke Efficiency**

- **A (9):** Number keys 1-9 switch collector tabs instantly. Ctrl+K command palette for everything else. Ctrl+[ and Ctrl+] navigate entries. Fewest keystrokes to reach any data point.
- **B (8):** Arrow keys navigate the always-visible entry list, Enter selects. One click loads detail. Good, but switching between collectors requires scrolling the accordion or clicking sections.
- **C (6):** Click-on-bar interaction model is mouse-heavy. Zooming, panning, and clicking timeline segments requires pointer precision. Keyboard shortcuts exist (Tab to cycle bars) but are awkward for targeted access.
- **D (7):** Ctrl+K is the universal entry point, which is efficient for known destinations but requires typing every time. No numbered tab shortcuts. J/K vim-style is nice. Progressive disclosure means extra clicks to drill into data.
- **E (5):** Widget-based layout means data is scattered. Finding a specific query requires locating the DB widget, scrolling to it, then interacting. No global keyboard-first navigation.

**2. Muscle Memory**

- **A (9):** Fixed layout. Sidebar always in the same place, tabs always in the same order, context bar always at top. After a week, your hands know where everything is.
- **B (8):** Three-column layout is stable. Entry list is always on the left. Content on the right. Consistent spatial model. Slight penalty because accordion order within content can change.
- **C (6):** Timeline position depends on the request's timing characteristics. Bars shift with zoom. The spatial position of "my DB queries" changes per request. Harder to build motor memory.
- **D (7):** Command palette is always Ctrl+K, which becomes muscle memory fast. But the content area changes completely between views with animated transitions, making spatial memory unreliable.
- **E (4):** Widgets can be rearranged. What was in the top-left last session might be bottom-right now. Custom dashboards compound this. The user fights their own customizations.

**3. Glanceability**

- **A (8):** Context bar shows method/status/URL/time at a glance. Tab badges show counts and error indicators. Color-coded status codes. Strong at-a-glance summary.
- **B (7):** Entry list with color-coded methods and status is scannable. Selected entry highlighted. But you need to be looking at two places (list + content) simultaneously.
- **C (9):** The timeline is the strongest glanceability story. A single visual shows the entire request lifecycle: what took time, what errored, what overlapped. Red bars for errors jump out immediately. You can see the problem before you read anything.
- **D (5):** Cards grid at Level 1 shows counts and sparklines, but the information is abstract. "12 msgs" in the Log card tells you very little. You must drill in to understand anything. Minimal chrome means minimal signaling.
- **E (7):** Multiple widgets visible simultaneously shows many data facets. But the density depends on layout configuration, and widgets compete for attention. No clear visual hierarchy tells you where to look first.

**4. Context Switching Cost**

- **A (7):** Tab switching is instant (no transition), but switching tabs replaces the entire content area. You lose your scroll position in the Logs tab when you check DB queries, then go back.
- **B (9):** Best in class. The entry list never disappears. Selecting a different entry updates the content area but your mental model stays anchored by the persistent list. Accordion view lets you see multiple collectors at once without switching.
- **C (5):** Moving between timeline view and list-based views (routes, config) is a fundamentally different interaction paradigm. Going from "click bars on a waterfall" to "read a table" creates significant cognitive gear-shifting.
- **D (6):** Page transitions (slide animations) create a sense of movement but also disorientation. Every navigation is a full context switch. No persistent reference points on screen.
- **E (6):** Widgets provide co-visibility, reducing some switching. But switching dashboards (Debug to Inspector) is a complete context wipe. And widget-internal interactions (filtering, expanding rows) happen in small containers that feel cramped.

**5. Power User Features**

- **A (9):** Command palette, number-key tab switching, multi-column sort (Shift+Click), column resize with double-click auto-fit, density toggle, CSV/JSON/TSV export, right-click column visibility. Every DevTools-standard power feature is present.
- **B (7):** Keyboard navigation in lists, Ctrl+Click for compare, right-click context menu with Copy cURL. Solid but fewer dedicated shortcuts than A.
- **C (7):** Zoom/pan with mousewheel, double-click-to-fit, minimap navigation, Shift+Click multi-select for comparison. Power features exist but are specific to the timeline metaphor and do not carry over to table views.
- **D (8):** Vim-style J/K, Ctrl+K as universal command, multiple Ctrl+key chords for direct navigation. The command palette itself is a power user feature. Lacks density toggle and column customization in tables.
- **E (6):** Layout editing, widget resize/move, export/import layout JSON. These are power features, but they are *meta-features* about configuring the tool, not about accessing data faster.

**6. URL Shareability**

- **A (9):** Explicit design: entry ID, active tab, sort, filters, page, compare target all in URL params. "Hey, look at this query" becomes a link.
- **B (7):** Entry ID and breadcrumb path in URL. But the accordion state (which collectors are expanded) is not in the URL, so a shared link might not show the same view.
- **C (7):** Entry ID and selected segment can be in URL. But zoom level and pan position are harder to encode, so the recipient sees a different viewport.
- **D (6):** URL tracks the current page but not the progressive disclosure state (which card is expanded, what level of detail is shown). Shared links land on Level 1, not the specific detail the sender was looking at.
- **E (5):** Dashboard selection and entry ID can be in URL, but widget states (filters, expanded rows, scroll positions) are per-widget and not serialized to URL. Sharing is imprecise.

**7. Multi-monitor Workflow**

- **A (7):** Works fine on a secondary monitor. Fixed layout scales well. But the single-pane content area means you cannot see two collectors side-by-side without compare mode.
- **B (8):** The three-column layout adapts naturally to different screen widths. The resizable split between entry list and content lets you optimize for your monitor. Good spatial use.
- **C (7):** Timeline benefits from wide monitors (more horizontal resolution = more visible time range). Detail panel at the bottom uses vertical space well. Adequate but not exceptional.
- **D (6):** Full-width single-column layout wastes horizontal space on wide monitors. A 24" secondary monitor shows content centered with large margins. No way to tile data.
- **E (9):** Built for multi-monitor. Grid layout naturally fills any screen size. You could put a dashboard with DB + Logs widgets on one monitor and the timeline on another. Best use of screen real estate.

**8. Error Surfacing**

- **A (8):** Tab badges show error counts in red. Status code in context bar is color-coded. Exception tab exists. Errors are visible but require looking at the right places.
- **B (8):** Error rows in tables get red background tint. Status 5xx is red in the entry list. Slow queries get orange highlighting. Consistent color-coding throughout.
- **C (9):** Red bars on the timeline scream "error here" with zero reading required. Errors in the waterfall are spatially positioned relative to other operations, showing causality. Diagonal hash pattern on slow segments. Strongest error surfacing.
- **D (5):** Cards show counts but do not visually alarm. A card saying "1 exception" in small text is easy to miss. No persistent indicators. You must be on the right page to notice problems.
- **E (7):** Error states in widgets are visible if you have the right widget displayed. Multiple simultaneous widgets can show errors in parallel. But if the Exception widget is minimized or on a different dashboard, you miss it.

**9. Customizability**

- **A (5):** Column width/order/visibility via localStorage. Theme toggle. Density toggle. Table page size. Reasonable defaults that you can tune, but the overall layout is fixed.
- **B (6):** Resizable panel split. Accordion section reordering and pinning. Column customization in tables. Theme. Slightly more flexible than A.
- **C (5):** Filter by collector type on timeline. Column visibility in tables. Density options. But the timeline-centric layout is not negotiable.
- **D (4):** Theme toggle. Column resize. That is essentially it. The philosophy is "we made the right choices so you do not have to customize." This works until it doesn't.
- **E (10):** Full widget system. Create dashboards, add/remove/resize/reposition widgets, create custom dashboards, export/import layouts. This is a configurable workspace, not a fixed tool.

**10. Integration with IDE**

- **A (8):** Source references (file:line) in stack traces and query sources are natural click-to-IDE targets. Copy cURL, export data, and dense compact mode work well alongside PhpStorm.
- **B (8):** Source links in tables (e.g., `UserService::create:42`). Copy cURL from context menu. The persistent entry list acts as a secondary navigation that complements IDE debugging.
- **C (7):** Stack trace links exist in detail panel. "Jump to source" in right-click menu. But the timeline-centric view is less about code structure and more about runtime behavior, which is a different mental model than IDE debugging.
- **D (7):** Copy handlers in expanded rows. Source references present. But the minimal aesthetic means less information density per screen, requiring more switching between panel and IDE.
- **E (6):** Source links in table widgets. But the widget system's overhead (title bars, resize handles, grid gaps) eats into the space available for actual data, making side-by-side IDE+panel usage less efficient.

## Daily Workflow Scenarios

### Scenario 1: "My API returned 500, what happened?"

This is the most common debugging scenario. An endpoint returned a server error and you need to find the exception.

**A: Command Center (Excellent)**
Context bar immediately shows `500` in red with the URL. Click the "Exceptions" tab (or press `6` if it's the 6th tab). Full exception with stack trace loads instantly. Total: 1 click or 1 keystroke. You can share the exact URL with `?id=xxx&tab=exceptions` to a colleague.

**B: Split Focus (Very Good)**
The entry list shows the 500 request in red. Click it (or it auto-selects if "Auto-latest" is ON). Content area loads with accordion view. If exceptions are expanded by default, you see the error immediately. If not, one more click to expand. Total: 0-2 clicks. The entry list remaining visible means you can quickly check if other recent requests also failed.

**C: Timeline-Driven (Good)**
The timeline shows a red bar for the exception. You see *when* in the request lifecycle it occurred. Click the red bar to get the exception detail in the bottom panel. Total: 1 click. The timeline gives you extra context (the error happened after DB query #3), but if you just want the stack trace, you had to hunt for the red bar first.

**D: Minimal Zen (Adequate)**
Entry pill shows `500` in red. The debug dashboard cards appear. You see an "Exceptions" card with count "1". Click it to expand, then click again for full detail. Total: 2-3 clicks with progressive disclosure steps. No way to jump directly to the exception without going through the levels.

**E: Dashboard Grid (Adequate)**
Depends entirely on dashboard configuration. If you have an Exception widget on your debug dashboard, it shows the error immediately. If not, you need to add one or switch to a dashboard that has it. Once found, click to expand the row. Total: 1-3 clicks depending on setup. First-time setup cost is high.

### Scenario 2: "Is my N+1 query fixed?"

You just pushed a fix for an N+1 query problem. You need to verify the query count dropped and no duplicate queries remain.

**A: Command Center (Excellent)**
Switch to DB tab (number key or click). SmartTable shows all queries with count in the title ("DB (5)" vs the old "DB (47)"). Sort by Query column to spot duplicates. Filter by query text. Use compare mode (`Ctrl+Shift+C`) to compare the old entry with the new one side by side. Total: 2-3 actions. Compare mode with the old entry's URL param makes verification definitive.

**B: Split Focus (Very Good)**
Select the new request in entry list. Expand DB section in accordion. See query count. Then Ctrl+Click the old request to enter compare mode, seeing both query lists in horizontal split. Total: 2-3 clicks. The persistent entry list makes finding old vs new entries fast.

**C: Timeline-Driven (Very Good)**
The timeline immediately reveals the difference: where there used to be 47 short green bars (DB queries), there are now 5. The visual delta is dramatic and instant. Click a DB bar for query detail. For precise comparison, use compare timeline view. Total: 0 actions for visual confirmation, 1-2 for details. Best *visual* answer to "did it get better?"

**D: Minimal Zen (Mediocre)**
Navigate to debug dashboard. DB card shows "5 queries" (good). Click to expand for details. But comparing with the previous request requires navigating back (entry pill prev button), counting queries, then navigating forward again. No compare mode mentioned in the spec. Total: 4-6 actions with mental note-taking.

**E: Dashboard Grid (Good)**
DB Queries widget shows count in title "DB Queries (5)". Table shows all queries. But comparing old vs new requires either memory or opening a second browser tab. No built-in compare mode at the dashboard level. Total: 1-2 actions to verify current state, but comparison is manual.

### Scenario 3: "What events fired during this request?"

You need to see all dispatched events, their listeners, and execution order to debug a hook that is not firing.

**A: Command Center (Excellent)**
Switch to Events tab. SmartTable shows all events with listener counts, sorted by dispatch order. Filter by event class name to find your specific event. If it is missing, that is your answer. Tab badge shows total count. Total: 1 click + optional filter typing.

**B: Split Focus (Very Good)**
Expand the Events section in the accordion. See the event list. If you need more space, click to enter focused view for the Events collector. Filter and sort within the table. Total: 1-2 clicks.

**C: Timeline-Driven (Good)**
Events appear as orange bars on the timeline, positioned chronologically. Click an event bar to see its listeners in the detail panel. But the timeline shows events interleaved with all other operations, making it harder to get a clean list. Switching to the dedicated Events list view (via icon rail) gives a table. Total: 1-2 clicks but requires choosing between timeline (context) and list (completeness).

**D: Minimal Zen (Adequate)**
From the dashboard, click the Events card. Level 2 shows an inline list. Click through to Level 3 for the full table with filtering. The search box filters by event class. Total: 2-3 clicks through progressive disclosure.

**E: Dashboard Grid (Good)**
If an Events widget is on the current dashboard, the data is right there. Filter within the widget, expand rows for listener detail. If the widget is not present, add it or switch dashboards. Total: 0-2 clicks depending on dashboard setup.

### Scenario 4: "Compare performance before and after my fix"

You made an optimization and want to compare two request entries quantitatively: response time, memory, query count, query durations.

**A: Command Center (Best)**
Click "Compare" in context bar (or `Ctrl+Shift+C`). Select the old entry. URL updates to `?compare=def456`. Both entries shown side by side with diffs highlighted. Switch between collector tabs to compare each data facet. Export both as JSON for external analysis. Total: 2-3 clicks for full comparison. URL is shareable.

**B: Split Focus (Very Good)**
Ctrl+Click the old entry in the list to activate compare mode. Content area splits horizontally showing both entries. Scroll through both simultaneously. The entry list persists, making it easy to pick the right entries. Total: 1 Ctrl+Click. Horizontal split may feel cramped on smaller screens.

**C: Timeline-Driven (Good)**
Compare timeline view shows both timelines stacked vertically. Visual comparison of timing is excellent: you can see exactly which phases got faster or slower. But comparing specific values (query count, memory) requires clicking into detail panels. Total: 2-3 actions. Best for timing comparison, weaker for data comparison.

**D: Minimal Zen (Poor)**
No compare mode described in the spec. You would need to open two browser tabs and manually compare. Or use prev/next navigation and remember numbers. This is a significant gap for the optimization workflow.

**E: Dashboard Grid (Mediocre)**
No built-in compare mode at the dashboard level. You could open the panel in two browser windows, each showing a different entry, and tile them. Layout export/import does not help here. Total: manual workaround required.

## Top 3 Ranking

1. **Variant A: Command Center** (79/100)
2. **Variant B: Split Focus** (76/100)
3. **Variant C: Timeline-Driven** (68/100)

## Recommendation

**Build Variant A (Command Center) as the primary design, and incorporate two specific ideas from B and C.**

Variant A wins because daily debugging is fundamentally about *fast, repetitive access to specific data categories*. When you debug 50+ requests per day, you are not exploring -- you are executing a known drill. Tab-based navigation with keyboard shortcuts (number keys), a persistent context bar showing request summary, and a command palette for everything else creates the fastest path from "something is wrong" to "here is the data I need." URL shareability with full state serialization is a force multiplier for team debugging.

**Steal from Variant B:** The always-visible entry list is B's killer feature. Consider adding a collapsible left panel (wider than A's icon rail) that shows the entry list, similar to B's middle column. This addresses A's weakness of requiring a separate "Debug List" page to browse entries. The entry list should be togglable (keyboard shortcut to show/hide) so power users who want maximum content width can dismiss it.

**Steal from Variant C:** Add an optional "Timeline" collector tab within A's tab system. When the user clicks the Timeline tab, they see C's waterfall view of the current request. This gives C's outstanding glanceability for performance debugging without imposing the timeline-first paradigm on every workflow. The timeline tab would be particularly useful for Scenario 2 (N+1 verification) and Scenario 4 (performance comparison).

**Avoid Variant D's minimalism** for a debugging tool. Debugging is information-dense work. Removing persistent navigation in favor of a command palette creates a tool that is pleasant to demo but frustrating to use at speed. Progressive disclosure (3 levels to see a stack trace) is the wrong tradeoff for a tool where urgency is the default emotional state.

**Avoid Variant E's widget system** as the primary paradigm. The configuration overhead (arranging widgets, managing dashboards) front-loads complexity onto every user before they can debug their first request. Grafana works because dashboards are built once and monitored passively. Debug panels are used actively and the "what do I need" changes per request. A fixed layout with sensible defaults and targeted customization (column visibility, density, theme) serves 95% of use cases with zero setup cost.
