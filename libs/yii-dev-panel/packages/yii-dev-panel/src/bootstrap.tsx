import '@yiisoft/yii-dev-panel/wdyr';

import App from '@yiisoft/yii-dev-panel/App';
import '@yiisoft/yii-dev-panel/index.css';
import React from 'react';
import ReactDOM from 'react-dom/client';
import {Config} from '@yiisoft/yii-dev-panel-sdk/Config';

let queryParams: {toolbar?: '0' | string} = {toolbar: '1'};
try {
    queryParams = Object.fromEntries(new URLSearchParams(location.search));
} catch (e) {
    console.error('Error while parsing query params: ', e);
}

(function YiiDevPanelWidget(scope) {
    scope.init = function () {
        console.debug('YiiDevPanelWidget initialization', this);
        const container = document.getElementById(this.config.containerId) as HTMLElement;
        console.debug('YiiDevPanelWidget mounting into', container);

        const root = ReactDOM.createRoot(container);
        root.render(
            <React.StrictMode>
                <App config={this.config.options} />
            </React.StrictMode>,
        );
    };
    scope.init();
})(
    (window['YiiDevPanelWidget'] ??= {
        config: {
            containerId: 'root',
            options: {
                modules: {
                    toolbar: queryParams?.toolbar !== '0',
                },
                router: {
                    basename: '',
                    useHashRouter: Config.appEnv === 'github',
                },
                backend: {
                    baseUrl: import.meta.env.VITE_BACKEND_URL || 'http://127.0.0.1:8080',
                    favoriteUrls: [],
                    usePreferredUrl: false,
                },
                env: Config.appEnv,
            },
        },
    }),
);
