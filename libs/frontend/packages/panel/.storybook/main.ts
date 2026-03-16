import type {StorybookConfig} from '@storybook/react-vite';
import path from 'node:path';

const config: StorybookConfig = {
    stories: ['../src/**/*.stories.@(ts|tsx)', '../../sdk/src/**/*.stories.@(ts|tsx)'],
    addons: ['@storybook/addon-essentials'],
    framework: {
        name: '@storybook/react-vite',
        options: {},
    },
    viteFinal: async (config) => {
        const sdkSrc = path.resolve(__dirname, '../../sdk/src');
        const panelSrc = path.resolve(__dirname, '../src');
        const toolbarSrc = path.resolve(__dirname, '../../toolbar/src');

        config.resolve = config.resolve ?? {};
        config.resolve.alias = {
            ...(config.resolve.alias as Record<string, string>),
            '@app-dev-panel/sdk': sdkSrc,
            '@app-dev-panel/panel': panelSrc,
            '@app-dev-panel/toolbar': toolbarSrc,
        };

        return config;
    },
};

export default config;
