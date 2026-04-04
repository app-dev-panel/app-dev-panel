import {RouterOptionsContextProvider} from '@app-dev-panel/sdk/Component/RouterOptions';
import {DefaultThemeProvider} from '@app-dev-panel/sdk/Component/Theme/DefaultTheme';
import {modules} from '@app-dev-panel/toolbar/modules';
import {createRouter} from '@app-dev-panel/toolbar/router';
import {createStore} from '@app-dev-panel/toolbar/store';
import {useMemo} from 'react';
import {Provider} from 'react-redux';
import {RouterProvider} from 'react-router';

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
        <RouterOptionsContextProvider baseUrl="" openLinksInNewWindow={true}>
            <Provider store={store}>
                <DefaultThemeProvider>
                    <RouterProvider router={router} />
                </DefaultThemeProvider>
            </Provider>
        </RouterOptionsContextProvider>
    );
}
