# Variant C: Timeline-Driven — Inspector: Routes with Timing Overlay

## Routes List with Timing Distribution

The routes inspector shows all registered routes overlaid with aggregated timing data from recent
debug entries. This connects the static route configuration with actual runtime performance.

```
┌──────────────────────────────────────────────────────────────────────────────────────────────────────────────────┐
│ ┌─ADP─┐  Inspector: Routes ── 34 routes registered ── Timing from last 100 entries            [Refresh]       │
├────┬───┴─────────────────────────────────────────────────────────────────────────────────────────────────────────┤
│    │                                                                                                            │
│    │  ┌─ Filters ────────────────────────────────────────────────────────────────────────────────────────────┐  │
│    │  │ Method: [All ▼]  Group: [All ▼]  Search: [________________________]  [Show only hit routes ☑]      │  │
│    │  └──────────────────────────────────────────────────────────────────────────────────────────────────────┘  │
│ ┌──┤                                                                                                            │
│ │🔍│  ┌──────┬────────────────────────┬──────────────────────────┬──────┬───────────────────────────────┬─────┐ │
│ │  │  │Method│ Pattern                │ Handler                  │ Hits │ Response Time Distribution     │ Avg │ │
│ │📋│  ├──────┼────────────────────────┼──────────────────────────┼──────┼───────────────────────────────┼─────┤ │
│ │  │  │ GET  │ /api/users             │ UserController::index    │  12  │ ╟──┤████████├─────────╢       │178ms│ │
│ │⏱ │  │ GET  │ /api/users/{id}        │ UserController::show     │  28  │ ╟─┤████├───────────────╢      │247ms│ │
│ │  │  │ POST │ /api/users             │ UserController::create   │   4  │ ╟───┤██████████├──╢           │312ms│ │
│ │📊│  │ PUT  │ /api/users/{id}        │ UserController::update   │   8  │ ╟─┤████████├────╢             │198ms│ │
│ │  │  │DELETE│ /api/users/{id}        │ UserController::delete   │   2  │ ╟──┤██├────╢                  │134ms│ │
│ │🗄 │  │      │                        │                          │      │                               │     │ │
│ │  │  │ GET  │ /api/orders            │ OrderController::index   │  15  │ ╟────┤██████████████├─╢       │423ms│ │
│ │🔔│  │ POST │ /api/orders            │ OrderController::create  │   6  │ ╟──────┤██████████████████├╢  │534ms│ │
│ │  │  │ GET  │ /api/orders/{id}       │ OrderController::show    │  19  │ ╟─┤██████├───╢               │267ms│ │
│ │🌐│  │ POST │ /api/orders/{id}/cancel│ OrderController::cancel  │   3  │ ╟────┤████████████████├╢     │412ms│ │
│ │  │  │      │                        │                          │      │                               │     │ │
│ │⚙ │  │ GET  │ /api/products          │ ProductController::index │  22  │ ╟┤██├──╢                      │ 89ms│ │
│ └──┤  │ GET  │ /api/products/{id}     │ ProductController::show  │  14  │ ╟┤████├╢                      │ 67ms│ │
│    │  │      │                        │                          │      │                               │     │ │
│    │  │ POST │ /api/auth/login        │ AuthController::login    │   9  │ ╟──┤████████├──╢              │156ms│ │
│    │  │ POST │ /api/auth/logout       │ AuthController::logout   │   5  │ ╟┤██├╢                        │ 34ms│ │
│    │  │      │                        │                          │      │                               │     │ │
│    │  │ GET  │ /health                │ HealthController::check  │  18  │ ╟┤├╢                           │ 12ms│ │
│    │  │ GET  │ /dashboard             │ DashController::index    │   3  │ ╟─────┤██████████████├────╢   │423ms│ │
│    │  │      │                        │                          │      │                               │     │ │
│    │  │ GET  │ /api/reports/{type}    │ ReportController::show   │   0  │ (no data)                     │  -  │ │
│    │  │ POST │ /api/webhooks          │ WebhookController::recv  │   0  │ (no data)                     │  -  │ │
│    │  └──────┴────────────────────────┴──────────────────────────┴──────┴───────────────────────────────┴─────┘ │
│    │                                                                                                            │
└────┴─────────────────────────────────────────────────────────────────────────────────────────────────────────────┘
```

