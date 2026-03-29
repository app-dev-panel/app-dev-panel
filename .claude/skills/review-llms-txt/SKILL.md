---
name: review-llms-txt
description: "Review and regenerate llms.txt after documentation changes. Verifies the VitePress buildEnd hook produces correct llms.txt and llms-full.txt from sidebar config and markdown sources."
argument-hint: "[optional: specific concern or check]"
allowed-tools: Read, Edit, Bash, Grep, Glob
---

# llms.txt Reviewer

Task: $ARGUMENTS

You maintain the llms.txt generation pipeline for the ADP documentation site.

## What llms.txt Is

Two files auto-generated at VitePress build time into `website/.vitepress/dist/`:

| File | Content | Spec |
|------|---------|------|
| `llms.txt` | Concise TOC — H1, blockquote summary, H2 sections with `- [Title](URL)` links | https://llmstxt.org/ |
| `llms-full.txt` | All English docs concatenated, frontmatter/Vue stripped | Full context for large-window LLMs |

## Source of Truth

```
website/.vitepress/plugins/llms-txt.ts    # buildEnd hook (generator)
website/.vitepress/config.ts              # sidebar config → sections and links
website/guide/*.md                        # English docs → content
website/api/*.md                          # English API docs → content
```

Russian pages (`website/ru/`) are excluded — only English content goes into llms.txt.

## Generator Architecture

`generateLlmsTxt(siteConfig)` runs as VitePress `buildEnd` hook:

1. Reads English sidebar from `siteConfig.site.locales.root.themeConfig.sidebar`
2. Extracts section groups via `collectSections()` (H2 = group `text`, links = child items)
3. **llms.txt**: Writes H1 + blockquote + sections with `- [text](siteUrl + link)` entries
4. **llms-full.txt**: For each unique link, reads source `.md`, strips frontmatter/Vue/containers via `cleanMarkdown()`, concatenates with `---` separators

### cleanMarkdown() strips

- YAML frontmatter (via `gray-matter`)
- `<script setup>` blocks
- Self-closing Vue components (`<Component />`)
- Vue component blocks (`<Component>...</Component>`)
- VitePress containers (`:::`)
- Excess blank lines

## Review Checklist

### 1. Sidebar Completeness
Every page in `website/guide/` and `website/api/` must appear in the English sidebar in `config.ts`. Orphan pages (exist on disk but not in sidebar) will be missing from llms.txt.

```bash
# Find orphan pages
comm -23 \
  <(find website/guide website/api -name '*.md' -not -path '*/ru/*' | sed 's|website/||;s|\.md$||;s|/index$||' | sort) \
  <(grep -oP "link: '/[^']*'" website/.vitepress/config.ts | sed "s|link: '||;s|'||" | sort)
```

### 2. Build Succeeds
```bash
cd website && npm run build
```
Must print `[llms-txt] Generated llms.txt` and `[llms-txt] Generated llms-full.txt`.

### 3. Output Validation

**llms.txt format:**
- Starts with `# Application Development Panel (ADP)`
- Has `>` blockquote summary
- Has `## Section` headings matching sidebar groups
- Links use full site URL (`https://app-dev-panel.github.io/app-dev-panel/...`)
- No empty sections

**llms-full.txt content:**
- Starts with same H1 + blockquote
- Contains all page content (check page count matches sidebar link count)
- No frontmatter (`---\ntitle:...`) remnants
- No `<script setup>` or Vue component tags
- No `:::` container markers
- Pages separated by `---`

### 4. URL Correctness
Site URL is derived from `sitemap.hostname` in config. Currently: `https://app-dev-panel.github.io/app-dev-panel`

### 5. cleanMarkdown Quality
Check edge cases:
- Blog posts with `<BlogPost>` wrapper → must be stripped
- Code groups (`::: code-group`) → `:::` markers stripped, code blocks preserved
- Nested containers → all `:::` lines removed
- Vue `<script setup>` with multiline imports → fully removed

## After Review

1. If sidebar has new pages not reflected in llms.txt output — no code change needed, just rebuild
2. If `cleanMarkdown()` misses patterns — edit `website/.vitepress/plugins/llms-txt.ts`
3. Run `cd website && npm run build` to verify
4. Check generated files: `cat website/.vitepress/dist/llms.txt` and `wc -l website/.vitepress/dist/llms-full.txt`
5. Report: sections found, pages included, any orphans or stripping issues
