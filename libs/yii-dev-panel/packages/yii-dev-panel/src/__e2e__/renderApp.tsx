import {cleanup, render} from '@testing-library/react';
import App from '@yiisoft/yii-dev-panel/App';
import {afterEach} from 'vitest';

afterEach(() => {
    cleanup();
    localStorage.clear();
});

export function renderApp(path = '/') {
    window.history.pushState({}, '', path);

    return render(
        <App
            config={{
                modules: {toolbar: false},
                router: {basename: '', useHashRouter: false},
                backend: {baseUrl: 'http://127.0.0.1:8080', favoriteUrls: '', usePreferredUrl: false},
            }}
        />,
    );
}
