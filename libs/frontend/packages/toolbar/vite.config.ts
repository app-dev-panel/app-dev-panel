import react from '@vitejs/plugin-react';
import {defineConfig} from 'vite';
import svgrPlugin from 'vite-plugin-svgr';
import viteTsconfigPaths from 'vite-tsconfig-paths';

export default defineConfig(async () => ({
    server: {
        open: false,
        port: 3001,
        fs: {
            allow: ['../..'],
        },
    },
    resolve: {
        alias: {
            '@app-dev-panel/panel/*': '../panel/src/*',
            '@app-dev-panel/sdk/*': '../sdk/src/*',
            '@app-dev-panel/toolbar/*': '../toolbar/src/*',
        },
    },
    plugins: [
        react(),
        viteTsconfigPaths(),
        svgrPlugin(),
    ],
    optimizeDeps: {
        // Force Vite to pre-bundle MUI dependencies together to avoid
        // createTheme resolution issues caused by the react-redux alias.
        include: ['@mui/material', '@mui/system', '@mui/material/styles'],
    },
    base: './',
    build: {
        rollupOptions: {
            output: {
                assetFileNames: (assetInfo) => {
                    const name = assetInfo.names?.[0] ?? '';
                    if (/\.(woff2?|ttf|eot)$/.test(name)) {
                        return 'assets/[name][extname]';
                    }
                    return 'bundle[extname]';
                },
                entryFileNames: 'bundle.js',
            },
        },
        minify: process.env.VITE_ENV === 'github',
        outDir: 'dist',
        target: 'esnext',
    },
}));
