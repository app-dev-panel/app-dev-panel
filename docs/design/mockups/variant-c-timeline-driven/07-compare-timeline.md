# Variant C: Timeline-Driven — Compare Timelines

## Two Timelines Stacked for Comparison

Select 2-3 entries from the debug list to compare their timelines side by side (vertically stacked).
A shared time axis and diff highlights make regressions immediately visible.

```
┌──────────────────────────────────────────────────────────────────────────────────────────────────────────────────┐
│ ┌─ADP─┐  Compare Timelines ── 2 entries ── Shared time axis                    [Swap Order] [Exit Compare]    │
├────┬───┴─────────────────────────────────────────────────────────────────────────────────────────────────────────┤
│    │                                                                                                            │
│    │  ┌─ Shared Time Axis ─────────────────────────────────────────────────────────────────────────────────┐   │
│    │  │  0ms        100ms       200ms       300ms       400ms       500ms       600ms                      │   │
│    │  │  ├───────────┼───────────┼───────────┼───────────┼───────────┼───────────┤                          │   │
│    │  └────────────────────────────────────────────────────────────────────────────────────────────────────┘   │
│ ┌──┤                                                                                                            │
│ │🔍│  ┌─ A: GET /api/users/42 ── 247ms ── 200 OK ── ID: 6f3a9b ── 2026-03-15 14:32:07 ───────────────────┐  │
│ │  │  │                                                                                                    │  │
│ │📋│  │  Request   ████████████████████████████                                                            │  │
│ │  │  │  Middlewar   ██████████████████████████                                                             │  │
│ │⏱ │  │    Auth      ████████                                                                              │  │
│ │  │  │    CORS           ███                                                                              │  │
│ │📊│  │  Router             ██████                                                                         │  │
│ │  │  │  Handler                  █████████████████████                                                     │  │
│ │🗄 │  │    DB #1                   ████                                                                    │  │
│ │  │  │    DB #2                       ██████                                                               │  │
│ │🔔│  │    Event                             ███                                                            │  │
│ │  │  │  Response                                 █████████                                                 │  │
│ │🌐│  │                                                                                                    │  │
│ │  │  └────────────────────────────────────────────────────────────────────────────────────────────────────┘  │
│ │⚙ │                                                                                                         │
│ └──┤  ┌─ B: GET /api/users/42 ── 534ms ── 200 OK ── ID: 8b2d5e ── 2026-03-15 14:28:15 ── ⚠ SLOW ────────┐  │
│    │  │                                                                                                    │  │
│    │  │  Request   ████████████████████████████████████████████████████████████████████████████             │  │
│    │  │  Middlewar   ████████████████████████████████████████████████████████████████████████████            │  │
│    │  │    Auth      ████████                                                                              │  │
│    │  │    CORS           ███                                                                              │  │
│    │  │  Router             ██████                                                                         │  │
│    │  │  Handler                  ███████████████████████████████████████████████████████████████            │  │
│    │  │    DB #1                   ████                                                                     │  │
│    │  │    DB #2                       ██████████████████████████████████████  ◀─ 2.8x slower               │  │
│    │  │    HTTP                                                   ████████████████  ◀─ new span             │  │
│    │  │    Event                                                               ███                          │  │
│    │  │  Response                                                                  █████████                │  │
│    │  │                                                                                                    │  │
│    │  └────────────────────────────────────────────────────────────────────────────────────────────────────┘  │
│    │                                                                                                            │
│    ├─── Diff Summary ────────────────────────────────────────────────────────────────────────────────────────────┤
│    │                                                                                                            │
│    │  ┌─ Changes ────────────────────────────────────────────────────────────────────────────────────────────┐  │
│    │  │                                                                                                      │  │
│    │  │  Total:     A: 247ms → B: 534ms  (+287ms, +116%)  ▲ REGRESSION                                      │  │
│    │  │                                                                                                      │  │
│    │  │  ┌───────────────────────┬───────────┬───────────┬───────────┬────────────────────────────────────┐   │  │
│    │  │  │ Segment               │ Entry A   │ Entry B   │ Delta     │ Bar                                │   │  │
│    │  │  ├───────────────────────┼───────────┼───────────┼───────────┼────────────────────────────────────┤   │  │
│    │  │  │ Auth middleware       │    42ms   │    42ms   │     0ms   │ ═══════════                        │   │  │
│    │  │  │ CORS middleware       │    18ms   │    18ms   │     0ms   │ ═══════════                        │   │  │
│    │  │  │ Router                │    28ms   │    28ms   │     0ms   │ ═══════════                        │   │  │
│    │  │  │ DB Query #1           │    12ms   │    12ms   │     0ms   │ ═══════════                        │   │  │
│    │  │  │ DB Query #2           │    34ms   │    96ms   │   +62ms   │ ════════════════════████████ ▲     │   │  │
│    │  │  │ HTTP Client           │     -     │    52ms   │   +52ms   │ ──────────── NEW ████████████ ▲    │   │  │
│    │  │  │ Event dispatch        │    18ms   │    18ms   │     0ms   │ ═══════════                        │   │  │
│    │  │  │ Response              │    51ms   │    51ms   │     0ms   │ ═══════════                        │   │  │
│    │  │  └───────────────────────┴───────────┴───────────┴───────────┴────────────────────────────────────┘   │  │
│    │  │                                                                                                      │  │
│    │  │  Key findings:                                                                                       │  │
│    │  │  • DB Query #2 is 2.8x slower in entry B (34ms → 96ms)                                               │  │
│    │  │  • Entry B includes an additional HTTP client call (52ms) not present in A                            │  │
│    │  │  • Other segments are identical in duration                                                           │  │
│    │  │                                                                                                      │  │
│    │  └──────────────────────────────────────────────────────────────────────────────────────────────────────┘  │
│    │                                                                                                            │
└────┴─────────────────────────────────────────────────────────────────────────────────────────────────────────────┘
```

