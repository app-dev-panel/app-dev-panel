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
            // Needed for `useSelector` tracking in wdyr.tsx: https://github.com/welldone-software/why-did-you-render/issues/85
            'react-redux': 'react-redux/dist/react-redux.js',
            '@app-dev-panel/panel/*': '../panel/src/*',
            '@app-dev-panel/sdk/*': '../sdk/src/*',
            '@app-dev-panel/toolbar/*': '../toolbar/src/*',
        },
    },
    plugins: [
        react({
            jsxImportSource: '@welldone-software/why-did-you-render',
        }),
        viteTsconfigPaths(),
        svgrPlugin(),
    ],
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
