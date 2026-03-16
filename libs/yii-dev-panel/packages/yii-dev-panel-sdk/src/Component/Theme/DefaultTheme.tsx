import {PaletteMode, ThemeProvider, createTheme, useMediaQuery} from '@mui/material';
import {LinkProps} from '@mui/material/Link';
import {RouterOptionsContext} from '@yiisoft/yii-dev-panel-sdk/Component/RouterOptions';
import {darkSemanticTokens, semanticTokens} from '@yiisoft/yii-dev-panel-sdk/Component/Theme/tokens';
import React, {PropsWithChildren, useContext, useMemo} from 'react';
import {Link as RouterLink, LinkProps as RouterLinkProps, useHref} from 'react-router-dom';

const LinkBehavior = (routerOptions: {openLinksInNewWindow: boolean; baseUrl: string}) =>
    React.forwardRef<HTMLAnchorElement, Omit<RouterLinkProps, 'to'> & {href: RouterLinkProps['to']}>((props, ref) => {
        let {href, ...other} = props;
        const routerHref = useHref(href);

        if (typeof href !== 'string') {
            href = '#';
        }

        if (href === '#' || href.startsWith('http://') || href.startsWith('https://')) {
            return <a href={href} ref={ref} {...other} />;
        }

        if (routerOptions.openLinksInNewWindow) {
            other = {...other, target: '_blank'};
        }
        if (routerOptions.baseUrl) {
            return <a href={routerOptions.baseUrl + routerHref} ref={ref} {...other} />;
        }
        return <RouterLink ref={ref} to={href} {...other} />;
    });

export const createAdpTheme = (mode: PaletteMode, routerOptions: {openLinksInNewWindow: boolean; baseUrl: string}) => {
    const linkComponent = LinkBehavior(routerOptions);
    const palette =
        mode === 'dark' ? {...semanticTokens.palette, ...darkSemanticTokens.palette} : semanticTokens.palette;

    // Build MUI shadows array (25 entries required)
    const shadows: [string, ...string[]] = ['none', ...Array(24).fill(semanticTokens.shadows.sm)] as [
        string,
        ...string[],
    ];
    shadows[2] = semanticTokens.shadows.md;
    shadows[3] = semanticTokens.shadows.md;

    return createTheme({
        palette: {mode, ...palette},
        typography: {
            fontFamily: semanticTokens.typography.fontFamily,
            h4: semanticTokens.typography.h4,
            body1: semanticTokens.typography.body1,
            body2: semanticTokens.typography.body2,
            caption: semanticTokens.typography.caption,
            overline: semanticTokens.typography.overline,
        },
        shape: semanticTokens.shape,
        shadows: shadows as unknown as typeof createTheme extends (o: infer O) => unknown
            ? O extends {shadows?: infer S}
                ? S
                : never
            : never,
        components: {
            MuiLink: {defaultProps: {component: linkComponent} as LinkProps},
            MuiButtonBase: {defaultProps: {LinkComponent: linkComponent}},
            MuiCssBaseline: {
                styleOverrides: {
                    body: {backgroundColor: palette.background.default},
                    '@font-face': [{fontFamily: 'Inter'}, {fontFamily: 'JetBrains Mono'}],
                },
            },
            MuiPaper: {
                styleOverrides: {outlined: {borderColor: palette.divider, boxShadow: semanticTokens.shadows.sm}},
            },
            MuiButton: {styleOverrides: {root: {textTransform: 'none', fontWeight: 500}}},
        },
    });
};

export const DefaultThemeProvider = ({children}: PropsWithChildren) => {
    const prefersDarkMode = useMediaQuery('(prefers-color-scheme: dark)');
    const mode: PaletteMode = prefersDarkMode ? 'dark' : 'light';
    const routerOptions = useContext(RouterOptionsContext);

    const theme = useMemo(() => createAdpTheme(mode, routerOptions), [mode, routerOptions]);

    return <ThemeProvider theme={theme}>{children}</ThemeProvider>;
};

export {darkSemanticTokens, primitives, semanticTokens} from '@yiisoft/yii-dev-panel-sdk/Component/Theme/tokens';
