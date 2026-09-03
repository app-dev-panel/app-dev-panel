import react from '@vitejs/plugin-react';
import {resolve} from 'path';
import {defineConfig} from 'vitest/config';

const exclude = ['**/node_modules/**', '**/__e2e__/**', '**/*.browser.test.{ts,tsx}'];

/**
 * `.test.ts` files that need a DOM (`document`, `window`, `navigator`,
 * `localStorage`, `EventSource`, …) even though they are not React component
 * tests. Every other `.test.ts` file runs in the plain `node` environment,
 * which skips the jsdom bootstrap (~1s per file). If a new `.test.ts` fails
 * with "document is not defined", add it here or rename it to `.test.tsx`.
 */
const domDependentTsTests = [
    'packages/panel/src/Application/Component/sseUpdatesHandler.test.ts',
    'packages/panel/src/Module/Project/projectSyncMiddleware.test.ts',
    'packages/panel/src/index.html.test.ts',
    'packages/sdk/src/API/Application/api.test.ts',
    'packages/sdk/src/Component/ServerSentEventsObserver.test.ts',
    'packages/sdk/src/Helper/IFrameWrapper.test.ts',
    'packages/sdk/src/Helper/clipboard.test.ts',
    'packages/sdk/src/Helper/collectChatContext.test.ts',
    'packages/sdk/src/Helper/mailPreview.test.ts',
    'packages/sdk/src/Helper/openInNewTabOnModifier.test.ts',
    'packages/sdk/src/Helper/panelBase.test.ts',
    'packages/sdk/src/Helper/panelMountPath.test.ts',
    'packages/sdk/src/Helper/scrollToAnchor.test.ts',
];

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
        setupFiles: ['./vitest.setup.ts'],
        css: false,
        testTimeout: 10_000,
        hookTimeout: 10_000,
        teardownTimeout: 5_000,
        // Worker threads spawn faster than the default child processes. Files
        // stay isolated (`isolate: true`): the suite relies on per-file
        // `vi.mock` registrations and a fresh jsdom document, both of which leak
        // between files when isolation is off.
        pool: 'threads',
        // Pre-bundle the MUI packages with esbuild so every test file imports a
        // handful of bundled chunks instead of ~2000 tiny ESM modules.
        deps: {
            optimizer: {
                client: {
                    enabled: true,
                    include: [
                        '@mui/material',
                        '@mui/material/styles',
                        '@mui/material/Box',
                        '@mui/material/Button',
                        '@mui/material/Dialog',
                        '@mui/material/DialogActions',
                        '@mui/material/DialogContent',
                        '@mui/material/DialogTitle',
                        '@mui/material/useMediaQuery',
                        '@mui/icons-material',
                        '@mui/lab',
                        '@mui/lab/TabList',
                        '@mui/x-data-grid',
                        '@mui/x-tree-view',
                    ],
                },
            },
        },
        projects: [
            {
                extends: true,
                test: {
                    name: 'node',
                    environment: 'node',
                    include: ['packages/*/src/**/*.test.ts'],
                    exclude: [...exclude, ...domDependentTsTests],
                },
            },
            {
                extends: true,
                test: {
                    name: 'jsdom',
                    environment: 'jsdom',
                    include: ['packages/*/src/**/*.test.tsx', ...domDependentTsTests],
                    exclude,
                },
            },
        ],
    },
});
