---
title: Screenshots
description: "Take screenshots of the ADP frontend panel using Playwright for visual verification and documentation."
---

# Screenshots

ADP supports taking screenshots of the frontend panel using [Playwright](https://playwright.dev/). This is useful for visual verification during development, debugging UI issues, and generating documentation assets.

## Prerequisites

| Tool | Purpose | Install |
|------|---------|---------|
| **Playwright** | Browser automation | `npm install -g playwright` |
| **Chromium** | Headless browser | Bundled with Playwright (`npx playwright install chromium`) |

::: tip Why Playwright, not Selenium?
Selenium requires ChromeDriver to match the installed Chromium version exactly. Playwright bundles its own browser — no version conflicts. Use Selenium only for [E2E test suites](/guide/ci-and-tooling#frontend-checks).
:::

## Quick Screenshot (CLI)

One command, no script needed:

```bash
npx playwright screenshot \
  --browser chromium \
  --wait-for-timeout 5000 \
  --full-page \
  --viewport-size "1920,1080" \
  http://localhost:5173/ /tmp/screenshot.png
```

## Full Screenshot (Node.js)

For React SPAs, use a script to wait for rendering:

```js
const { chromium } = require('playwright');

(async () => {
    const browser = await chromium.launch({ headless: true });
    const page = await browser.newPage({
        viewport: { width: 1920, height: 1080 },
    });

    await page.goto('http://localhost:5173/', { waitUntil: 'networkidle' });
    await page.waitForTimeout(3000);

    await page.screenshot({ path: '/tmp/screenshot.png', fullPage: true });
    await browser.close();
})();
```

::: info NODE_PATH
If Playwright is installed globally (e.g. at `/opt/node22/lib/node_modules`), prefix the command:

```bash
NODE_PATH=/opt/node22/lib/node_modules node screenshot.js
```
:::

## Starting the Dev Server

Before taking screenshots, start the frontend:

```bash
cd libs/frontend/packages/panel
npx vite --host 0.0.0.0 --port 5173 &

# Wait for server
sleep 5
curl -s -o /dev/null -w "%{http_code}" http://localhost:5173/
# Should return 200
```

## Advanced Scenarios

### Wait for a Specific Element

```js
await page.goto('http://localhost:5173/debug');
await page.waitForSelector('.MuiDataGrid-root', { timeout: 10000 });
await page.screenshot({ path: '/tmp/debug-grid.png' });
```

### Screenshot a Single Element

```js
const card = await page.locator('.MuiCard-root').first();
await card.screenshot({ path: '/tmp/card.png' });
```

### Multiple Pages

```js
const pages = ['/', '/debug', '/inspector/config', '/inspector/routes'];
for (const path of pages) {
    await page.goto(`http://localhost:5173${path}`, { waitUntil: 'networkidle' });
    await page.waitForTimeout(2000);
    const name = path === '/' ? 'home' : path.replace(/\//g, '-').slice(1);
    await page.screenshot({ path: `/tmp/screenshot-${name}.png`, fullPage: true });
}
```

### Dark Mode

```js
await page.emulateMedia({ colorScheme: 'dark' });
await page.goto('http://localhost:5173/', { waitUntil: 'networkidle' });
await page.waitForTimeout(3000);
await page.screenshot({ path: '/tmp/dark-mode.png', fullPage: true });
```

### Mobile Viewport

```js
const page = await browser.newPage({
    viewport: { width: 390, height: 844 },
    deviceScaleFactor: 3,
});
```

## Claude Code Integration

Claude Code can take and analyze screenshots using the `/screenshot` skill:

```
/screenshot http://localhost:5173/
```

This launches Playwright, captures the page, and displays the image directly in the conversation. Claude Code can then analyze the UI, identify issues, or compare it against design specs.

### Workflow

1. Start the frontend dev server
2. Use `/screenshot [URL or path]` to capture
3. Claude Code reads and analyzes the image via the `Read` tool
4. Iterate: fix UI issues, re-screenshot, verify

## Troubleshooting

| Problem | Cause | Solution |
|---------|-------|----------|
| White/blank screenshot | React hasn't rendered | Add `waitForTimeout(3000)` or `waitForSelector()` |
| `MODULE_NOT_FOUND: playwright` | Playwright not in NODE_PATH | Set `NODE_PATH=/path/to/node_modules` |
| `ERR_CONNECTION_REFUSED` in console | Backend API not running | Expected — frontend UI still renders without backend |
| `Session not created` (Selenium) | ChromeDriver/Chromium version mismatch | Use Playwright instead |
