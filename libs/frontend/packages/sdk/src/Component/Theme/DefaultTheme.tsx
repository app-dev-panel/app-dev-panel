import {RouterOptionsContext} from '@app-dev-panel/sdk/Component/RouterOptions';
import {darkSemanticTokens, semanticTokens} from '@app-dev-panel/sdk/Component/Theme/tokens';
import {PaletteMode, ThemeProvider, createTheme, useMediaQuery} from '@mui/material';
import {LinkProps} from '@mui/material/Link';
import React, {PropsWithChildren, useContext, useMemo} from 'react';
import {useSelector} from 'react-redux';
import {Link as RouterLink, LinkProps as RouterLinkProps, useHref} from 'react-router-dom';

// ---------------------------------------------------------------------------
// MUI module augmentation — extend theme with ADP custom properties
// ---------------------------------------------------------------------------

type CollectorColorsMap = typeof semanticTokens.collectorColors;

/* eslint-disable @typescript-eslint/consistent-type-definitions */
declare module '@mui/material/styles' {
    interface Theme {
        adp: {
            fontFamilyMono: string;
            chartColors: readonly string[];
            collectorColors: CollectorColorsMap;
            highlightColor: string;
        };
    }
    interface ThemeOptions {
        adp?: {
            fontFamilyMono?: string;
            chartColors?: readonly string[];
            collectorColors?: CollectorColorsMap;
            highlightColor?: string;
        };
    }
    interface TypographyVariants {
        micro: React.CSSProperties;
    }
    interface TypographyVariantsOptions {
        micro?: React.CSSProperties;
    }
}

declare module '@mui/material/Typography' {
    interface TypographyPropsVariantOverrides {
        micro: true;
    }
}
/* eslint-enable @typescript-eslint/consistent-type-definitions */

// ---------------------------------------------------------------------------
// Link behavior
// ---------------------------------------------------------------------------

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

// ---------------------------------------------------------------------------
// Theme factory
// ---------------------------------------------------------------------------

export const createAdpTheme = (mode: PaletteMode, routerOptions: {openLinksInNewWindow: boolean; baseUrl: string}) => {
    const linkComponent = LinkBehavior(routerOptions);
    const isDark = mode === 'dark';
    const palette = isDark ? {...semanticTokens.palette, ...darkSemanticTokens.palette} : semanticTokens.palette;

    // Build MUI shadows array (25 entries required)
    // 0=none, 1=sm (cards), 2-3=md (popovers), 4+=lg (menus, dialogs)
    const shadows: [string, ...string[]] = ['none', ...Array(24).fill(semanticTokens.shadows.lg)] as [
        string,
        ...string[],
    ];
    shadows[1] = semanticTokens.shadows.sm;
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
            micro: semanticTokens.typography.micro,
            overline: semanticTokens.typography.overline,
        },
        shape: semanticTokens.shape,
        shadows: shadows as unknown as typeof createTheme extends (o: infer O) => unknown
            ? O extends {shadows?: infer S}
                ? S
                : never
            : never,
        adp: {
            fontFamilyMono: semanticTokens.typography.fontFamilyMono,
            chartColors: isDark ? darkSemanticTokens.chartColors : semanticTokens.chartColors,
            collectorColors: isDark ? darkSemanticTokens.collectorColors : semanticTokens.collectorColors,
            highlightColor: isDark ? darkSemanticTokens.highlightColor : semanticTokens.highlightColor,
        },
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
            MuiMenu: {styleOverrides: {paper: {border: `1px solid ${palette.divider}`, backgroundImage: 'none'}}},
            MuiPopover: {styleOverrides: {paper: {border: `1px solid ${palette.divider}`, backgroundImage: 'none'}}},
            MuiAutocomplete: {
                styleOverrides: {paper: {border: `1px solid ${palette.divider}`, backgroundImage: 'none'}},
            },
            MuiButton: {styleOverrides: {root: {textTransform: 'none', fontWeight: 500}}},
        },
    });
};

export const DefaultThemeProvider = ({children}: PropsWithChildren) => {
    const prefersDarkMode = useMediaQuery('(prefers-color-scheme: dark)');
    const themeMode = useSelector((state: any) => state?.application?.themeMode) as string | undefined;
    const mode: PaletteMode =
        themeMode === 'light' || themeMode === 'dark' ? themeMode : prefersDarkMode ? 'dark' : 'light';
    const routerOptions = useContext(RouterOptionsContext);

    const theme = useMemo(() => createAdpTheme(mode, routerOptions), [mode, routerOptions]);

    return <ThemeProvider theme={theme}>{children}</ThemeProvider>;
};

export {darkSemanticTokens, primitives, semanticTokens} from '@app-dev-panel/sdk/Component/Theme/tokens';

/** Monospace font family — safe to use directly in sx props (does not change between themes). */
export const monoFontFamily = semanticTokens.typography.fontFamilyMono;
