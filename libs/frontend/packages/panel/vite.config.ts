import react from '@vitejs/plugin-react';
import {defineConfig} from 'vite';
import {VitePWA} from 'vite-plugin-pwa';
import svgrPlugin from 'vite-plugin-svgr';
import viteTsconfigPaths from 'vite-tsconfig-paths';

const sharedModules = ['react', 'react-dom', 'react-redux', 'react-router', 'react-router-dom', 'redux-persist'];
export default defineConfig(() => ({
    server: {
        open: false,
        port: 3000,
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
        VitePWA({
            // injectRegister: 'script',
            strategies: 'injectManifest',
            // Fix symlink for Windows
            srcDir: './../sdk/src/',
            filename: 'service-worker.ts',
            devOptions: {
                enabled: process.env.NODE_ENV === 'development',
                type: 'module',
                navigateFallback: '/index.html',
            },
            registerType: 'autoUpdate',
            injectManifest: {
                maximumFileSizeToCacheInBytes: 10 * 1024 * 1024,
            },
            workbox: {
                sourcemap: true,
            },
        }),
        viteTsconfigPaths(),
        svgrPlugin(),
        // federation({
        //     name: 'host',
        //     remotes: {},
        //     shared: sharedModules,
        // }),
    ],
    base: './',
    build: {
        rollupOptions: {
            output: {
                assetFileNames: (assetInfo) => {
                    // Keep original extensions for fonts so servers send correct Content-Type
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
