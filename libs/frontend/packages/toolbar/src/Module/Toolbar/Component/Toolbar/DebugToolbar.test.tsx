import {DebugEntry} from '@app-dev-panel/sdk/API/Debug/Debug';
import {CollectorsMap} from '@app-dev-panel/sdk/Helper/collectors';
import {renderWithProviders} from '@app-dev-panel/sdk/test-utils';
import {
    buildIframeSrc,
    DebugToolbar,
    readDebugIdFromServiceWorkerMessage,
} from '@app-dev-panel/toolbar/Module/Toolbar/Component/Toolbar/DebugToolbar';
import {fireEvent, screen} from '@testing-library/react';
import {afterEach, describe, expect, it, vi} from 'vitest';

// Popups pull in the LLM / inspector RTK Query APIs, which the lightweight
// test store does not carry. They are irrelevant to the navigation contract.
vi.mock('@app-dev-panel/toolbar/Module/Toolbar/Component/Toolbar/AiChatPopup', () => ({AiChatPopup: () => null}));
vi.mock('@app-dev-panel/toolbar/Module/Toolbar/Component/DebugEntriesListModal', () => ({
    DebugEntriesListModal: () => null,
}));

const BASE = 'http://127.0.0.1:8080';

describe('buildIframeSrc', () => {
    it('opens the panel root when no page was requested', () => {
        const url = new URL(buildIframeSrc(BASE, '/debug', null));
        expect(url.pathname).toBe('/debug');
        expect(url.searchParams.get('toolbar')).toBe('0');
    });

    it('does not prefix the mount twice for a collector page (issue #111)', () => {
        const page = '/debug/debug?collector=AppDevPanel%5CKernel%5CCollector%5CLogCollector&debugEntry=e1';
        const url = new URL(buildIframeSrc(BASE, '/debug', page));
        // First `/debug` is the mount, second the panel-internal collector route.
        expect(url.pathname).toBe('/debug/debug');
        expect(url.searchParams.get('collector')).toBe('AppDevPanel\\Kernel\\Collector\\LogCollector');
        expect(url.searchParams.get('debugEntry')).toBe('e1');
        expect(url.searchParams.get('toolbar')).toBe('0');
    });

    it('keeps a custom mount exactly once', () => {
        const url = new URL(buildIframeSrc(BASE, '/adp', '/adp/inspector/routes'));
        expect(url.pathname).toBe('/adp/inspector/routes');
    });

    it('resolves against the backend origin even with a path on the base URL', () => {
        const url = new URL(buildIframeSrc('http://app.test/some/page', '/debug', '/debug/debug?debugEntry=e1'));
        expect(url.origin).toBe('http://app.test');
        expect(url.pathname).toBe('/debug/debug');
    });
});

describe('readDebugIdFromServiceWorkerMessage', () => {
    it('extracts the x-debug-id header from a FETCH message', () => {
        const data = {type: 'FETCH', payload: {headers: {'x-debug-id': 'abc'}, url: '/x', method: 'GET', status: 200}};
        expect(readDebugIdFromServiceWorkerMessage(data)).toBe('abc');
    });

    it('returns null for null, primitive and header-less messages', () => {
        expect(readDebugIdFromServiceWorkerMessage(null)).toBeNull();
        expect(readDebugIdFromServiceWorkerMessage(undefined)).toBeNull();
        expect(readDebugIdFromServiceWorkerMessage('ping')).toBeNull();
        expect(readDebugIdFromServiceWorkerMessage({payload: null})).toBeNull();
        expect(readDebugIdFromServiceWorkerMessage({payload: {headers: {'content-type': 'x'}}})).toBeNull();
        expect(readDebugIdFromServiceWorkerMessage({payload: {headers: {'x-debug-id': ''}}})).toBeNull();
    });
});

describe('DebugToolbar without an embedded panel', () => {
    const originalOpen = window.open;

    afterEach(() => {
        window.open = originalOpen;
    });

    it('falls back to opening the page in the host window when the iframe is disabled', () => {
        const open = vi.fn();
        window.open = open;
        const entry = {
            id: 'entry-9',
            collectors: [],
            web: {request: {startTime: 0, processingTime: 0.01}, memory: {peakUsage: 1024}},
            request: {url: 'http://localhost/x', path: '/x', query: '', method: 'GET', isAjax: false, userIp: ''},
            response: {statusCode: 200},
            db: {queries: {total: 3, error: 0}},
        } as unknown as DebugEntry;

        renderWithProviders(<DebugToolbar activeComponents={{iframe: false}} />, {
            preloadedState: {
                application: {
                    baseUrl: BASE,
                    toolbarOpen: true,
                    toolbarPosition: 'bottom',
                    iframeHeight: 300,
                    favoriteUrls: [],
                    autoLatest: false,
                },
                'store.debug': {entry, currentPageRequestIds: []},
            },
        });

        fireEvent.click(screen.getByText('DB 3'));

        expect(open).toHaveBeenCalledTimes(1);
        const [url, target] = open.mock.calls[0] as [string, string];
        expect(target).toBe('_top');
        const parsed = new URL(url);
        expect(parsed.origin).toBe(BASE);
        expect(parsed.pathname).toBe('/debug/debug');
        expect(parsed.searchParams.get('collector')).toBe(CollectorsMap.DatabaseCollector);
        expect(parsed.searchParams.get('debugEntry')).toBe('entry-9');
        expect(document.querySelector('iframe')).toBeNull();
    });
});
