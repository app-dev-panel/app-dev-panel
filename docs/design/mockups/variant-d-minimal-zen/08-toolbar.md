# Variant D: Minimal Zen — Toolbar

## Concept

The embeddable toolbar is ultra-minimal: a single line at the bottom of the viewport. It shows
only the most critical information — request method, status, duration, memory, and query count.
Hovering over a segment reveals a tooltip with more detail. Clicking opens the full debug panel
in a new tab. The toolbar is nearly invisible until you need it.

## Default State — Single Line, Bottom Edge

```
┌────────────────────────────────────────────────────────────────────────────────────────────────────────────────────┐
│                                                                                                                    │
│                                                                                                                    │
│                                         User's application content                                                 │
│                                                                                                                    │
│                                                                                                                    │
│                                                                                                                    │
│                                                                                                                    │
│                                                                                                                    │
│                                                                                                                    │
│                                                                                                                    │
│                                                                                                                    │
│                                                                                                                    │
│                                                                                                                    │
│                                                                                                                    │
│                                                                                                                    │
│                                                                                                                    │
│                                                                                                                    │
│                                                                                                                    │
│                                                                                                                    │
│                                                                                                                    │
│                                                                                                                    │
├────────────────────────────────────────────────────────────────────────────────────────────────────────────────────┤
│ ◆  GET 200  143ms  4.2MB  4q  12 log  1 err                                                        ▴  ✕          │
└────────────────────────────────────────────────────────────────────────────────────────────────────────────────────┘
```

## Toolbar Anatomy (28px tall)

```
┌────────────────────────────────────────────────────────────────────────────────────────────────────────────────────┐
│ ◆  GET 200  143ms  4.2MB  4q  12 log  1 err                                                        ▴  ✕          │
│ ▲  ▲   ▲    ▲      ▲     ▲   ▲       ▲                                                             ▲  ▲          │
│ │  │   │    │      │     │   │       │                                                             │  │          │
│ │  │   │    │      │     │   │       Exception count (red if > 0)                                  │  Close      │
│ │  │   │    │      │     │   Log message count                                                    Expand         │
│ │  │   │    │      │     Query count                                                                              │
│ │  │   │    │      Peak memory                                                                                    │
│ │  │   │    Duration                                                                                               │
│ │  │   Status code (color-coded: green=2xx, orange=3xx, red=4xx/5xx)                                              │
│ │  HTTP method                                                                                                     │
│ ADP logo (click → open debug panel in new tab)                                                                     │
└────────────────────────────────────────────────────────────────────────────────────────────────────────────────────┘
```

## Segment Tooltips — Hover for Detail

Hovering over any segment in the toolbar shows a tooltip with more context.

### Hovering over "143ms"

```
│ ◆  GET 200  143ms  4.2MB  4q  12 log  1 err                                                        ▴  ✕          │
│              ▲                                                                                                     │
│              │                                                                                                     │
│        ┌─────────────────────────────┐                                                                             │
│        │  Duration: 143ms            │                                                                             │
│        │                             │                                                                             │
│        │  Bootstrap    12ms          │                                                                             │
│        │  Routing       8ms          │                                                                             │
│        │  Middleware    23ms          │                                                                             │
│        │  Controller   82ms          │                                                                             │
│        │  Response     18ms          │                                                                             │
│        └─────────────────────────────┘                                                                             │
```

### Hovering over "4q"

```
│ ◆  GET 200  143ms  4.2MB  4q  12 log  1 err                                                        ▴  ✕          │
│                            ▲                                                                                       │
│                            │                                                                                       │
│                      ┌──────────────────────────────────────────┐                                                  │
│                      │  4 Database Queries  (18.3ms)            │                                                  │
│                      │                                          │                                                  │
│                      │  SELECT … FROM users   8.2ms             │                                                  │
│                      │  SELECT COUNT(*)       5.1ms             │                                                  │
│                      │  SELECT … FROM roles   3.5ms             │                                                  │
│                      │  SELECT … FROM cache   1.5ms             │                                                  │
│                      └──────────────────────────────────────────┘                                                  │
```

### Hovering over "1 err"

```
│ ◆  GET 200  143ms  4.2MB  4q  12 log  1 err                                                        ▴  ✕          │
│                                         ▲                                                                          │
│                                         │                                                                          │
│                                ┌────────────────────────────────────────────────┐                                   │
│                                │  1 Exception                                  │                                   │
│                                │                                               │                                   │
│                                │  RuntimeException                             │                                   │
│                                │  Order total exceeds maximum allowed amount   │                                   │
│                                │  src/Service/OrderService.php:142             │                                   │
│                                │                                               │                                   │
│                                │  Click to open in debug panel →               │                                   │
│                                └────────────────────────────────────────────────┘                                   │
```

