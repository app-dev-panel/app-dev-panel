import '@app-dev-panel/panel/wdyr';

import App from '@app-dev-panel/panel/App';
import '@app-dev-panel/panel/index.css';
import {Config} from '@app-dev-panel/sdk/Config';
import React from 'react';
import ReactDOM from 'react-dom/client';

let queryParams: {toolbar?: '0' | string} = {toolbar: '1'};
try {
    queryParams = Object.fromEntries(new URLSearchParams(location.search));
} catch (e) {
    console.error('Error while parsing query params: ', e);
}

(function AppDevPanelWidget(scope) {
    scope.init = function () {
        console.debug('AppDevPanelWidget initialization', this);
        const container = document.getElementById(this.config.containerId) as HTMLElement;
        console.debug('AppDevPanelWidget mounting into', container);

        const root = ReactDOM.createRoot(container);
        root.render(
            <React.StrictMode>
                <App config={this.config.options} />
            </React.StrictMode>,
        );
    };
    scope.init();
})(
    (window['AppDevPanelWidget'] ??= {
        config: {
            containerId: 'root',
            options: {
                modules: {toolbar: queryParams?.toolbar !== '0'},
                router: {basename: '', useHashRouter: Config.appEnv === 'github'},
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
