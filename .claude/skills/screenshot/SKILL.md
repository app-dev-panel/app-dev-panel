---
name: screenshot
description: Take screenshots of frontend pages using Playwright. Captures full-page or element screenshots from running dev servers. Use for visual verification, debugging UI, and documenting features.
argument-hint: "[URL, page path, or description of what to capture]"
allowed-tools: Read, Write, Edit, Bash, Glob, Grep
---

# Screenshot Expert

Task: $ARGUMENTS

You take screenshots of web pages using Playwright. You can capture full pages, specific viewports, or individual elements.

## Prerequisites

### Required Software

| Tool | Purpose | Check |
|------|---------|-------|
| **Playwright** | Browser automation | `npx playwright --version` |
| **Chromium** (Playwright-managed) | Headless browser | Installed with Playwright |

Playwright is installed globally at `/opt/node22/lib/node_modules/playwright` with its own Chromium binary. No separate ChromeDriver needed.

### Important: Do NOT Use Selenium for Screenshots

Selenium requires ChromeDriver version to match Chromium exactly. In this environment, versions are mismatched (ChromeDriver 145 vs Chromium 141). **Always use Playwright** — it bundles its own browser and has no version conflicts.

## Quick Screenshot (CLI)

Simplest method — one command:

```bash
npx playwright screenshot --browser chromium \
  --wait-for-timeout 5000 \
  --full-page \
  --viewport-size "1920,1080" \
  http://localhost:5173/ /tmp/screenshot.png
```

Then view with the Read tool:
```
Read /tmp/screenshot.png
```

**Limitations**: CLI cannot wait for specific elements, interact with the page, or capture console errors.

## Full Screenshot (Node.js Script)

For React SPAs that need time to render:

```bash
NODE_PATH=/opt/node22/lib/node_modules node << 'SCRIPT'
const { chromium } = require('playwright');

(async () => {
  const browser = await chromium.launch({ headless: true });
  const page = await browser.newPage({ viewport: { width: 1920, height: 1080 } });

  // Optional: capture console output
  page.on('console', msg => console.log('CONSOLE:', msg.type(), msg.text()));
  page.on('pageerror', err => console.log('PAGE ERROR:', err.message));

  await page.goto('http://localhost:5173/', { waitUntil: 'networkidle' });
  await page.waitForTimeout(3000); // Wait for React to render

  await page.screenshot({ path: '/tmp/screenshot.png', fullPage: true });
  console.log('Screenshot saved');

  await browser.close();
})();
SCRIPT
```

**Key**: `NODE_PATH=/opt/node22/lib/node_modules` is required to find the global Playwright installation.

## Advanced Scenarios

### Wait for Specific Element

```javascript
await page.goto('http://localhost:5173/debug');
await page.waitForSelector('.MuiDataGrid-root', { timeout: 10000 });
await page.screenshot({ path: '/tmp/debug-grid.png' });
```

### Screenshot a Specific Element

```javascript
const element = await page.locator('.MuiCard-root').first();
await element.screenshot({ path: '/tmp/card.png' });
```

### Multiple Pages in One Session

```javascript
const pages = ['/', '/debug', '/inspector/config', '/inspector/routes'];
for (const path of pages) {
  await page.goto(`http://localhost:5173${path}`, { waitUntil: 'networkidle' });
  await page.waitForTimeout(2000);
  const name = path === '/' ? 'home' : path.replace(/\//g, '-').slice(1);
  await page.screenshot({ path: `/tmp/screenshot-${name}.png`, fullPage: true });
}
```

### Dark Mode Screenshot

```javascript
await page.emulateMedia({ colorScheme: 'dark' });
await page.goto('http://localhost:5173/', { waitUntil: 'networkidle' });
await page.waitForTimeout(3000);
await page.screenshot({ path: '/tmp/dark-mode.png', fullPage: true });
```

### Mobile Viewport

```javascript
const page = await browser.newPage({
  viewport: { width: 390, height: 844 },  // iPhone 14 Pro
  deviceScaleFactor: 3,
});
```

## Starting the Frontend Dev Server

Before taking screenshots, ensure the dev server is running:

```bash
# Start Vite dev server (background)
cd libs/frontend/packages/panel && npx vite --host 0.0.0.0 --port 5173 > /tmp/vite.log 2>&1 &

# Wait for server to be ready
sleep 5
curl -s -o /dev/null -w "%{http_code}" http://localhost:5173/
# Should return 200
```

Default URL: `http://localhost:5173/`

## Output

- Screenshots saved as PNG to `/tmp/` (or any writable path)
- View screenshots with the `Read` tool — Claude Code can analyze images
- For documentation, save to `docs/screenshots/` or `runtime/screenshots/`

## Troubleshooting

| Problem | Cause | Fix |
|---------|-------|-----|
| White/blank screenshot | React not rendered yet | Add `waitForTimeout(3000)` or `waitForSelector()` |
| `MODULE_NOT_FOUND: playwright` | Missing NODE_PATH | Use `NODE_PATH=/opt/node22/lib/node_modules` |
| `ERR_CONNECTION_REFUSED` in console | Backend not running | Expected if only frontend is started; UI still renders |
| Chromium version mismatch | Using Selenium + ChromeDriver | Switch to Playwright (no ChromeDriver needed) |
| `Session not created` | Selenium ChromeDriver/Chromium mismatch | Use Playwright instead |

## Workflow

1. Start frontend dev server (if not running)
2. Take screenshot with Playwright
3. Read the image with `Read` tool to view/analyze it
4. Optionally save to project directory for documentation
