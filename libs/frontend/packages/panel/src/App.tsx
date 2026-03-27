import '@app-dev-panel/panel/App.css';
import {BreadcrumbsContextProvider} from '@app-dev-panel/panel/Application/Context/BreadcrumbsContext';
import {modules} from '@app-dev-panel/panel/modules';
import {createRouter} from '@app-dev-panel/panel/router';
import {createStore} from '@app-dev-panel/panel/store';
import {changeBaseUrl} from '@app-dev-panel/sdk/API/Application/ApplicationContext';
import {ErrorFallback} from '@app-dev-panel/sdk/Component/ErrorFallback';
import {RouterOptionsContextProvider} from '@app-dev-panel/sdk/Component/RouterOptions';
import {DefaultThemeProvider} from '@app-dev-panel/sdk/Component/Theme/DefaultTheme';
import {CrossWindowEventType, dispatchWindowEvent} from '@app-dev-panel/sdk/Helper/dispatchWindowEvent';
import {useEffect} from 'react';
import {ErrorBoundary} from 'react-error-boundary';
import {Provider} from 'react-redux';
import {RouterProvider} from 'react-router-dom';
import {PersistGate} from 'redux-persist/integration/react';

type AppProps = {
    config: {
        modules: {toolbar: boolean};
        router: {basename: string; useHashRouter: boolean};
        backend: {baseUrl: string; favoriteUrls: string; usePreferredUrl: boolean};
    };
};
export default function App({config}: AppProps) {
    const router = createRouter(modules, config.router, config.modules);
    const {store, persistor} = createStore({
        application: {baseUrl: config.backend.baseUrl, favoriteUrls: config.backend.favoriteUrls ?? []},
    });

    useEffect(() => {
        if (config.backend.usePreferredUrl) {
            store.dispatch(changeBaseUrl(config.backend.baseUrl));
        }
    }, [config.backend.usePreferredUrl, config.backend.baseUrl, store]);

    useEffect(() => {
        dispatchWindowEvent(window.parent, 'panel.loaded', true);

        const listener = (event: MessageEvent) => {
            if (event.origin !== window.location.origin) {
                return;
            }

            const data = event.data;

            if (data && typeof data === 'object' && 'event' in data) {
                switch (data.event as CrossWindowEventType) {
                    case 'router.navigate':
                        router.navigate(data.value);
                        break;
                }
            }
        };

        window.addEventListener('message', listener);

        return () => {
            window.removeEventListener('message', listener);
        };
    }, [router]);

    return (
        <RouterOptionsContextProvider baseUrl="" openLinksInNewWindow={false}>
            <Provider store={store}>
                <PersistGate persistor={persistor}>
                    <DefaultThemeProvider>
                        <ErrorBoundary FallbackComponent={ErrorFallback} resetKeys={[window.location.pathname]}>
                            <BreadcrumbsContextProvider>
                                <RouterProvider router={router} />
                            </BreadcrumbsContextProvider>
                        </ErrorBoundary>
                    </DefaultThemeProvider>
                </PersistGate>
            </Provider>
        </RouterOptionsContextProvider>
    );
}
