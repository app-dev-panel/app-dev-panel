---
title: Скриншоты
---

# Скриншоты

ADP поддерживает создание скриншотов фронтенд-панели с помощью [Playwright](https://playwright.dev/). Это полезно для визуальной верификации при разработке, отладки UI и создания документации.

## Требования

| Инструмент | Назначение | Установка |
|------------|-----------|-----------|
| **Playwright** | Автоматизация браузера | `npm install -g playwright` |
| **Chromium** | Headless-браузер | Поставляется с Playwright (`npx playwright install chromium`) |

::: tip Почему Playwright, а не Selenium?
Selenium требует точного совпадения версий ChromeDriver и Chromium. Playwright включает собственный браузер — никаких конфликтов версий. Selenium используется только для [E2E тестов](/ru/guide/ci-and-tooling#проверки-фронтенда).
:::

## Быстрый скриншот (CLI)

Одна команда, без скрипта:

```bash
npx playwright screenshot \
  --browser chromium \
  --wait-for-timeout 5000 \
  --full-page \
  --viewport-size "1920,1080" \
  http://localhost:5173/ /tmp/screenshot.png
```

## Полный скриншот (Node.js)

Для React SPA используйте скрипт с ожиданием рендеринга:

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
Если Playwright установлен глобально (например, в `/opt/node22/lib/node_modules`), добавьте префикс:

```bash
NODE_PATH=/opt/node22/lib/node_modules node screenshot.js
```
:::

## Запуск dev-сервера

Перед созданием скриншотов запустите фронтенд:

```bash
cd libs/frontend/packages/panel
npx vite --host 0.0.0.0 --port 5173 &

# Дождитесь запуска
sleep 5
curl -s -o /dev/null -w "%{http_code}" http://localhost:5173/
# Должен вернуть 200
```

## Продвинутые сценарии

### Ожидание конкретного элемента

```js
await page.goto('http://localhost:5173/debug');
await page.waitForSelector('.MuiDataGrid-root', { timeout: 10000 });
await page.screenshot({ path: '/tmp/debug-grid.png' });
```

### Скриншот одного элемента

```js
const card = await page.locator('.MuiCard-root').first();
await card.screenshot({ path: '/tmp/card.png' });
```

### Несколько страниц

```js
const pages = ['/', '/debug', '/inspector/config', '/inspector/routes'];
for (const path of pages) {
    await page.goto(`http://localhost:5173${path}`, { waitUntil: 'networkidle' });
    await page.waitForTimeout(2000);
    const name = path === '/' ? 'home' : path.replace(/\//g, '-').slice(1);
    await page.screenshot({ path: `/tmp/screenshot-${name}.png`, fullPage: true });
}
```

### Тёмная тема

```js
await page.emulateMedia({ colorScheme: 'dark' });
await page.goto('http://localhost:5173/', { waitUntil: 'networkidle' });
await page.waitForTimeout(3000);
await page.screenshot({ path: '/tmp/dark-mode.png', fullPage: true });
```

### Мобильный экран

```js
const page = await browser.newPage({
    viewport: { width: 390, height: 844 },
    deviceScaleFactor: 3,
});
```

## Интеграция с Claude Code

Claude Code может делать и анализировать скриншоты с помощью скилла `/screenshot`:

```
/screenshot http://localhost:5173/
```

Playwright захватит страницу, и изображение отобразится прямо в разговоре. Claude Code сможет проанализировать UI, найти проблемы или сравнить с макетом.

### Процесс работы

1. Запустите dev-сервер фронтенда
2. Используйте `/screenshot [URL или путь]` для захвата
3. Claude Code прочитает и проанализирует изображение через `Read`
4. Итерируйте: исправьте UI, сделайте новый скриншот, проверьте

## Решение проблем

| Проблема | Причина | Решение |
|----------|---------|---------|
| Белый/пустой скриншот | React ещё не отрендерился | Добавьте `waitForTimeout(3000)` или `waitForSelector()` |
| `MODULE_NOT_FOUND: playwright` | Playwright не в NODE_PATH | Установите `NODE_PATH=/путь/к/node_modules` |
| `ERR_CONNECTION_REFUSED` в консоли | Бэкенд API не запущен | Ожидаемо — UI рендерится и без бэкенда |
| `Session not created` (Selenium) | Несовпадение версий ChromeDriver/Chromium | Используйте Playwright |
