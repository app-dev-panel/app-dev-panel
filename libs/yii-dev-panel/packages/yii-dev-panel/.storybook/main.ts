import type {StorybookConfig} from '@storybook/react-vite';
import path from 'node:path';

const config: StorybookConfig = {
    stories: ['../src/**/*.stories.@(ts|tsx)', '../../yii-dev-panel-sdk/src/**/*.stories.@(ts|tsx)'],
    addons: ['@storybook/addon-essentials'],
    framework: {
        name: '@storybook/react-vite',
        options: {},
    },
    viteFinal: async (config) => {
        const sdkSrc = path.resolve(__dirname, '../../yii-dev-panel-sdk/src');
        const panelSrc = path.resolve(__dirname, '../src');
        const toolbarSrc = path.resolve(__dirname, '../../yii-dev-toolbar/src');

        config.resolve = config.resolve ?? {};
        config.resolve.alias = {
            ...(config.resolve.alias as Record<string, string>),
            '@yiisoft/yii-dev-panel-sdk': sdkSrc,
            '@yiisoft/yii-dev-panel': panelSrc,
            '@yiisoft/yii-dev-toolbar': toolbarSrc,
        };

        return config;
    },
};

export default config;
