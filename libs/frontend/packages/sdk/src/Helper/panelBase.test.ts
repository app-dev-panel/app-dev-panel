import {afterEach, describe, expect, it} from 'vitest';
import {basenameFromDocument, stripBasename} from './panelBase';

const setBase = (href: string | null) => {
    document.querySelectorAll('base').forEach((el) => el.remove());
    if (href !== null) {
        const base = document.createElement('base');
        base.setAttribute('href', href);
        document.head.prepend(base);
    }
};

describe('basenameFromDocument', () => {
    afterEach(() => setBase(null));

    it('returns an empty basename when the panel is mounted at the root', () => {
        setBase('/');
        expect(basenameFromDocument()).toBe('');
    });

    it('returns an empty basename without an explicit <base>', () => {
        // jsdom default URL is http://localhost:3000/ — baseURI pathname is "/".
        expect(basenameFromDocument()).toBe('');
    });

    it('strips the trailing slash from the mount directory', () => {
        setBase('/debug/');
        expect(basenameFromDocument()).toBe('/debug');
    });

    it('supports nested mounts and absolute base URLs', () => {
        setBase('http://example.test/tools/adp/');
        expect(basenameFromDocument()).toBe('/tools/adp');
    });
});

describe('stripBasename', () => {
    it('removes the mount prefix from a collector URL (double /debug is intended)', () => {
        expect(stripBasename('/debug/debug?collector=X&debugEntry=1', '/debug')).toBe(
            '/debug?collector=X&debugEntry=1',
        );
    });

    it('handles a custom mount', () => {
        expect(stripBasename('/adp/inspector/routes', '/adp')).toBe('/inspector/routes');
    });

    it('maps the bare mount to the panel root', () => {
        expect(stripBasename('/debug', '/debug')).toBe('/');
        expect(stripBasename('/debug?debugEntry=1', '/debug')).toBe('/?debugEntry=1');
    });

    it('ignores a trailing slash on the basename', () => {
        expect(stripBasename('/debug/llm', '/debug/')).toBe('/llm');
    });

    it('returns the URL unchanged for an empty basename', () => {
        expect(stripBasename('/debug/debug?collector=X', '')).toBe('/debug/debug?collector=X');
    });

    it('does not strip a prefix that only shares characters with the mount', () => {
        expect(stripBasename('/debugger/x', '/debug')).toBe('/debugger/x');
    });
});
