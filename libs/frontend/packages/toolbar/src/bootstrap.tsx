import '@app-dev-panel/toolbar/wdyr';

import {Config} from '@app-dev-panel/sdk/Config';
import App from '@app-dev-panel/toolbar/App';
import '@app-dev-panel/toolbar/index.css';
import React from 'react';
import ReactDOM from 'react-dom/client';

(function AppDevPanelToolbarWidget(scope) {
    scope.init = function () {
        console.debug('AppDevPanelToolbarWidget initialization', this);
        const container = document.getElementById(this.config.containerId) as HTMLElement;
        console.debug('AppDevPanelToolbarWidget mounting into', container);

        const root = ReactDOM.createRoot(container);
        root.render(
            <React.StrictMode>
                <App config={this.config.options} />
            </React.StrictMode>,
        );
    };
    scope.init();
})(
    (window['AppDevPanelToolbarWidget'] ??= {
        config: {
            containerId: 'app-dev-toolbar',
            options: {
                router: {basename: '', useHashRouter: Config.appEnv === 'github'},
                backend: {
                    baseUrl:
                        import.meta.env.VITE_BACKEND_URL || (window as any).__adpBackendUrl || 'http://127.0.0.1:8080',
                    usePreferredUrl: false,
                },
            },
        },
    }),
);
