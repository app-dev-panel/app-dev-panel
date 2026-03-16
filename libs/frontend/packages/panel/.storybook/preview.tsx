import {CssBaseline, ThemeProvider} from '@mui/material';
import type {Preview} from '@storybook/react';
import {createAdpTheme} from '@app-dev-panel/sdk/Component/Theme/DefaultTheme';
import React from 'react';

const lightTheme = createAdpTheme('light', {openLinksInNewWindow: false, baseUrl: ''});
const darkTheme = createAdpTheme('dark', {openLinksInNewWindow: false, baseUrl: ''});

const preview: Preview = {
    parameters: {
        controls: {matchers: {color: /(background|color)$/i, date: /Date$/i}},
        backgrounds: {disable: true},
    },
    globalTypes: {
        theme: {
            description: 'Theme mode',
            toolbar: {title: 'Theme', icon: 'paintbrush', items: ['light', 'dark'], dynamicTitle: true},
        },
    },
    initialGlobals: {theme: 'light'},
    decorators: [
        (Story, context) => {
            const theme = context.globals.theme === 'dark' ? darkTheme : lightTheme;
            return (
                <ThemeProvider theme={theme}>
                    <CssBaseline />
                    <Story />
                </ThemeProvider>
            );
        },
    ],
};

export default preview;
