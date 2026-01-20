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
            allow: ['.'],
        },
    },
    resolve: {
        alias: {
            // Needed for `useSelector` tracking in wdyr.tsx: https://github.com/welldone-software/why-did-you-render/issues/85
            'react-redux': 'react-redux/dist/react-redux.js',
            '@yiisoft/yii-dev-panel/*': '../yii-dev-panel/src/*',
            '@yiisoft/yii-dev-panel-sdk/*': '../yii-dev-panel-sdk/src/*',
            '@yiisoft/yii-dev-toolbar/*': '../yii-dev-toolbar/src/*',
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
            srcDir: './../yii-dev-panel-sdk/src/',
            filename: 'service-worker.ts',
            devOptions: {
                enabled: process.env.NODE_ENV === 'development',
                type: 'module',
                navigateFallback: '/index.html',
            },
            registerType: 'autoUpdate',
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
    base: process.env.VITE_ENV === 'github' ? 'https://yiisoft.github.io/yii-dev-panel/' : './',
    build: {
        rollupOptions: {
            output: {
                assetFileNames: 'bundle.css',
                entryFileNames: 'bundle.js',
            },
        },
        minify: process.env.VITE_ENV === 'github',
        outDir: 'dist',
        target: 'esnext',
    },
}));
