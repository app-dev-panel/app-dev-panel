import react from '@vitejs/plugin-react';
import {resolve} from 'path';
import {defineConfig} from 'vitest/config';

export default defineConfig({
    plugins: [react()],
    resolve: {
        alias: {
            '@app-dev-panel/sdk': resolve(__dirname, 'packages/sdk/src'),
            '@app-dev-panel/panel': resolve(__dirname, 'packages/panel/src'),
            '@app-dev-panel/toolbar': resolve(__dirname, 'packages/toolbar/src'),
        },
    },
    test: {
        globals: true,
        environment: 'jsdom',
        setupFiles: ['./vitest.setup.ts'],
        include: ['packages/*/src/**/*.test.{ts,tsx}'],
        exclude: ['**/node_modules/**', '**/__e2e__/**', '**/*.browser.test.{ts,tsx}'],
        css: false,
        onConsoleLog: () => false,
    },
});
