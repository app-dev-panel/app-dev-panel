# 8. Internal Improvements & Recommendations

## 8.1 Critical Improvements (Impact on Adoption)

| # | Improvement | Module | Marketing Impact | Priority |
|---|-----------|--------|-----------------|----------|
| 1 | **Getting Started docs** per framework | Docs | Without this, developers leave within 2 minutes | P0 |
| 2 | **Interactive demo/playground** (public) | Infrastructure | "Try before install" — key to adoption | P0 |
| 3 | **GIF/video demo on GitHub README** | Docs | GitHub README is the main entry point | P0 |
| 4 | **Single composer require** for installation | Adapters | Complex installation = lost users | P0 |
| 5 | **Zero-config defaults** | Adapters | "Install and it works" without config files | P0 |

## 8.2 UX Improvements (Impact on Retention)

| # | Improvement | Module | Description |
|---|-----------|--------|------------|
| 1 | **Onboarding wizard** | Frontend | Show key features on first launch |
| 2 | **Code splitting / lazy loading** | Frontend | Speed up initial SPA load |
| 3 | **List virtualization** (react-window) | Frontend | No lag on 1000+ records |
| 4 | **Keyboard shortcuts guide** | Frontend | Overlay with hotkeys (Shift+?) |
| 5 | **Icon tooltips** | Frontend | Not all icons are self-explanatory |
| 6 | **Empty states with hints** | Frontend | "No SQL queries? Maybe the ORM isn't connected to ADP" |
| 7 | **Performance budget** | Frontend | Target LCP < 2s, TTI < 3s |

## 8.3 Technical Improvements (Impact on Quality)

| # | Improvement | Module | Description |
|---|-----------|--------|------------|
| 1 | **Complete security hardening** | API | Auth, CSRF, postMessage validation |
| 2 | **Plugin/Extension API** | Kernel | Allow the community to create collectors |
| 3 | **Laravel adapter E2E tests** | Testing | Laravel adapter must be production-ready |
| 4 | **Exponential backoff for SSE** | Frontend | Graceful reconnection |
| 5 | **Error boundaries per module** | Frontend | An error in one panel doesn't break the entire UI |
| 6 | **Accessibility audit** | Frontend | WCAG 2.1 AA compliance |
| 7 | **Bundle size monitoring** | Frontend | Alerting on bundle growth |

## 8.4 Strategic Initiatives

| # | Initiative | Description | Potential |
|---|-----------|------------|----------|
| 1 | **VS Code Extension** | ADP panel right in VS Code | Massive reach, convenience |
| 2 | **JetBrains Plugin** | ADP panel in PhpStorm | Target PHP audience |
| 3 | **AI Log Analysis** | GPT/Claude analyzes exceptions and suggests fixes | 2025-2026 trend, wow-factor |
| 4 | **Docker one-liner** | `docker run adp` — standalone ADP server | Instant start |
| 5 | **Cloud/SaaS version** | Hosted ADP for teams | Monetization, enterprise |
| 6 | **WordPress adapter** | Huge WordPress community | Audience scaling |
| 7 | **Node.js / Python native adapters** | Not just ingestion API, but full adapters | Expansion beyond PHP |

## 8.5 Rebranding Completion (Residual Yii Traces)

**Context**: ADP originated from Yii Debug, but rebranding is done — org `app-dev-panel`, packages `app-dev-panel/*`, the kernel doesn't depend on Yii. Association is minimal, but some remnants should be cleaned up.

**What remains:**
- Documentation (getting-started.md) — Yii is listed first (historical ordering)
- Frontend README — contains "Maintained by Yii Software", links to Yii Forum
- Git history — origin from yiisoft/yii-debug is visible (inevitable, but unimportant)

**Recommendations:**
1. List Laravel and Symfony first in documentation and marketing (by community size)
2. Update frontend README — remove Yii-specific links
3. GitHub topics: `laravel`, `symfony`, `php-debugging`, `php-profiler`
4. Packagist keywords: `debug-panel`, `laravel-debug`, `symfony-debug`, `php-profiler`

---

## Actions

### P0 (immediately)
- [ ] Write Getting Started docs for Laravel
- [ ] Write Getting Started docs for Symfony
- [ ] Record GIF demo and add to GitHub README
- [ ] Deploy a public interactive playground
- [ ] Ensure single `composer require` installation for each framework

### P1 (next sprint)
- [ ] Implement code splitting / lazy loading on the frontend
- [ ] Add onboarding wizard on first launch
- [ ] Implement react-window for list virtualization
- [ ] Complete security hardening (Auth, CSRF)
- [ ] Add E2E fixtures for the Laravel adapter

### P2 (backlog)
- [ ] Start VS Code extension development
- [ ] Research AI Log Analysis (GPT/Claude integration)
- [ ] Prepare `docker run adp` — standalone Docker image
- [ ] Conduct accessibility audit (WCAG 2.1 AA)
- [ ] Update frontend README — remove Yii-specific links
- [ ] Reorder frameworks in documentation: Laravel → Symfony → Yii 3 → Yii 2
