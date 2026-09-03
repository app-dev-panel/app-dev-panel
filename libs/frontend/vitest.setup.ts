import '@testing-library/jest-dom/vitest';
import {cleanup} from '@testing-library/react';
import {afterEach} from 'vitest';

// Testing Library only auto-registers its `afterEach(cleanup)` when the module
// is first evaluated. With `isolate: false` (see vitest.config.ts) the module
// cache survives across test files, so the hook would be registered for the
// first file of each worker only — register it explicitly for every file.
afterEach(() => {
    cleanup();
});

// Mock window.matchMedia for jsdom (required by MUI, @uiw/react-json-view, etc.).
// Pure helper tests run in the `node` environment (see vitest.config.ts), where
// there is no `window` to patch.
if (typeof window !== 'undefined') {
    Object.defineProperty(window, 'matchMedia', {
        writable: true,
        value: (query: string) => ({
            matches: false,
            media: query,
            onchange: null,
            addListener: () => {},
            removeListener: () => {},
            addEventListener: () => {},
            removeEventListener: () => {},
            dispatchEvent: () => false,
        }),
    });
}
