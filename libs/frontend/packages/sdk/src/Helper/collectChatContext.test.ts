import {afterEach, beforeEach, describe, expect, it, vi} from 'vitest';
import {collectChatContext} from './collectChatContext';

describe('collectChatContext', () => {
    beforeEach(() => {
        window.history.replaceState({}, '', '/debug?collector=log&debugEntry=abc123');
        document.title = 'ADP Test';
    });

    afterEach(() => {
        vi.restoreAllMocks();
    });

    it('collects url, user agent, language, viewport, screen and title in JSDOM', () => {
        const ctx = collectChatContext();

        expect(ctx.url).toBe(window.location.href);
        expect(ctx.url).toContain('collector=log');
        expect(ctx.url).toContain('debugEntry=abc123');
        expect(ctx.userAgent).toBe(window.navigator.userAgent);
        expect(ctx.language).toBe(window.navigator.language);
        expect(ctx.title).toBe('ADP Test');
        expect(ctx.viewport).toEqual({width: window.innerWidth, height: window.innerHeight});
        expect(ctx.screen).toEqual({
            width: window.screen.width,
            height: window.screen.height,
            devicePixelRatio: window.devicePixelRatio,
        });
    });

    it('includes a resolved IANA timezone', () => {
        const ctx = collectChatContext();
        expect(typeof ctx.timezone).toBe('string');
        expect(ctx.timezone).not.toBe('');
    });

    it('reports light theme when prefers-color-scheme: dark does not match', () => {
        vi.spyOn(window, 'matchMedia').mockImplementation((query: string) => ({
            matches: false,
            media: query,
            onchange: null,
            addListener: vi.fn(),
            removeListener: vi.fn(),
            addEventListener: vi.fn(),
            removeEventListener: vi.fn(),
            dispatchEvent: vi.fn(),
        }));

        expect(collectChatContext().theme).toBe('light');
    });

    it('reports dark theme when prefers-color-scheme: dark matches', () => {
        vi.spyOn(window, 'matchMedia').mockImplementation((query: string) => ({
            matches: query.includes('dark'),
            media: query,
            onchange: null,
            addListener: vi.fn(),
            removeListener: vi.fn(),
            addEventListener: vi.fn(),
            removeEventListener: vi.fn(),
            dispatchEvent: vi.fn(),
        }));

        expect(collectChatContext().theme).toBe('dark');
    });

    it('omits referrer when document.referrer is empty', () => {
        Object.defineProperty(document, 'referrer', {configurable: true, value: ''});
        expect(collectChatContext().referrer).toBeUndefined();
    });

    it('includes referrer when document.referrer is set', () => {
        Object.defineProperty(document, 'referrer', {configurable: true, value: 'https://example.com/prev'});
        expect(collectChatContext().referrer).toBe('https://example.com/prev');
    });

    it('returns an empty object when window is not defined (SSR)', async () => {
        vi.stubGlobal('window', undefined);
        expect(collectChatContext()).toEqual({});
    });
});
