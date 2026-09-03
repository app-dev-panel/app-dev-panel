import {readFileSync} from 'node:fs';
import {resolve} from 'node:path';
import {describe, expect, it} from 'vitest';

/**
 * Issue #113 — the panel must load from `/debug`, `/debug/`, deep links such as
 * `/debug/inspector/routes`, and from `/`. The bundle is built with a relative
 * Vite base, so `index.html` must not contain a single root-absolute asset
 * reference and must carry the `<base>` placeholder servers rewrite.
 */
const html = readFileSync(resolve(__dirname, '../index.html'), 'utf8');
const doc = new DOMParser().parseFromString(html, 'text/html');

const urlAttributes = (selector: string, attribute: string): string[] =>
    Array.from(doc.querySelectorAll(selector))
        .map((el) => el.getAttribute(attribute))
        .filter((value): value is string => typeof value === 'string');

describe('packages/panel/index.html', () => {
    it('carries the <base> placeholder marker as the first URL-bearing element', () => {
        const base = doc.querySelector('base[data-adp-base]');
        expect(base).not.toBeNull();
        expect(base!.getAttribute('href')).toBe('./');

        const firstUrlBearing = doc.head.querySelector('base, link, script[src]');
        expect(firstUrlBearing).toBe(base);
    });

    it('has no root-absolute link hrefs', () => {
        const hrefs = urlAttributes('link[href]', 'href');
        expect(hrefs.length).toBeGreaterThan(0);
        expect(hrefs.filter((href) => href.startsWith('/'))).toEqual([]);
    });

    it('has no root-absolute script sources', () => {
        const sources = urlAttributes('script[src]', 'src');
        expect(sources.length).toBeGreaterThan(0);
        expect(sources.filter((src) => src.startsWith('/'))).toEqual([]);
    });

    it('references favicon and manifest relative to the mount directory', () => {
        expect(doc.querySelector('link[rel="manifest"]')!.getAttribute('href')).toBe('manifest.json');
        expect(doc.querySelector('link[rel="icon"]')!.getAttribute('href')).toBe('favicon.ico');
    });

    it('runs the self-healing bootstrap before the module entry point', () => {
        const scripts = Array.from(doc.querySelectorAll('script'));
        const bootstrapIndex = scripts.findIndex((el) => (el.textContent ?? '').includes('base[data-adp-base]'));
        const moduleIndex = scripts.findIndex((el) => el.getAttribute('type') === 'module');
        expect(bootstrapIndex).toBeGreaterThanOrEqual(0);
        expect(moduleIndex).toBeGreaterThan(bootstrapIndex);
    });

    describe('self-healing bootstrap', () => {
        const bootstrap = Array.from(doc.querySelectorAll('script')).find((el) =>
            (el.textContent ?? '').includes('base[data-adp-base]'),
        )!.textContent!;

        const run = (pathname: string, placeholder = './'): string => {
            document.querySelectorAll('base').forEach((el) => el.remove());
            const base = document.createElement('base');
            base.setAttribute('href', placeholder);
            base.setAttribute('data-adp-base', '');
            document.head.prepend(base);
            const location = {pathname};
            new Function('window', 'document', bootstrap)({location}, document);
            const href = base.getAttribute('href')!;
            base.remove();
            return href;
        };

        it('maps a mount without trailing slash to its directory', () => {
            expect(run('/debug')).toBe('/debug/');
        });

        it('keeps a mount with trailing slash', () => {
            expect(run('/debug/')).toBe('/debug/');
        });

        it('strips an explicit index.html', () => {
            expect(run('/bundles/appdevpanel/index.html')).toBe('/bundles/appdevpanel/');
        });

        it('maps the root', () => {
            expect(run('/')).toBe('/');
        });

        it('leaves a server-rewritten base untouched', () => {
            expect(run('/debug/inspector/routes', '/debug/')).toBe('/debug/');
        });
    });
});
