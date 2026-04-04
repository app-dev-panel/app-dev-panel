import {RouterOptionsContextProvider} from '@app-dev-panel/sdk/Component/RouterOptions';
import {DefaultThemeProvider} from '@app-dev-panel/sdk/Component/Theme/DefaultTheme';
import {modules} from '@app-dev-panel/toolbar/modules';
import {createRouter} from '@app-dev-panel/toolbar/router';
import {createStore} from '@app-dev-panel/toolbar/store';
import {Box, Typography} from '@mui/material';
import {Component, type ErrorInfo, type ReactNode, useMemo} from 'react';
import {Provider} from 'react-redux';
import {RouterProvider} from 'react-router';

class ToolbarErrorBoundary extends Component<{children: ReactNode}, {error: Error | null}> {
    state: {error: Error | null} = {error: null};

    static getDerivedStateFromError(error: Error) {
        return {error};
    }

    componentDidCatch(error: Error, info: ErrorInfo) {
        console.error('[ADP Toolbar] Error:', error, info);
    }

    render() {
        if (this.state.error) {
            return (
                <Box
                    sx={{
                        position: 'fixed',
                        bottom: 16,
                        right: 16,
                        zIndex: 1300,
                        bgcolor: 'background.paper',
                        border: 1,
                        borderColor: 'error.main',
                        borderRadius: 2,
                        px: 2,
                        py: 1,
                        boxShadow: 2,
                        cursor: 'pointer',
                        maxWidth: 300,
                    }}
                    onClick={() => this.setState({error: null})}
                >
                    <Typography sx={{fontSize: 12, fontWeight: 600, color: 'error.main'}}>Toolbar error</Typography>
                    <Typography sx={{fontSize: 11, color: 'text.secondary', mt: 0.5}}>
                        {this.state.error.message}
                    </Typography>
                    <Typography sx={{fontSize: 10, color: 'text.disabled', mt: 0.5}}>Click to retry</Typography>
                </Box>
            );
        }
        return this.props.children;
    }
}

type AppProps = {
    config: {
        router: {basename: string; useHashRouter: boolean};
        backend: {baseUrl: string; favoriteUrls: string[]; usePreferredUrl: boolean};
    };
};

export default function App({config}: AppProps) {
    const {store, router} = useMemo(() => {
        const r = createRouter(modules, config.router);
        const {store: s} = createStore({
            application: {baseUrl: config.backend.baseUrl, favoriteUrls: config.backend.favoriteUrls ?? []} as any,
        });
        return {store: s, router: r};
    }, []);

    return (
        <ToolbarErrorBoundary>
            <RouterOptionsContextProvider baseUrl="" openLinksInNewWindow={true}>
                <Provider store={store}>
                    <DefaultThemeProvider>
                        <RouterProvider router={router} />
                    </DefaultThemeProvider>
                </Provider>
            </RouterOptionsContextProvider>
        </ToolbarErrorBoundary>
    );
}
