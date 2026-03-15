# Variant D: Minimal Zen — Smart Table

## Concept

When data is best presented as a table (routes, events, middleware, services), the Smart Table
component provides a clean, minimal-chrome table. No heavy borders, no alternating row colors,
no toolbar clutter. Just data with subtle separators, inline search, sortable columns, and
keyboard navigation.

## Default Table — Routes

```
┌────────────────────────────────────────────────────────────────────────────────────────────────────────────────────┐
│ ◆ ADP          ┌─ GET /api/users ── 200 ── 143ms ─┐     ◁  ▷         ⌘K Search…            ☀  ⋮                  │
│                 └──────────────────────────────────-┘                                                              │
├────────────────────────────────────────────────────────────────────────────────────────────────────────────────────┤
│                                                                                                                    │
│         Inspector  ›  Routes                                                                                       │
│                                                                                                                    │
│         ┌────────────────────────────────────────────────────────────────────────────────┐                          │
│         │  Search routes…                                                     24 routes │                          │
│         └────────────────────────────────────────────────────────────────────────────────┘                          │
│                                                                                                                    │
│         Method ▾     Path                          Handler                          Middleware                      │
│         ─────────────────────────────────────────────────────────────────────────────────────────────────────       │
│                                                                                                                    │
│         GET          /                              HomeController::index            auth, cors                     │
│         GET          /api/products                  ProductController::index         auth, cors, rate-limit         │
│         GET          /api/products/{id}             ProductController::show          auth, cors                     │
│         POST         /api/products                  ProductController::store         auth, cors, csrf               │
│         PUT          /api/products/{id}             ProductController::update        auth, cors, csrf               │
│         DELETE       /api/products/{id}             ProductController::destroy       auth, cors, csrf               │
│         GET          /api/users                     UserController::index            auth, cors, rate-limit         │
│         GET          /api/users/{id}                UserController::show             auth, cors                     │
│         POST         /api/users                     UserController::store            auth, cors, csrf               │
│         PUT          /api/users/{id}                UserController::update           auth, cors, csrf               │
│         DELETE       /api/users/{id}                UserController::destroy          auth, cors, csrf               │
│         POST         /api/auth/login                AuthController::login            cors, rate-limit               │
│         POST         /api/auth/logout               AuthController::logout           auth, cors                     │
│         POST         /api/auth/refresh              AuthController::refresh          cors                           │
│         GET          /api/orders                    OrderController::index           auth, cors                     │
│         GET          /api/orders/{id}               OrderController::show            auth, cors                     │
│         POST         /api/orders                    OrderController::store           auth, cors, csrf               │
│         GET          /api/categories                CategoryController::index        cors                           │
│         GET          /health                        HealthController::check          —                              │
│         GET          /metrics                       MetricsController::index         auth, internal                 │
│                                                                                                                    │
│         ─────────────────────────────────────────────────────────────────────────────────────────────────────       │
│         Showing 20 of 24                                                                     Load more ▾           │
│                                                                                                                    │
└────────────────────────────────────────────────────────────────────────────────────────────────────────────────────┘
```

## Table Design Principles

```
1. No outer border — table floats in whitespace
2. Single thin separator line under header
3. No vertical column dividers
4. No alternating row colors
5. Generous row height (40px) for readability
6. Hover: entire row gets a subtle background tint
7. Column headers: caption style (12px, uppercase, secondary color)
8. Sortable columns: click to sort, ▾/▴ indicator
```

## Sorted Column — Descending

```
         Method       Path ▾                         Handler                          Middleware
         ─────────────────────────────────────────────────────────────────────────────────────────────────────
                      ▲
                      │  ▾ = descending (current)
                      │  Click again → ▴ ascending
                      │  Click third time → unsorted
```

## Row Hover — Highlighted

```
         GET          /api/products                  ProductController::index         auth, cors, rate-limit
        ┌────────────────────────────────────────────────────────────────────────────────────────────────────────┐
        │GET          /api/products/{id}             ProductController::show          auth, cors               │
        └────────────────────────────────────────────────────────────────────────────────────────────────────────┘
         POST         /api/products                  ProductController::store         auth, cors, csrf
                                                     ▲
                                                     │  Hover row: faint background (#FAFAFA light / #1A1A1A dark)
                                                     │  Cursor: pointer
```

## Row Click — Inline Expand

Clicking a row expands a detail panel directly below the row. No page navigation.

```
         GET          /api/users                     UserController::index            auth, cors, rate-limit
         ┌──────────────────────────────────────────────────────────────────────────────────────────────────┐
         │                                                                                                  │
         │  GET /api/users/{id}                                                                     ✕      │
         │                                                                                                  │
         │  Handler        App\Controller\UserController::show                              ⎘ Copy          │
         │  Pattern        /api/users/{id:\d+}                                              ⎘ Copy          │
         │  Name           user.show                                                                        │
         │  Methods        GET, HEAD                                                                        │
         │                                                                                                  │
         │  Middleware Stack                                                                                 │
         │  1. Yiisoft\Yii\Middleware\CorsMiddleware                                                        │
         │  2. App\Middleware\AuthMiddleware                                                                 │
         │  3. Yiisoft\Router\Middleware\Router                                                              │
         │                                                                                                  │
         │  Parameters                                                                                      │
         │  id        \d+        (required)                                                                 │
         │                                                                                                  │
         └──────────────────────────────────────────────────────────────────────────────────────────────────┘
         POST         /api/users                     UserController::store            auth, cors, csrf
         PUT          /api/users/{id}                UserController::update           auth, cors, csrf
```

## Search — Inline Filter

Search filters rows in real time. The search box is above the table, not in a toolbar.

### Searching "order"

```
│         ┌────────────────────────────────────────────────────────────────────────────────┐                          │
│         │  order▎                                                               3 found │                          │
│         └────────────────────────────────────────────────────────────────────────────────┘                          │
│                                                                                                                    │
│         Method       Path                          Handler                          Middleware                      │
│         ─────────────────────────────────────────────────────────────────────────────────────────────────────       │
│                                                                                                                    │
│         GET          /api/«order»s                  «Order»Controller::index         auth, cors                     │
│         GET          /api/«order»s/{id}             «Order»Controller::show          auth, cors                     │
│         POST         /api/«order»s                  «Order»Controller::store         auth, cors, csrf               │
│                                                                                                                    │
```

## Column Resizing

Columns auto-size by default. User can drag column header edges to resize.
A double-click on the edge auto-fits to content width.

```
         Method ▾  │  Path                         │  Handler                         │  Middleware
                   ↕                                ↕                                  ↕
              drag handle                      drag handle                        drag handle
```

## Empty Table

```
│                                                                                                                    │
│         Method       Path                          Handler                          Middleware                      │
│         ─────────────────────────────────────────────────────────────────────────────────────────────────────       │
│                                                                                                                    │
│                                                                                                                    │
│                                      No routes match your search.                                                  │
│                                      Try a different term or clear the filter.                                     │
│                                                                                                                    │
│                                                                                                                    │
```

## Keyboard Navigation

```
  J / ↓         Move selection down
  K / ↑         Move selection up
  Enter         Expand selected row / navigate to detail
  Escape        Collapse expanded row
  /             Focus search box
  Ctrl+C        Copy selected row as text
```

## Method Badges

HTTP methods are rendered as subtle color-coded text (no background pill).

```
  GET        green text
  POST       blue text
  PUT        orange text
  PATCH      orange text (lighter)
  DELETE     red text
  HEAD       gray text
  OPTIONS    gray text
```