## Expanded State — Mini Panel

Clicking the expand button (▴) reveals a compact summary panel above the toolbar.

```
┌────────────────────────────────────────────────────────────────────────────────────────────────────────────────────┐
│                                                                                                                    │
│                                         User's application content                                                 │
│                                                                                                                    │
│                                                                                                                    │
│                                                                                                                    │
│                                                                                                                    │
│                                                                                                                    │
│                                                                                                                    │
│                                                                                                                    │
├────────────────────────────────────────────────────────────────────────────────────────────────────────────────────┤
│                                                                                                                    │
│  GET /api/users                                                                    Open in Debug Panel ▸           │
│                                                                                                                    │
│  ┌───────────────┐  ┌───────────────┐  ┌───────────────┐  ┌───────────────┐  ┌───────────────┐                     │
│  │  200 OK       │  │  143ms        │  │  4.2MB        │  │  4 queries    │  │  12 log msgs  │                     │
│  │  HTTP/1.1     │  │  ▁▂▃▅▇▅▃▂    │  │  peak memory  │  │  18.3ms SQL   │  │  3err 4warn   │                     │
│  └───────────────┘  └───────────────┘  └───────────────┘  └───────────────┘  └───────────────┘                     │
│                                                                                                                    │
│  Request Headers                                          Response Headers                                         │
│  Accept: application/json                                 Content-Type: application/json                            │
│  Host: localhost:8080                                     X-Debug-Id: abc123                                        │
│  Authorization: Bearer eyJ…                               X-Request-Duration: 143ms                                │
│                                                                                                                    │
├────────────────────────────────────────────────────────────────────────────────────────────────────────────────────┤
│ ◆  GET 200  143ms  4.2MB  4q  12 log  1 err                                                        ▾  ✕          │
└────────────────────────────────────────────────────────────────────────────────────────────────────────────────────┘
```

## Error State — Red Accent

When the request has a 4xx/5xx status or uncaught exceptions, the toolbar gets a subtle red tint.

```
┌────────────────────────────────────────────────────────────────────────────────────────────────────────────────────┐
│▌◆  POST 500  342ms  6.1MB  2q  8 log  1 err                                                        ▴  ✕         ▐│
└────────────────────────────────────────────────────────────────────────────────────────────────────────────────────┘
  ▲                                                                                                                ▲
  │  Left + right red accent borders                                                                               │
     Status "500" rendered in red
     "1 err" rendered in red
```

## Closed State — Icon Only

After clicking close (✕), the toolbar collapses to a single floating icon in the bottom-right.
Click to restore.

```
┌────────────────────────────────────────────────────────────────────────────────────────────────────────────────────┐
│                                                                                                                    │
│                                         User's application content                                                 │
│                                                                                                                    │
│                                                                                                                    │
│                                                                                                                    │
│                                                                                                                    │
│                                                                                                                    │
│                                                                                                                    │
│                                                                                                                    │
│                                                                                                                    │
│                                                                                                                    │
│                                                                                                                    │
│                                                                                                                    │
│                                                                                                             ┌───┐ │
│                                                                                                             │ ◆ │ │
│                                                                                                             └───┘ │
└────────────────────────────────────────────────────────────────────────────────────────────────────────────────────┘
```

## Visual Specifications

```
Toolbar bar:
  - Height: 28px (collapsed), ~200px (expanded)
  - Background: surface (#FAFAFA light / #141414 dark)
  - Border-top: 1px solid border color
  - Font: mono 12px
  - Z-index: 9999

Segments:
  - Separated by 16px horizontal spacing
  - No dividers between segments
  - Color-coded values (status, errors)
  - Hover: underline + tooltip after 300ms delay

Floating icon:
  - 32x32px, border-radius: 8px
  - Shadow: 0 2px 8px rgba(0,0,0,0.15)
  - Bottom-right corner, 16px margin
  - Opacity: 0.7, hover: 1.0

Tooltips:
  - Max-width: 320px
  - Background: #171717 (always dark)
  - Text: #EDEDED
  - Border-radius: 8px
  - Shadow: 0 4px 12px rgba(0,0,0,0.2)
  - Appear above the toolbar, arrow pointing down
```

## SSE Live Updates

The toolbar updates in real time when a new debug entry is received via SSE.
A brief flash animation on the toolbar indicates new data.

```
  New entry received:
  ┌────────────────────────────────────────────────────────────────────────────────────────────────────────────────┐
  │ ◆  POST 201  89ms  3.8MB  2q  5 log  0 err                                              ● NEW   ▴  ✕       │
  └────────────────────────────────────────────────────────────────────────────────────────────────────────────────┘
                                                                                             ▲
                                                                                             │
                                                                                        Green dot pulses
                                                                                        for 2 seconds,
                                                                                        then fades out
```
