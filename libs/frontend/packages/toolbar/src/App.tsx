import {changeBaseUrl} from '@app-dev-panel/sdk/API/Application/ApplicationContext';
import {RouterOptionsContextProvider} from '@app-dev-panel/sdk/Component/RouterOptions';
import {DefaultThemeProvider} from '@app-dev-panel/sdk/Component/Theme/DefaultTheme';
import '@app-dev-panel/toolbar/App.css';
import {modules} from '@app-dev-panel/toolbar/modules';
import {createRouter} from '@app-dev-panel/toolbar/router';
import {createStore} from '@app-dev-panel/toolbar/store';
import {useEffect} from 'react';
import {Provider} from 'react-redux';
import {RouterProvider} from 'react-router-dom';

type AppProps = {
    config: {
        router: {basename: string; useHashRouter: boolean};
        backend: {baseUrl: string; favoriteUrls: string; usePreferredUrl: boolean};
    };
};

export default function App({config}: AppProps) {
    const router = createRouter(modules, config.router);
    const {store} = createStore({
        application: {baseUrl: config.backend.baseUrl, favoriteUrls: config.backend.favoriteUrls ?? []},
    });

    useEffect(() => {
        if (config.backend.usePreferredUrl) {
            console.log('Override backend url', config.backend.baseUrl);
            store.dispatch(changeBaseUrl(config.backend.baseUrl));
        }
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
