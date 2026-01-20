import {ErrorFallback} from '@yiisoft/yii-dev-panel-sdk/Component/ErrorFallback';
import {RouterOptionsContextProvider} from '@yiisoft/yii-dev-panel-sdk/Component/RouterOptions';
import {DefaultThemeProvider} from '@yiisoft/yii-dev-panel-sdk/Component/Theme/DefaultTheme';
import '@yiisoft/yii-dev-panel/App.css';
import {modules} from '@yiisoft/yii-dev-panel/modules';
import {createRouter} from '@yiisoft/yii-dev-panel/router';
import {createStore} from '@yiisoft/yii-dev-panel/store';
import {ErrorBoundary} from 'react-error-boundary';
import {Provider} from 'react-redux';
import {RouterProvider} from 'react-router-dom';
import React, {useEffect} from 'react';
import {PersistGate} from 'redux-persist/integration/react';
import {CrossWindowEventType, dispatchWindowEvent} from '@yiisoft/yii-dev-panel-sdk/Helper/dispatchWindowEvent';
import {changeBaseUrl} from '@yiisoft/yii-dev-panel-sdk/API/Application/ApplicationContext';
import {BreadcrumbsContextProvider} from '@yiisoft/yii-dev-panel/Application/Context/BreadcrumbsContext';

type AppProps = {
    config: {
        modules: {
            toolbar: boolean;
        };
        router: {
            basename: string;
            useHashRouter: boolean;
        };
        backend: {
            baseUrl: string;
            favoriteUrls: string;
            usePreferredUrl: boolean;
        };
    };
};
export default function App({config}: AppProps) {
    const router = createRouter(modules, config.router, config.modules);
    const {store, persistor} = createStore({
        application: {
            baseUrl: config.backend.baseUrl,
            favoriteUrls: config.backend.favoriteUrls ?? [],
        },
    });

    useEffect(() => {
        if (config.backend.usePreferredUrl) {
            console.log('Override backend url', config.backend.baseUrl);
            store.dispatch(changeBaseUrl(config.backend.baseUrl));
        }
    }, []);

    useEffect(() => {
        dispatchWindowEvent(window.parent, 'panel.loaded', true);

        const listener = (event: MessageEvent) => {
            console.log('Post message event', event, event.data);
            const data = event.data;

            if ('event' in data) {
                switch (data.event as CrossWindowEventType) {
                    case 'router.navigate':
                        router.navigate(data.value);
                        break;
                }
            }
        };

        window.addEventListener('message', listener);

        return () => {
            window?.removeEventListener('message', listener);
        };
    }, []);

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
