import {RouterOptionsContextProvider} from '@yiisoft/yii-dev-panel-sdk/Component/RouterOptions';
import {DefaultThemeProvider} from '@yiisoft/yii-dev-panel-sdk/Component/Theme/DefaultTheme';
import '@yiisoft/yii-dev-toolbar/App.css';
import {modules} from '@yiisoft/yii-dev-toolbar/modules';
import {createRouter} from '@yiisoft/yii-dev-toolbar/router';
import {createStore} from '@yiisoft/yii-dev-toolbar/store';
import {Provider} from 'react-redux';
import {RouterProvider} from 'react-router-dom';
import {useEffect} from 'react';
import {changeBaseUrl} from '@yiisoft/yii-dev-panel-sdk/API/Application/ApplicationContext';

type AppProps = {
    config: {
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
    const router = createRouter(modules, config.router);
    const {store} = createStore({
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
