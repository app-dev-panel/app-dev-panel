---
description: "ADP — framework-agnostic debugging and development panel for PHP. Works with Symfony, Laravel, Yii, and any PSR app."
layout: home
hero:
  name: ADP
  text: Application Development Panel
  tagline: Framework-agnostic debugging and development panel for PHP applications
  image:
    src: /duck.svg
    alt: ADP Duck
  actions:
    - theme: brand
      text: Get Started
      link: /guide/getting-started
    - theme: alt
      text: Live Demo
      link: /demo/
    - theme: alt
      text: View on GitHub
      link: https://github.com/app-dev-panel/app-dev-panel

features:
  - icon: 🔍
    title: Universal Debugging
    details: Inspect logs, events, requests, exceptions, database queries, and more — regardless of which PHP framework you use.
  - icon: 🔌
    title: Framework Adapters
    details: Out-of-the-box support for Symfony, Yii 2, Yii 3, and Laravel. Easy to add your own adapter.
  - icon: 📡
    title: Real-time SSE
    details: Live updates via Server-Sent Events. See debug data appear in real-time as your application runs.
  - icon: 🤖
    title: MCP Server
    details: AI assistant integration via Model Context Protocol. Let your AI tools access debug data directly.
  - icon: 🎨
    title: Modern UI
    details: Beautiful React SPA with dark mode, search, filters, and a responsive design built with Material-UI.
  - icon: ⚡
    title: Zero Config
    details: Works with PSR standards. No custom configuration needed — just install the adapter and start debugging.
---

<div class="home-sponsors">
  <h2 class="home-sponsors-title">Sponsored by</h2>
  <div class="home-sponsors-grid">
    <p class="home-sponsors-empty">Your logo here</p>
  </div>
  <div class="home-sponsors-cta">
    <a href="/app-dev-panel/sponsor">Become a Sponsor</a>
    <span class="home-sponsors-sep">|</span>
    <a href="https://github.com/app-dev-panel/app-dev-panel" target="_blank" rel="noopener">Star on GitHub</a>
  </div>
</div>

<style>
.home-sponsors {
    max-width: 800px;
    margin: 80px auto 0;
    text-align: center;
    padding: 0 24px;
}
.home-sponsors-title {
    font-size: 24px;
    font-weight: 700;
    letter-spacing: 0.02em;
    text-transform: uppercase;
    color: var(--vp-c-text-2);
    margin-bottom: 24px;
}
.home-sponsors-grid {
    display: flex;
    flex-wrap: wrap;
    justify-content: center;
    gap: 24px;
    margin-bottom: 24px;
}
.home-sponsors-empty {
    padding: 32px 48px;
    border: 2px dashed var(--vp-c-divider);
    border-radius: var(--adp-radius-lg);
    color: var(--vp-c-text-3);
    font-style: italic;
    min-width: 280px;
}
.home-sponsors-cta {
    font-size: 14px;
    color: var(--vp-c-text-3);
}
.home-sponsors-cta a {
    color: var(--vp-c-brand-1);
    text-decoration: none;
    font-weight: 500;
}
.home-sponsors-cta a:hover {
    text-decoration: underline;
}
.home-sponsors-sep {
    margin: 0 8px;
    color: var(--vp-c-divider);
}
</style>
