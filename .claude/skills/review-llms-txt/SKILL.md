---
name: review-llms-txt
description: "Review and regenerate llms.txt after documentation changes. Verifies vitepress-plugin-llms produces correct llms.txt, llms-full.txt, and per-page .md files."
argument-hint: "[optional: specific concern or check]"
allowed-tools: Read, Edit, Bash, Grep, Glob
---

# llms.txt Reviewer

Task: $ARGUMENTS

You maintain the llms.txt generation pipeline for the ADP documentation site.

## What llms.txt Is

Files auto-generated at VitePress build time into `website/.vitepress/dist/` by [`vitepress-plugin-llms`](https://github.com/okineadev/vitepress-plugin-llms):

| File | Content | Spec |
|------|---------|------|
| `llms.txt` | Concise TOC with `- [Title](URL)` links to per-page `.md` files | https://llmstxt.org/ |
| `llms-full.txt` | All English docs concatenated, cleaned via remark AST | Full context for large-window LLMs |
| `*.md` (per-page) | Clean markdown copy alongside each `.html` page | Individual page access |

## Source of Truth

```
website/.vitepress/config.ts              # Plugin config under vite.plugins
website/guide/*.md                        # English docs → content
website/api/*.md                          # English API docs → content
```

Russian pages (`website/ru/`) are excluded via `ignoreFiles: ['ru/**']`.

## Plugin Configuration

In `website/.vitepress/config.ts`:

```typescript
import llmstxt from 'vitepress-plugin-llms';

export default defineConfig({
    vite: {
        plugins: [
            llmstxt({
                ignoreFiles: ['ru/**'],
                domain: 'https://app-dev-panel.github.io',
            }),
        ],
    },
});
```

## Content Control Tags

Use in any markdown page:
- `<llm-only>content</llm-only>` — content appears only in LLM output, not on HTML site
- `<llm-exclude>content</llm-exclude>` — content appears on HTML site but excluded from LLM output

## Plugin Architecture

Operates as two Vite plugins (`enforce: 'pre'` + `enforce: 'post'`):

1. **Transform phase**: collects all `.md` files, strips `<llm-only>`/`<llm-exclude>` tags, injects hidden LLM hints on HTML pages
2. **generateBundle phase** (client build only): processes collected markdown through remark pipeline (strips frontmatter, HTML, Vue components), generates `llms.txt`, `llms-full.txt`, and per-page `.md` files

## Review Checklist

### 1. Build Succeeds
```bash
cd website && npm run build
```
Must print `Generated llms.txt` and `Generated llms-full.txt` with file counts.

### 2. Output Validation

**llms.txt format:**
- Starts with `# ADP`
- Has `>` blockquote description
- Has `## Table of Contents` with all page links
- Links use full site URL (`https://app-dev-panel.github.io/app-dev-panel/...`)
- Links point to `.md` files (not `.html`)
- No Russian pages included

**llms-full.txt content:**
- Contains all English page content
- No frontmatter remnants
- No `<script setup>` or Vue component tags
- No raw HTML tags (stripped by plugin)
- File count matches llms.txt link count

**Per-page .md files:**
- Exist alongside `.html` files in dist
- Clean markdown, no frontmatter

### 3. URL Correctness
Domain from plugin config + VitePress `base`. Currently: `https://app-dev-panel.github.io` + `/app-dev-panel/`.

### 4. Page Coverage
All English pages should be included (except index.md and blog pages which are excluded by default):
```bash
# Count source pages (excluding ru/ and index.md)
find website/guide website/api -name '*.md' -not -path '*/ru/*' | wc -l

# Compare with plugin output
grep -c '^\- \[' website/.vitepress/dist/llms.txt
```

## After Review

1. Run `cd website && npm run build` to verify
2. Check generated files: `cat website/.vitepress/dist/llms.txt` and `wc -l website/.vitepress/dist/llms-full.txt`
3. If content control is needed — use `<llm-only>` / `<llm-exclude>` tags in source markdown
4. If plugin config needs changes — edit `vite.plugins` in `website/.vitepress/config.ts`
5. Report: pages included, token count, any missing pages or stripping issues
