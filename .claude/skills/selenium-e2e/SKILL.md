---
name: selenium-e2e
description: Write, run, and debug E2E browser tests using Selenium (ChromeDriver). Covers both frontend Vitest browser tests and PHP PHPUnit E2E tests. Use for any browser testing task.
argument-hint: "[test file, page, or feature to test]"
allowed-tools: Read, Write, Edit, Grep, Glob, Bash, Agent
---

# Selenium E2E Test Expert

Task: $ARGUMENTS

You are an E2E browser testing expert. You write and maintain browser tests for ADP using Selenium WebDriver (ChromeDriver) — both frontend (Vitest + WebDriverIO) and PHP (PHPUnit + php-webdriver).

## Architecture

ADP has two E2E test layers:

### 1. Frontend Browser Tests (Vitest + WebDriverIO)

**Location**: `libs/frontend/packages/panel/src/__e2e__/`
**Config**: `libs/frontend/vitest.browser.config.ts`
**Run**: `make test-frontend-e2e` or `cd libs/frontend && npm run test:e2e`

Uses `@vitest/browser` with WebDriverIO provider (Selenium protocol) and ChromeDriver.

**File structure**:
```
packages/panel/src/__e2e__/
├── renderApp.tsx              # App renderer helper (creates React app with test config)
├── setup.ts                   # MSW (Mock Service Worker) setup for API mocking
├── mocks/
│   └── handlers.ts            # MSW request handlers (mock API responses)
├── navigation.browser.test.tsx
├── debug.browser.test.tsx
├── inspector.browser.test.tsx
└── api-interaction.browser.test.tsx
```

**Test file conventions**:
- Filename: `*.browser.test.tsx` (must match vitest browser config include pattern)
- Import `page` from `@vitest/browser/context` for element queries
- Import `renderApp` from `./renderApp` to mount the React app at a given path
- Import `./setup` to activate MSW mock handlers
- Use `page.getByText()`, `page.getByRole()` for element queries
- Use `await expect.element(...).toBeVisible()` for assertions
- Use `document.body.textContent` for full-page text checks
- Add `await new Promise(r => setTimeout(r, N))` for RTK Query fetch delays

**Example test**:
```tsx
import {page} from '@vitest/browser/context';
import {describe, expect, it} from 'vitest';
import {renderApp} from './renderApp';
import './setup';

describe('My Feature', () => {
    it('renders the page', async () => {
        renderApp('/my-page');
        await expect.element(page.getByText('Expected Title')).toBeVisible();
    });

    it('loads data from API', async () => {
        renderApp('/my-page');
        await expect.element(page.getByText('Expected Title')).toBeVisible();
        await new Promise(r => setTimeout(r, 1000)); // wait for RTK Query
        const bodyText = document.body.textContent || '';
        expect(bodyText).toContain('expected data');
    });
});
```

**Adding mock API responses**: Edit `mocks/handlers.ts`. Use `http.get()` / `http.post()` from `msw`. Base URL is `http://127.0.0.1:8080`.

### 2. PHP E2E Tests (PHPUnit + php-webdriver)

**Location**: `tests/E2E/`
**Base class**: `tests/E2E/BrowserTestCase.php`
**Run**: `FRONTEND_URL=http://localhost:3000 php vendor/bin/phpunit --testsuite E2E`

Uses `php-webdriver/webdriver` (Facebook WebDriver) with ChromeDriver.

**File structure**:
```
tests/E2E/
├── BrowserTestCase.php        # Abstract base (auto-starts ChromeDriver, creates driver)
├── NavigationTest.php
├── DebugPageTest.php
├── InspectorPageTest.php
└── ApiInteractionTest.php
```

