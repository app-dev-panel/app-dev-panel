import {afterEach, describe, expect, it} from 'vitest';
import {panelMountPath, panelPagePath} from './panelMountPath';

const setMount = (value: string | undefined) => {
    if (value === undefined) {
        delete window.__adp_panel_url;
    } else {
        window.__adp_panel_url = value;
    }
};

describe('panelMountPath', () => {
    afterEach(() => setMount(undefined));

    it('defaults to /debug when no global is set', () => {
        expect(panelMountPath()).toBe('/debug');
    });

    it('reads the mount path from window.__adp_panel_url', () => {
        setMount('/adp');
        expect(panelMountPath()).toBe('/adp');
    });

    it('strips trailing slashes from the configured mount path', () => {
        setMount('/adp///');
        expect(panelMountPath()).toBe('/adp');
    });
});

describe('panelPagePath', () => {
    afterEach(() => setMount(undefined));

    it('appends a bare query string directly to the mount path', () => {
        // /debug + ?foo=bar — must NOT become /debug/?foo=bar (404 against the adapter route).
        expect(panelPagePath('?foo=bar')).toBe('/debug?foo=bar');
    });

    it('appends a bare hash fragment directly to the mount path', () => {
        expect(panelPagePath('#section')).toBe('/debug#section');
    });

    it('returns just the mount path when input is empty', () => {
        expect(panelPagePath('')).toBe('/debug');
    });

    it('returns just the mount path when called with no arguments', () => {
        expect(panelPagePath()).toBe('/debug');
    });

    it('prepends a leading slash to sub-paths that lack one', () => {
        expect(panelPagePath('inspector/files?class=Foo')).toBe('/debug/inspector/files?class=Foo');
    });

    it('preserves an explicit leading slash on the sub-path', () => {
        expect(panelPagePath('/inspector/files?class=Foo')).toBe('/debug/inspector/files?class=Foo');
    });

    it('honors a custom mount path from window.__adp_panel_url', () => {
        setMount('/adp');
        expect(panelPagePath('?foo=1')).toBe('/adp?foo=1');
        expect(panelPagePath('/inspector/files')).toBe('/adp/inspector/files');
    });
});
