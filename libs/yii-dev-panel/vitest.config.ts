import {defineConfig} from 'vitest/config';
import tsconfigPaths from 'vite-tsconfig-paths';

export default defineConfig({
    plugins: [tsconfigPaths()],
    test: {
        environment: 'jsdom',
        globals: true,
        include: ['packages/*/src/**/*.test.{ts,tsx}'],
        coverage: {
            provider: 'v8',
            include: ['packages/*/src/**/*.{ts,tsx}'],
            exclude: ['**/*.test.*', '**/*.d.ts', '**/index.ts'],
        },
    },
});
