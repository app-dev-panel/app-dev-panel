import App from '@app-dev-panel/toolbar/App';
import {cleanup, render} from '@testing-library/react';
import {afterEach} from 'vitest';

afterEach(() => {
    cleanup();
    localStorage.clear();
});

export function renderToolbar() {
    return render(
        <App
            config={{
                router: {basename: '', useHashRouter: false},
                backend: {baseUrl: 'http://127.0.0.1:8080', favoriteUrls: [], usePreferredUrl: false},
            }}
        />,
    );
}
