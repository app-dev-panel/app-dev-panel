# 08 — Floating Action Bar

## Design Concept

The floating action bar is a contextual toolbar that appears near selected content.
It provides quick actions relevant to the current selection without requiring a trip
to the menu bar or right-click. It floats above the content at z-index 30.

## Positioning

The bar appears:
- **Below selected table rows** — anchored to bottom-right of selection
- **Above selected text** — anchored to top of selection (like Medium's editor)
- **Beside accordion headers** — anchored to right of header on hover

```
Content area with floating action bar:
┌──────────────────────────────────────────────────────────────────────────────┐
│                                                                            │
│  ┌────┬──────────────────────────────────────────┬───────┬──────┬────────┐ │
│  │ 1  │ INSERT INTO users (name, email, crea...) │  8ms  │   1  │ OK     │ │
│  │ 2  │ SELECT * FROM roles WHERE active = 1     │  2ms  │   3  │ OK     │ │
│  │ 3  │ INSERT INTO user_roles (user_id, rol...) │  2ms  │   3  │ OK     │ │  <-- selected
│  └────┴──────────────────────────────────────────┴───────┴──────┴────────┘ │
│                                                                            │
│                          ┌──────────────────────────────────────┐           │
│                          │ [Copy SQL] [Explain] [Show Params] │           │
│                          └──────────────────────────────────────┘           │
│                          ^^^^^ floating action bar ^^^^^                   │
│                                                                            │
└──────────────────────────────────────────────────────────────────────────────┘
```

## Action Bar Variants

### Query Row Selected

```
┌─────────────────────────────────────────────────────┐
│  [Copy SQL]  [Explain]  [Show Params]  [Copy cURL]  │
└─────────────────────────────────────────────────────┘
```

### Log Entry Selected

```
┌──────────────────────────────────────────────────────┐
│  [Copy Message]  [View Context]  [Copy Stack Trace]  │
└──────────────────────────────────────────────────────┘
```

### Event Row Selected

```
┌────────────────────────────────────────────────────────┐
│  [Show Listeners]  [View Payload]  [Copy Event Class]  │
└────────────────────────────────────────────────────────┘
```

### Text Selection in JSON/Code View

```
┌─────────────────────────────────────┐
│  [Copy]  [Search]  [Filter by This] │
└─────────────────────────────────────┘
```

### Multiple Rows Selected

```
┌──────────────────────────────────────────────┐
│  [Copy All]  [Export CSV]  [Export JSON]  (3) │
└──────────────────────────────────────────────┘
                                            ^^^ count badge
```

### Config Value Selected

```
┌─────────────────────────────────────────────┐
│  [Copy Value]  [Copy Path]  [Copy as JSON]  │
└─────────────────────────────────────────────┘
```

## Full Context Example: Query Selected

```
┌──────────────────────────────────────────────────────────────────────────────┐
│  Debug > #a1b2c3 > Database                                         [...]  │
├──────────────────────────────────────────────────────────────────────────────┤
│                                                                            │
│  Summary: 3 queries  |  12ms total  |  7 rows affected  |  0 errors       │
│                                                                            │
│  ┌────┬──────────────────────────────────────────┬───────┬──────┬────────┐ │
│  │ #  │ Query                                    │ Time  │ Rows │ Status │ │
│  ├────┼──────────────────────────────────────────┼───────┼──────┼────────┤ │
│  │ 1  │ INSERT INTO users (name, email, crea...) │  8ms  │   1  │ OK     │ │
│  │▎2  │ SELECT * FROM roles WHERE active = 1     │  2ms  │   3  │ OK     │ │  <-- selected
│  │ 3  │ INSERT INTO user_roles (user_id, rol...) │  2ms  │   3  │ OK     │ │
│  └────┴──────────────────────────────────────────┴───────┴──────┴────────┘ │
│                                              ┌──────────────────────────┐   │
│                                              │ Copy SQL  Explain  Params│   │
│                                              └──────────────────────────┘   │
│                                                                            │
│  Click "Explain" to see query execution plan.                              │
│                                                                            │
└──────────────────────────────────────────────────────────────────────────────┘
```

## Full Context Example: Log Entry with Stack Trace

```
┌──────────────────────────────────────────────────────────────────────────────┐
│  Debug > #a1b2c3 > Log                                              [...]  │
├──────────────────────────────────────────────────────────────────────────────┤
│                                                                            │
│  ┌────────────┬─────────┬──────────────────────────────────┬─────────────┐ │
│  │ Time       │ Level   │ Message                          │ Category    │ │
│  ├────────────┼─────────┼──────────────────────────────────┼─────────────┤ │
│  │ 14:23:07.1 │ DEBUG   │ Routing matched: POST /api/users │ router      │ │
│  │ 14:23:07.2 │ INFO    │ Creating new user: john@exam...  │ app.user    │ │
│  │▎14:23:07.4 │ ERROR   │ Failed to send welcome email:... │ app.mailer  │ │  <-- selected
│  └────────────┴─────────┴──────────────────────────────────┴─────────────┘ │
│                                                                            │
│             ┌──────────────────────────────────────────────────┐            │
│             │ Copy Message   View Context   Copy Stack Trace  │            │
│             └──────────────────────────────────────────────────┘            │
│                                                                            │
└──────────────────────────────────────────────────────────────────────────────┘
```

## Animation and Behavior

```
Appearance:
- Fades in over 150ms (opacity 0 -> 1)
- Slides up 4px (translateY)
- Appears 8px below/above selection

Dismissal:
- Click outside -> fade out 100ms
- Escape key -> fade out 100ms
- Selection changes -> crossfade to new position (150ms)
- Scroll -> follows selection OR dismisses if selection leaves viewport

Positioning rules:
1. Prefer below selection
2. If below would clip viewport, show above
3. If above would clip viewport, show to the right
4. Always align right edge with selection right edge
5. Never overlap the selected row itself
```

## Accessibility

```
- Action bar is reachable via Tab key after selecting a row
- Each action is a focusable button
- Escape dismisses the bar and returns focus to the table
- Screen reader announces: "Actions for [row description]"
- Actions have tooltips with keyboard shortcuts
```

## Action Bar with Keyboard Shortcut Hints

```
┌────────────────────────────────────────────────────────────────┐
│  Copy SQL (Ctrl+C)  │  Explain (Ctrl+E)  │  Params (Ctrl+P)  │
└────────────────────────────────────────────────────────────────┘
```

Shortcut hints appear after 1 second hover delay or immediately with Ctrl held.