## Box Plot Legend

The "Response Time Distribution" column uses a box-and-whisker plot:

```
  ╟──────┤████████████████├──────────╢
  ^      ^               ^          ^
  min    P25 (Q1)        P75 (Q3)   max
         └───────────────┘
              IQR (interquartile range, filled)

  Scale: 0ms ─────────────────────── 600ms (auto-scaled to slowest route)
```

- Filled region (████): middle 50% of response times (P25 to P75)
- Whiskers (├── and ──┤): P25 to min, P75 to max
- Full range (╟ to ╢): absolute min and max
- If fewer than 4 data points: show individual dots instead of box plot

## Clicking a Route — Timeline Overlay

Clicking a route row opens a detail section showing recent entries for that route as mini-timelines:

```
│  │ GET  │ /api/users/{id}        │ UserController::show     │  28  │ ╟─┤████├───────────────╢      │247ms│
│  ├──────┴────────────────────────┴──────────────────────────┴──────┴───────────────────────────────┴─────┤
│  │  ┌─ Recent Entries (/api/users/{id}) ─────────────────────────────────────────── Avg: 247ms ───────┐ │
│  │  │                                                                                                  │ │
│  │  │  0ms       100ms      200ms      300ms      400ms                                                │ │
│  │  │  ├──────────┼──────────┼──────────┼──────────┤                                                   │ │
│  │  │  ID:6f3a9b  ██▓░▓▓░░████░██                                               247ms  200 OK         │ │
│  │  │  ID:5e2c8a  ██▓░▓░░███░█                                                  198ms  200 OK         │ │
│  │  │  ID:7d4b1f  ██▓░░▓▓▓▓████░░░██                                            312ms  200 OK         │ │
│  │  │  ID:3a9e7c  ██▓░▓░████░██                                                 223ms  200 OK         │ │
│  │  │  ID:8b2d5e  ██▓░░░▓▓▓▓████████░░░░██                                      401ms  200 OK  ⚠ slow│ │
│  │  │                                                                                                  │ │
│  │  │  [Show all 28 entries]                                                                           │ │
│  │  └──────────────────────────────────────────────────────────────────────────────────────────────────┘ │
│  ├──────┬────────────────────────┬──────────────────────────┬──────┬───────────────────────────────┬─────┤
│  │ POST │ /api/users             │ UserController::create   │   4  │ ╟───┤██████████├──╢           │312ms│
```

## Route Detail Side Panel

```
┌─ Route Detail ── GET /api/users/{id} ──────────────────────────────────────────────────────────────────────┐
│                                                                                                            │
│  Pattern:      /api/users/{id:\d+}                                                                         │
│  Name:         user.show                                                                                   │
│  Handler:      App\Controller\UserController::show                                                         │
│  Middleware:    AuthMiddleware, CorsMiddleware, DebugMiddleware                                             │
│  Methods:      GET, HEAD                                                                                   │
│  Group:        /api/users                                                                                  │
│                                                                                                            │
│  ┌─ Performance Summary (last 28 requests) ──────────────────────────────────────────────────────────────┐ │
│  │  Avg: 247ms  │  P50: 223ms  │  P95: 401ms  │  P99: 401ms  │  Min: 198ms  │  Max: 401ms              │ │
│  │  Avg DB time: 46ms (2.1 queries avg)  │  Avg memory: 4.2MB                                           │ │
│  └──────────────────────────────────────────────────────────────────────────────────────────────────────  ┘ │
│                                                                                                            │
└────────────────────────────────────────────────────────────────────────────────────────────────────────────┘
```
