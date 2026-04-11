# slidev-theme-adp

Slidev theme matching the ADP (Application Development Panel) visual language.
Light mode by design: white paper cards on a gray-50 page with blue accents
and Inter typography.

The theme is a direct port of the real ADP UI design tokens that live in
`libs/frontend/packages/sdk/src/Component/Theme/tokens.ts`. When the frontend
tokens change, update `styles/vars.css` alongside them.

## File layout

```
theme-adp/
├── package.json            # slidev.colorSchema: light, shiki defaults
├── README.md               # this file
├── styles/
│   ├── index.ts            # auto-loaded entrypoint (import order matters)
│   ├── vars.css            # ADP primitives + Slidev CSS variables
│   ├── container.css       # html / body / .slidev-slide-container / -content
│   ├── layout.css          # typography, headings, lists, cover/center tweaks
│   ├── code.css            # code-block card chrome, inline code, line highlight
│   └── components.css      # grids as cards, tables, images, kbd
└── layouts/
    └── cover.vue           # cover override (neutralises text-white frontmatter)
```

Slidev auto-loads `styles/index.ts` and every file under `layouts/`. Theme
layouts override the built-in layout with the same name; here we only
override `cover` because `pages/01-title.md` ships a `class: text-white`
that would make the title invisible on our light surface.

## Token map

ADP token (tokens.ts)        | CSS variable          | Value
----------------------------|-----------------------|----------
background.default (gray50) | `--adp-bg-default`    | `#f3f4f6`
background.paper (white)    | `--adp-bg-paper`      | `#ffffff`
text.primary (gray900)      | `--adp-text-primary`  | `#1a1a1a`
text.secondary (gray600)    | `--adp-text-secondary`| `#666666`
text.disabled (gray400)     | `--adp-text-disabled` | `#999999`
primary.main (blue500)      | `--adp-primary`       | `#2563eb`
primary.dark (blue700)      | `--adp-primary-dark`  | `#1d4ed8`
primary.light (blue50)      | `--adp-primary-light` | `#eff6ff`
divider (gray200)           | `--adp-divider`       | `#e5e5e5`
error.main (red600)         | `--adp-error`         | `#dc2626`
shape.borderRadius          | `--adp-radius`        | `8px`
shadows.sm                  | `--adp-shadow-sm`     | `0 1px 3px rgba(0,0,0,0.06), 0 1px 2px rgba(0,0,0,0.04)`
shadows.md                  | `--adp-shadow-md`     | `0 4px 12px rgba(0,0,0,0.08)`
navItem.activeBarWidth      | `--adp-accent-bar-width` | `3px`
primitives.fontFamily       | `--adp-font-sans`     | `Inter, ...`
primitives.fontFamilyMono   | `--adp-font-mono`     | `JetBrains Mono, ...`

## Slidev variables

We override the following built-in Slidev variables in `vars.css`:

Slidev variable                       | Mapped to
--------------------------------------|-------------------
`--slidev-slide-container-background` | `--adp-bg-default`  (fixes black letterbox)
`--slidev-theme-primary`              | `--adp-primary`
`--slidev-code-background`            | `--adp-bg-paper`
`--slidev-code-foreground`            | `--adp-text-primary`
`--slidev-code-font-family`           | `--adp-font-mono`
`--slidev-code-padding`               | `1.25rem 1.5rem`
`--slidev-code-line-height`           | `1.55`

## Typography scale

- h1: `2.75rem` (~44px), 700, `-0.02em` tracking, with a 3px primary accent bar
- h2: `2rem` (~32px), 600
- h3: `1.625rem` (~26px), 600
- h4: `1.375rem` (~22px), 600, secondary color
- body: `1.375rem` (~22px), 400, `1.6` leading
- cover h1: `5.5rem` (~88px), 800, painted in `--adp-primary`

Fonts loaded via Slidev frontmatter (`sans: Inter`, `mono: JetBrains Mono`).

## Code highlighting

The deck uses Shiki with `vitesse-light` theme (set in `slides.md` frontmatter).
Code blocks render as white ADP cards: `bg-paper` background, `1px` divider
border, `8px` radius, `shadow-sm`. Line highlighting (`{1-4|6-14|10|12|all}`)
paints the active line with `--adp-primary-light` plus an inset 2px
`--adp-primary` left bar, mirroring the ADP "active row" treatment.
