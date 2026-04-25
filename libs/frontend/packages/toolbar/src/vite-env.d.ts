/// <reference types="vite/client" />

declare module '@fontsource/*';

/**
 * Global `Window` augmentations for ADP.
 *
 * Set by `ToolbarInjector` (PHP) before the toolbar/panel bundles load.
 * `interface` is required here — only interfaces merge into the global
 * `Window` declaration; a `type` alias would shadow it instead.
 */
/* eslint-disable @typescript-eslint/consistent-type-definitions */
interface Window {
    /**
     * Path under the host origin where the debug panel SPA is mounted.
     * Sourced from `PanelConfig::viewerBasePath`. Defaults to `/debug` when absent.
     */
    __adp_panel_url?: string;
}
