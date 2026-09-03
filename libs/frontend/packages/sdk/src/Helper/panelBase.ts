/**
 * Helpers that reconcile the panel's React Router `basename` with the mount
 * path the host application serves the SPA from (issue #113 / #111).
 */

/**
 * Derive the router basename from the document `<base href>`.
 *
 * Every server that emits the panel HTML sets `<base href="<mount>/">`
 * (`PanelController`, `adp serve`, or the inline bootstrap in `index.html`),
 * so the base URL's pathname *is* the mount directory. The trailing slash is
 * stripped because React Router expects `/debug`, not `/debug/`; a root
 * mount yields `''`.
 */
export const basenameFromDocument = (): string => {
    if (typeof document === 'undefined') {
        return '';
    }
    try {
        return new URL(document.baseURI).pathname.replace(/\/+$/, '');
    } catch {
        return '';
    }
};

/**
 * Strip the mount prefix from a panel URL so it can be handed to
 * `router.navigate()`, which resolves relative to the router basename.
 *
 * The toolbar posts fully-prefixed URLs (`/debug/debug?collector=…`, the
 * same shape `window.open` needs); the panel router, configured with
 * `basename: '/debug'`, must receive `/debug?collector=…` — otherwise the
 * basename is applied twice and the panel ends up at `/debug/debug/debug`.
 *
 * URLs that do not start with the basename are returned unchanged.
 */
export const stripBasename = (url: string, basename: string): string => {
    const mount = basename.replace(/\/+$/, '');
    if (mount === '') {
        return url;
    }
    if (url === mount) {
        return '/';
    }
    if (url.startsWith(mount + '/') || url.startsWith(mount + '?') || url.startsWith(mount + '#')) {
        const rest = url.slice(mount.length);
        return rest.startsWith('/') ? rest : '/' + rest;
    }
    return url;
};
