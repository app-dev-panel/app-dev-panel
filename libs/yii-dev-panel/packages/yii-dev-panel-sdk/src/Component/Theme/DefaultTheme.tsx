import {PaletteMode, ThemeProvider, createTheme, useMediaQuery} from '@mui/material';
import {LinkProps} from '@mui/material/Link';
import {RouterOptionsContext} from '@yiisoft/yii-dev-panel-sdk/Component/RouterOptions';
import React, {PropsWithChildren, useContext} from 'react';
import {Link as RouterLink, LinkProps as RouterLinkProps, useHref} from 'react-router-dom';

const LinkBehavior = (routerOptions) =>
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
        // Map href (MUI) -> to (react-router)
        return <RouterLink ref={ref} to={href} {...other} />;
    });

export const DefaultThemeProvider = ({children}: PropsWithChildren) => {
    const prefersDarkMode = useMediaQuery('(prefers-color-scheme: dark)');
    const mode: PaletteMode = prefersDarkMode ? 'dark' : 'light';

    const routerOptions = useContext(RouterOptionsContext);

    const theme = createTheme({
        palette: {
            mode: mode,
            primary: {
                main: '#00617B',
            },
            secondary: {
                main: '#873C00',
            },
        },
        components: {
            MuiLink: {
                defaultProps: {
                    component: LinkBehavior(routerOptions),
                } as LinkProps,
            },
            MuiButtonBase: {
                defaultProps: {
                    LinkComponent: LinkBehavior(routerOptions),
                },
            },
        },
    });

    return <ThemeProvider theme={theme}>{children}</ThemeProvider>;
};
