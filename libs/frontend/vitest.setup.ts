import '@testing-library/jest-dom/vitest';

// Mock window.matchMedia for jsdom (required by MUI, @uiw/react-json-view, etc.)
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
