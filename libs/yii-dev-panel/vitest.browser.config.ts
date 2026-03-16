import {defineConfig} from 'vitest/config';

export default defineConfig({
    resolve: {
        alias: {
            '@yiisoft/yii-dev-panel/': new URL('./packages/yii-dev-panel/src/', import.meta.url).pathname,
            '@yiisoft/yii-dev-panel-sdk/': new URL('./packages/yii-dev-panel-sdk/src/', import.meta.url).pathname,
            '@yiisoft/yii-dev-toolbar/': new URL('./packages/yii-dev-toolbar/src/', import.meta.url).pathname,
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
