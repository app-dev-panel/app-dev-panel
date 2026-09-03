import App from '@app-dev-panel/panel/App';
import '@app-dev-panel/panel/index.css';
import {Config} from '@app-dev-panel/sdk/Config';
import {basenameFromDocument} from '@app-dev-panel/sdk/Helper/panelBase';
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
        const container = document.getElementById(this.config.containerId) as HTMLElement;

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
                // Issue #113: when no host page injected a config (static
                // index.html served by `adp serve`, Vite dev server, …) the
                // mount directory is whatever `<base href>` resolved to, so
                // deep links such as `/debug/inspector/routes` route correctly.
                router: {basename: basenameFromDocument(), useHashRouter: Config.appEnv === 'github'},
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
