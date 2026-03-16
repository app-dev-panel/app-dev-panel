import react from '@vitejs/plugin-react';
import {resolve} from 'path';
import {defineConfig} from 'vitest/config';

export default defineConfig({
    plugins: [react()],
    resolve: {
        alias: {
            '@yiisoft/yii-dev-panel-sdk': resolve(__dirname, 'packages/yii-dev-panel-sdk/src'),
            '@yiisoft/yii-dev-panel': resolve(__dirname, 'packages/yii-dev-panel/src'),
            '@yiisoft/yii-dev-toolbar': resolve(__dirname, 'packages/yii-dev-toolbar/src'),
        },
    },
    test: {
        globals: true,
        environment: 'jsdom',
        setupFiles: ['./vitest.setup.ts'],
        include: ['packages/*/src/**/*.test.{ts,tsx}'],
        css: false,
        onConsoleLog: () => false,
    },
});