## Three-Way Comparison

When three entries are selected:

```
│    │  ┌─ A: 247ms ─────────────────────┐                                                                     │
│    │  │ Request ██████████████████████  │                                                                     │
│    │  │ Handler     █████████████████  │                                                                     │
│    │  └─────────────────────────────────┘                                                                     │
│    │  ┌─ B: 534ms ──────────────────────────────────────────────┐                                             │
│    │  │ Request ████████████████████████████████████████████████ │                                             │
│    │  │ Handler     ███████████████████████████████████████████  │                                             │
│    │  └──────────────────────────────────────────────────────────┘                                             │
│    │  ┌─ C: 189ms ──────────────────┐                                                                         │
│    │  │ Request ████████████████████ │                                                                         │
│    │  │ Handler     ███████████████  │                                                                         │
│    │  └──────────────────────────────┘                                                                         │
```

## Comparison Controls

```
┌─ Compare Controls ───────────────────────────────────────────────────────────────────────────────────────────┐
│                                                                                                              │
│  Align by: (● Request start) (○ Handler start) (○ First DB query)                                           │
│                                                                                                              │
│  Show:  [✓] All segments   [✓] Diff highlights   [✓] Delta column   [ ] Percentage change                   │
│                                                                                                              │
│  Filter: [✓] Request  [✓] Middleware  [✓] Database  [✓] HTTP  [✓] Events  [✓] Response                      │
│                                                                                                              │
│  Scale:  (● Absolute time — shared axis)  (○ Relative — each entry 100% width)                               │
│                                                                                                              │
└──────────────────────────────────────────────────────────────────────────────────────────────────────────────┘
```

## Diff Highlight Markers

```
Visual indicators used in comparison timelines:

  ◀─ 2.8x slower      Annotation on bars that are significantly slower in one entry
  ◀─ new span          Annotation on bars that exist in one entry but not the other
  ◀─ removed           Dashed outline where a span existed in the other entry but not this one
  ▲ REGRESSION         Red badge on the diff summary row when delta is significant
  ▼ IMPROVEMENT        Green badge when a segment got faster
  ═ UNCHANGED          Grey equals sign when duration is within 5% tolerance
```
