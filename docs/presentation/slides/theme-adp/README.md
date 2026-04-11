# slidev-theme-adp

Local Slidev theme that matches the ADP (Application Development Panel) web UI.

Dark-first, CSS-only. Inherits base Slidev layouts (`cover`, `center`, `default`)
and restyles their DOM via `styles/*.css`.

## Source of truth

All colors mirror `libs/frontend/packages/sdk/src/Component/Theme/tokens.ts`.
If that file changes, update `styles/vars.css` too.

## Token mapping

| ADP token (`darkSemanticTokens.palette`) | Slidev surface                              | CSS variable            |
| ---------------------------------------- | ------------------------------------------- | ----------------------- |
| `background.default` `#0F172A`           | Slide background                            | `--slidev-theme-background` |
| `background.paper` `#1E293B`             | Code-block / card / blockquote background  | `--slidev-code-background` |
| `text.primary` `#F1F5F9`                 | Body text, headings                         | `--slidev-theme-foreground` |
| `text.secondary` `#94A3B8`               | Muted text, subtitles, opacity-* overrides  | `--slidev-theme-secondary` |
| `primary.main` `#60A5FA`                 | H1 underline, bullets, links, code highlight | `--slidev-theme-primary`, `--adp-primary` |
| `divider` `#334155`                      | Card borders, HR, table borders             | `--adp-divider`         |
| `error.main` `#F87171`                   | Reserved for roast/error accents            | `--adp-error`           |
| `primitives.fontFamily` (Inter)          | Sans typography                             | `--slidev-font-sans`    |
| `primitives.fontFamilyMono` (JetBrains Mono) | Inline + block code                     | `--slidev-font-mono`    |
| `primitives.radiusBase` `8px`            | Code blocks, cards                          | `--adp-radius`          |

## Usage

```yaml
# slides.md frontmatter
theme: ./theme-adp
```

Shiki code theme is set in `slides.md` (`colorSchema: dark`,
`highlighter: shiki`, and the per-deck shiki `themes` override).
The recommended pairing is `vitesse-dark`.
