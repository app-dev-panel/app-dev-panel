---
name: review-docs
description: Review and update project documentation after code changes. Ensures docs are LLM-optimized — no filler text, only dense factual content. Verifies file paths, commands, and interface signatures match actual code.
argument-hint: "[module or file to review]"
allowed-tools: Read, Write, Edit, Grep, Glob, Bash
---

# Documentation Reviewer

Review and update documentation for: $ARGUMENTS

If no argument — review all CLAUDE.md and docs/ across the project.

## Goal

Documentation is **optimized for LLM consumption**. Every sentence must carry information. Remove filler, marketing language, obvious statements.

## Review Checklist

### Structure
- CLAUDE.md starts with one-line module description.
- `##` headers. No `#` except title.
- Tables over prose for structured data (endpoints, files, deps, commands).
- Code blocks only for copy-paste content (commands, config, type defs).
- No "Introduction", "Overview", "Getting Started" fluff.

### Content Quality — Delete
- Obvious: "This module is responsible for...", "The purpose of this class is...", "This file contains..."
- Transitions: "Let's look at...", "As mentioned above...", "It's worth noting that..."
- Empty qualifiers: "very", "quite", "really", "basically", "simply"

### Content Quality — Keep
- Dependency rules, file paths, type signatures, architectural constraints, commands.
- "Why" explanations only when non-obvious.

### Accuracy
- Verify file paths exist on disk.
- Verify commands work.
- Verify interface signatures match code.
- Verify dependency claims match `composer.json` / `package.json`.

### Language
- All documentation must be in **English only**.
- If any doc contains non-English text — rewrite it in English.

### Format
- No emojis, badges, images, TOC.
- Lists use `-`.
- Inline code for file names, class names, commands.

## Files

```
CLAUDE.md
docs/*.md
libs/Kernel/CLAUDE.md, libs/Kernel/docs/*.md
libs/API/CLAUDE.md, libs/API/docs/*.md
libs/Cli/CLAUDE.md, libs/Cli/docs/*.md
libs/Adapter/Yiisoft/CLAUDE.md, libs/Adapter/Yiisoft/docs/*.md
libs/yii-dev-panel/CLAUDE.md, libs/yii-dev-panel/docs/*.md
```

## After Review

1. Edit files in place.
2. Show `git diff --stat`.
3. Summarize: removed (fluff), added (missing facts), corrected (inaccuracies).
