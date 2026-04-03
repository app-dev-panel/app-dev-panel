import {playwright} from '@vitest/browser-playwright';
import {defineConfig} from 'vitest/config';

export default defineConfig({
    resolve: {
        alias: {
            '@app-dev-panel/panel/': new URL('./packages/panel/src/', import.meta.url).pathname,
            '@app-dev-panel/sdk/': new URL('./packages/sdk/src/', import.meta.url).pathname,
            '@app-dev-panel/toolbar/': new URL('./packages/toolbar/src/', import.meta.url).pathname,
        },
    },
    test: {
        include: ['packages/*/src/**/*.browser.test.{ts,tsx}'],
        browser: {
            enabled: true,
            headless: true,
            provider: playwright({
                launch: {
                    executablePath: process.env.CHROMIUM_PATH || undefined,
                    args: ['--no-sandbox', '--disable-gpu', '--disable-dev-shm-usage'],
                },
            }),
            instances: [{browser: 'chromium'}],
        },
        globals: true,
        testTimeout: 15000,
    },
});