**BrowserTestCase provides**:
- `navigate(string $path)` — navigate to frontend page
- `waitForElement(string $cssSelector, int $timeout = 10)` — wait for DOM element
- `waitForVisible(string $cssSelector, int $timeout = 10)` — wait for visible element
- `waitForText(string $text, int $timeout = 10)` — wait for text on page
- `waitForAppLoad(int $timeout = 30)` — wait for React to render content in `#root`
- `clickButton(string $text)` / `clickLink(string $text)` — click by visible text
- `getRenderedBodyText()` / `getRenderedBodyHtml()` — get React-rendered content
- `elementExists(string $cssSelector)` — check element presence
- `getText(string $cssSelector)` — get element text
- `getCurrentPath()` — get current URL path
- `takeScreenshot(string $name)` — save screenshot to `runtime/screenshots/`
- `countElements(string $cssSelector)` — count matching elements
- `executeJs(string $script)` — run JavaScript in browser
- `getConsoleErrors()` — get browser console SEVERE errors

**Environment variables (PHP E2E)**:
- `FRONTEND_URL` — frontend dev server URL (default: `http://localhost:3000`)
- `CHROME_BINARY` — path to Chromium binary (auto-detected if not set)
- `CHROMEDRIVER_PATH` — path to ChromeDriver binary (uses `which chromedriver` if not set)
- `CHROMEDRIVER_PORT` — ChromeDriver port (default: `9516`)

**Environment variables (Frontend E2E — WebDriverIO)**:
- `CHROMEDRIVER_PATH` — path to ChromeDriver binary (WebDriverIO uses this to skip auto-download)

**Example PHP E2E test**:
```php
<?php

declare(strict_types=1);

namespace AppDevPanel\Tests\E2E;

final class MyPageTest extends BrowserTestCase
{
    public function testPageLoads(): void
    {
        $this->navigate('/my-page');
        $this->waitForAppLoad();
        $this->waitForVisible('.my-component');
        $bodyText = $this->getRenderedBodyText();
        self::assertStringContainsString('Expected Content', $bodyText);
    }
}
```

## Prerequisites

Both test layers require:
- **Chromium** (`/usr/bin/chromium` or `CHROME_BINARY` env)
- **ChromeDriver** (`chromedriver` in PATH or `CHROMEDRIVER_PATH` env)
- Chromium and ChromeDriver versions must match (same major version)

The `setup-env.sh` script installs both automatically.

## Before Writing Tests

1. Read existing tests in the same directory — match style exactly.
2. For frontend tests: check `mocks/handlers.ts` to see available mock endpoints. Add new handlers if your test needs data from an API endpoint not yet mocked.
3. For PHP tests: ensure a frontend dev server is running at `FRONTEND_URL`.

## After Writing Tests

1. **Frontend**: Run `cd libs/frontend && npm run test:e2e` — all tests must pass.
2. **PHP**: Run `FRONTEND_URL=http://localhost:3000 php vendor/bin/phpunit --testsuite E2E --filter=ClassName`.
3. Run code quality checks:
   - Frontend: `cd libs/frontend && npm run format && npm run lint:fix`
   - PHP: `vendor/bin/mago fmt && vendor/bin/mago lint && vendor/bin/mago analyze`

## Debugging Failed Tests

1. **Frontend**: Add `console.log()` in test, check Vitest output. Use `document.body.innerHTML` to inspect rendered HTML.
2. **PHP**: Use `$this->takeScreenshot('debug-name')` to capture browser state. Screenshots saved to `runtime/screenshots/`.
3. **ChromeDriver issues**: Check `chromedriver --version` matches `chromium --version` major version. If mismatch, reinstall ChromeDriver.
4. **Timeout issues**: Increase `testTimeout` in vitest config or `$timeoutSeconds` in PHP wait methods. React rendering can be slow with MSW.

## Standalone Screenshots (Playwright)

For **ad-hoc screenshots** outside of test suites (visual verification, debugging, documentation), use the `/screenshot` skill instead. It uses Playwright which bundles its own Chromium and avoids ChromeDriver version mismatch issues.

Selenium `takeScreenshot()` in PHP E2E tests remains the correct approach for capturing browser state **during test execution**.

## Anti-Patterns

- Don't use `page.locator()` — use `page.getByText()`, `page.getByRole()` for semantic queries.
- Don't hardcode waits without assertions — always wait for a specific condition.
- Don't skip MSW setup import in frontend tests — API calls will fail.
- Don't use `getPageSource()` in PHP tests — use `getRenderedBodyText()` for React content.
- Don't test implementation details (CSS classes, DOM structure) — test user-visible behavior.
