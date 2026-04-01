import {Config} from '@app-dev-panel/sdk/Config';
import App from '@app-dev-panel/toolbar/App';
import '@app-dev-panel/toolbar/index.css';
import React from 'react';
import ReactDOM from 'react-dom/client';

const backendUrl = import.meta.env.VITE_BACKEND_URL || (window as any).__adpBackendUrl || 'http://127.0.0.1:8080';

const defaultConfig = {
    containerId: 'app-dev-toolbar',
    options: {
        router: {basename: '', useHashRouter: Config.appEnv === 'github'},
        backend: {baseUrl: backendUrl, usePreferredUrl: true},
    },
};

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
    // When injected by ToolbarInjector, the page sets window['AppDevPanelToolbarWidget']
    // BEFORE this script runs. Use that config if present, otherwise use defaults.
    // Always override backend.baseUrl with the resolved value (dev controls → env → fallback).
    (() => {
        const existing = window['AppDevPanelToolbarWidget'] as any;
        if (existing?.config) {
            existing.config.options.backend.baseUrl = backendUrl;
            return existing;
        }
        return {config: defaultConfig};
    })(),
);
