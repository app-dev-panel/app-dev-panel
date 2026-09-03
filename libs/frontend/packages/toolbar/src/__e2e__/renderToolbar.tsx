import App from '@app-dev-panel/toolbar/App';
import {cleanup, render} from '@testing-library/react';
import {afterEach} from 'vitest';

afterEach(() => {
    cleanup();
    localStorage.clear();
});

export function renderToolbar() {
    // Browser test files share one origin, and redux-persist flushes the
    // toolbar state (open/closed, position, float rect) to localStorage
    // asynchronously — so a write from the previous file can land after its
    // `afterEach` cleared storage. Start every render from a clean slate.
    localStorage.clear();
    sessionStorage.clear();
    return render(
        <App
            config={{
                router: {basename: '', useHashRouter: false},
                backend: {baseUrl: 'http://127.0.0.1:8080', favoriteUrls: [], usePreferredUrl: false},
            }}
        />,
    );
}
