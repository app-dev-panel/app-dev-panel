# ADP Design Guide

Brand assets, design tokens, and visual guidelines for Application Development Panel.

## Logo / Mascot

The ADP mascot is a **yellow rubber duck** — a reference to [rubber duck debugging](https://en.wikipedia.org/wiki/Rubber_duck_debugging).

| File | Size | Format | Usage |
|------|------|--------|-------|
| `duck.svg` | 128×128 | SVG | Primary logo, scalable |
| `favicon.ico` | Multi | ICO | Browser favicon |
| `favicon-16x16.png` | 16×16 | PNG | Small browser tab icon |
| `favicon-32x32.png` | 32×32 | PNG | Standard favicon |
| `apple-touch-icon.png` | 180×180 | PNG | iOS home screen |
| `android-chrome-192x192.png` | 192×192 | PNG | Android home screen |
| `android-chrome-512x512.png` | 512×512 | PNG | Android splash screen |
| `mstile-150x150.png` | 150×150 | PNG | Windows tile |
| `logo192.png` | 192×192 | PNG | PWA manifest |
| `logo512.png` | 512×512 | PNG | PWA manifest |

### Duck Colors

| Part | Color | Hex |
|------|-------|-----|
| Body | Warm yellow | `#FCD34D` |
| Wing accent | Amber | `#FBBF24` |
| Beak | Orange | `#FB923C` |
| Eye | Dark brown | `#292524` |
| Eye highlight | White | `#FFFFFF` |
| Water ripple | Light blue | `#93C5FD` |

## Color Palette

### Primary

| Token | Light | Dark | Usage |
|-------|-------|------|-------|
| `primary.main` | `#2563EB` | `#60A5FA` | Buttons, links, active states |
| `primary.light` | `#EFF6FF` | `#1E3A5F` | Hover backgrounds, badges |
| `primary.dark` | `#1D4ED8` | `#3B82F6` | Pressed states |

### Status

| Token | Light | Dark | Usage |
|-------|-------|------|-------|
| `success.main` | `#16A34A` | `#4ADE80` | Successful operations, 2xx |
| `success.light` | `#DCFCE7` | `#14532D` | Success backgrounds |
| `warning.main` | `#D97706` | `#FBBF24` | Warnings, slow queries |
| `warning.light` | `#FEF3C7` | `#713F12` | Warning backgrounds |
| `error.main` | `#DC2626` | `#F87171` | Errors, exceptions, 5xx |
| `error.light` | `#FEE2E2` | `#7F1D1D` | Error backgrounds |

### Neutrals

| Token | Light | Dark | Usage |
|-------|-------|------|-------|
| `background.default` | `#F3F4F6` | `#0F172A` | Page background |
| `background.paper` | `#FFFFFF` | `#1E293B` | Cards, panels |
| `text.primary` | `#1A1A1A` | `#F1F5F9` | Headings, body text |
| `text.secondary` | `#666666` | `#94A3B8` | Labels, captions |
| `text.disabled` | `#999999` | `#64748B` | Disabled text |
| `divider` | `#E5E5E5` | `#334155` | Borders, separators |

### Grays

| Name | Hex | Usage |
|------|-----|-------|
| Gray 50 | `#F3F4F6` | Default background (light) |
| Gray 100 | `#F5F5F5` | Subtle background |
| Gray 200 | `#E5E5E5` | Borders, dividers |
| Gray 300 | `#F0F0F0` | Input backgrounds |
| Gray 400 | `#999999` | Disabled text |
| Gray 600 | `#666666` | Secondary text |
| Gray 900 | `#1A1A1A` | Primary text |

## Typography

| Property | Value |
|----------|-------|
| Font family (UI) | `Inter`, sans-serif |
| Font family (code) | `JetBrains Mono`, monospace |

### Type Scale

| Style | Size | Weight | Line Height | Usage |
|-------|------|--------|-------------|-------|
| h4 | 18px | 600 | 1.4 | Page titles, section headers |
| body1 | 14px | 400 | 1.5 | Primary body text |
| body2 | 13px | 400 | 1.5 | Secondary body text |
| caption | 11px | 600 | 1.3 | Badges, labels |
| overline | 12px | 600 | 1.5 | Section titles (uppercase, 0.6px spacing) |

## Spacing

Base unit: **8px**

| Scale | Value | Usage |
|-------|-------|-------|
| 1× | 8px | Inline spacing, small gaps |
| 2× | 16px | Main content gap, card padding |
| 3× | 24px | Section spacing |
| 4× | 32px | Large section gaps |

## Border Radius

| Token | Value | Usage |
|-------|-------|-------|
| Base | 8px | Cards, inputs, buttons |
| Large (2×) | 16px | Sidebar, content panel |

## Shadows

| Name | Value | Usage |
|------|-------|-------|
| Small | `0 1px 3px rgba(0,0,0,0.06), 0 1px 2px rgba(0,0,0,0.04)` | Cards, hover states |
| Medium | `0 4px 12px rgba(0,0,0,0.08)` | Dropdowns, popovers |
| Large | `0 8px 24px rgba(0,0,0,0.12), 0 2px 6px rgba(0,0,0,0.08)` | Modals, tooltips |

## Layout

| Component | Value |
|-----------|-------|
| Top bar height | 48px |
| Sidebar width | 200px |
| Nav item height | 38px |
| Active bar width | 3px |
| Main gap | 16px |
| Main max width | 1160px |

## Source Files

| File | Description |
|------|-------------|
| `libs/frontend/packages/sdk/src/Component/Theme/tokens.ts` | Design tokens (primitives, semantic, dark, component) |
| `libs/frontend/packages/sdk/src/Component/Theme/DefaultTheme.tsx` | MUI theme factory (`createAppTheme`) |
| `libs/frontend/packages/panel/public/` | All icon assets (source of truth) |
| `website/.vitepress/theme/style.css` | VitePress theme CSS (mirrors panel tokens) |
