declare global {
    // eslint-disable-next-line @typescript-eslint/consistent-type-definitions
    interface Window {
        __adp_panel_url?: string;
    }
}

/**
 * Resolve the URL path under which the ADP panel SPA is mounted in the host app.
 *
 * - When the toolbar is injected by `ToolbarInjector` (PHP), the panel path is
 *   written to `window.__adp_panel_url` alongside the bundle script.
 * - When the panel runs standalone, there is no such global — we fall back to
 *   `/debug`, which matches the default adapter setup.
 *
 * Trailing slashes are stripped so `panelMountPath() + '/...'` never produces
 * a `//...` sequence (which `new URL()` would parse as a protocol-relative
 * authority).
 */
export const panelMountPath = (): string => {
    const raw = typeof window !== 'undefined' ? window.__adp_panel_url : undefined;
    if (!raw) {
        return '/debug';
    }
    const trimmed = raw.replace(/\/+$/, '');
    return trimmed.length > 0 ? trimmed : '';
};

/**
 * Build a `target="_top"` URL that navigates the host window to a page inside
 * the ADP panel, correctly prefixed with the panel mount path.
 *
 * Examples (panel mounted at `/debug`):
 *   `panelPagePath('/inspector/files?class=Foo')` → `/debug/inspector/files?class=Foo`
 *   `panelPagePath('?collector=Foo')`             → `/debug?collector=Foo`
 *   `panelPagePath('')`                           → `/debug`
 *
 * A bare query string or empty input is appended directly so we hit the
 * panel's root route (`/debug`), not `/debug/` which the adapter does not
 * match as a catch-all path.
 */
export const panelPagePath = (pathWithQuery: string = ''): string => {
    const mount = panelMountPath();
    if (pathWithQuery === '' || pathWithQuery.startsWith('?') || pathWithQuery.startsWith('#')) {
        return mount + pathWithQuery;
    }
    const normalised = pathWithQuery.startsWith('/') ? pathWithQuery : '/' + pathWithQuery;
    return mount + normalised;
};
