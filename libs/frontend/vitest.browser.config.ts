import {defineConfig} from 'vitest/config';

export default defineConfig({
    resolve: {
        alias: {
            '@app-dev-panel/panel/': new URL('./packages/panel/src/', import.meta.url).pathname,
            '@app-dev-panel/sdk/': new URL('./packages/sdk/src/', import.meta.url).pathname,
            '@app-dev-panel/toolbar/': new URL('./packages/toolbar/src/', import.meta.url).pathname,
            'react-redux': 'react-redux/dist/react-redux.js',
        },
    },
    test: {
        include: ['packages/*/src/**/*.browser.test.{ts,tsx}'],
        browser: {
            enabled: true,
            name: 'chromium',
            provider: 'playwright',
            headless: true,
            providerOptions: {launch: {executablePath: '/root/.cache/ms-playwright/chromium-1194/chrome-linux/chrome'}},
        },
        globals: true,
        testTimeout: 15000,
    },
});
