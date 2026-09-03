import {dispatchWindowEvent} from '@app-dev-panel/sdk/Helper/dispatchWindowEvent';
import {act, fireEvent, screen, waitFor} from '@testing-library/react';
import {beforeEach, describe, expect, it, vi} from 'vitest';
import {renderToolbar} from './renderToolbar';
import './setup';

// The embedded panel is navigated through `postMessage` once it has booted
// (the iframe `src` is locked at mount time so the panel never reloads). The
// iframe points at the mocked backend origin, which is cross-origin from the
// test page, so its `contentWindow.postMessage` cannot be spied on directly —
// mock the tiny helper the toolbar routes every message through instead.
vi.mock('@app-dev-panel/sdk/Helper/dispatchWindowEvent', () => ({dispatchWindowEvent: vi.fn()}));

const BACKEND_ORIGIN = 'http://127.0.0.1:8080';

const expandToolbar = async () => {
    await waitFor(
        () => {
            const pill = screen.queryByLabelText('Open debug toolbar');
            const toolbar = screen.queryByLabelText('Collapse toolbar');
            expect(pill || toolbar).not.toBeNull();
        },
        {timeout: 5000},
    );
    const pill = screen.queryByLabelText('Open debug toolbar');
    if (pill) {
        fireEvent.click(pill);
    }
    await waitFor(
        () => {
            expect(screen.getByLabelText('Collapse toolbar')).toBeInTheDocument();
        },
        {timeout: 3000},
    );
};

const waitForBadges = async () => {
    await waitFor(
        () => {
            expect(screen.getByText('GET /api/test 200')).toBeInTheDocument();
        },
        {timeout: 5000},
    );
};

describe('Toolbar Badge Navigation', () => {
    beforeEach(() => {
        vi.mocked(dispatchWindowEvent).mockClear();
    });

    it('clicking Logs badge opens iframe with correct src', async () => {
        renderToolbar();
        await expandToolbar();
        await waitForBadges();

        // LogsItem now renders its label as split spans ("Logs" + segmented counts), so the
        // text isn't a single "Logs 5" node — use the aria-label that the tooltip injects.
        const logsBadge = screen.getByLabelText('5 log entries');
        expect(logsBadge).toBeInTheDocument();

        // No iframe before clicking
        expect(document.querySelector('iframe')).toBeNull();

        // Click the Logs badge
        fireEvent.click(logsBadge);

        // Iframe should appear with the correct src containing LogCollector
        await waitFor(
            () => {
                const iframe = document.querySelector('iframe');
                expect(iframe).not.toBeNull();
                const url = new URL(iframe!.src);
                // `{mount}/debug` — the mount (`/debug` by default) followed by the
                // panel-internal collector route; never `/debug/debug/debug` (#111).
                expect(url.pathname).toBe('/debug/debug');
                expect(url.searchParams.get('collector')).toBe('AppDevPanel\\Kernel\\Collector\\LogCollector');
                expect(url.searchParams.get('debugEntry')).toBe('toolbar-entry-001');
                expect(url.searchParams.get('toolbar')).toBe('0');
            },
            {timeout: 3000},
        );
    });

    it('clicking Events badge opens iframe with EventCollector src', async () => {
        renderToolbar();
        await expandToolbar();
        await waitForBadges();

        const eventsBadge = screen.getByText('Events 12');
        expect(eventsBadge).toBeInTheDocument();

        fireEvent.click(eventsBadge);

        await waitFor(
            () => {
                const iframe = document.querySelector('iframe');
                expect(iframe).not.toBeNull();
                const url = new URL(iframe!.src);
                expect(url.pathname).toBe('/debug/debug');
                expect(url.searchParams.get('collector')).toBe('AppDevPanel\\Kernel\\Collector\\EventCollector');
                expect(url.searchParams.get('debugEntry')).toBe('toolbar-entry-001');
                expect(url.searchParams.get('toolbar')).toBe('0');
            },
            {timeout: 3000},
        );
    });

    it('clicking badges while the panel is open navigates via postMessage without reloading the iframe', async () => {
        renderToolbar();
        await expandToolbar();
        await waitForBadges();

        // Open the panel without a target page: the iframe boots at the panel root.
        fireEvent.click(screen.getByLabelText('Open panel'));
        await waitFor(
            () => {
                expect(document.querySelector('iframe')).not.toBeNull();
            },
            {timeout: 3000},
        );
        const iframe = document.querySelector('iframe')!;
        const initialSrc = iframe.src;
        expect(new URL(initialSrc).pathname).toBe('/debug');
        expect(new URL(initialSrc).searchParams.get('toolbar')).toBe('0');

        // Panel is mounted but has not signalled `panel.loaded` yet: the click is
        // queued, the locked src is untouched and nothing is posted.
        fireEvent.click(screen.getByLabelText('5 log entries'));
        expect(iframe.src).toBe(initialSrc);
        expect(dispatchWindowEvent).not.toHaveBeenCalled();

        // The panel reports it has booted (only messages whose `source` is our
        // iframe's window are trusted). The queued navigation is flushed.
        act(() => {
            window.dispatchEvent(
                new MessageEvent('message', {data: {event: 'panel.loaded', value: true}, source: iframe.contentWindow}),
            );
        });
        await waitFor(
            () => {
                expect(dispatchWindowEvent).toHaveBeenCalledTimes(1);
            },
            {timeout: 3000},
        );
        const [logsTarget, logsEvent, logsUrl] = vi.mocked(dispatchWindowEvent).mock.calls[0];
        expect(logsTarget).toBe(iframe.contentWindow);
        expect(logsEvent).toBe('router.navigate');
        const logsPageUrl = new URL(String(logsUrl), BACKEND_ORIGIN);
        expect(logsPageUrl.pathname).toBe('/debug/debug');
        expect(logsPageUrl.searchParams.get('collector')).toBe('AppDevPanel\\Kernel\\Collector\\LogCollector');
        expect(logsPageUrl.searchParams.get('debugEntry')).toBe('toolbar-entry-001');

        // Hot path: the panel is ready, so the next badge posts immediately.
        fireEvent.click(screen.getByText('Events 12'));
        await waitFor(
            () => {
                expect(dispatchWindowEvent).toHaveBeenCalledTimes(2);
            },
            {timeout: 3000},
        );
        const [eventsTarget, eventsEvent, eventsUrl] = vi.mocked(dispatchWindowEvent).mock.calls[1];
        expect(eventsTarget).toBe(iframe.contentWindow);
        expect(eventsEvent).toBe('router.navigate');
        const eventsPageUrl = new URL(String(eventsUrl), BACKEND_ORIGIN);
        expect(eventsPageUrl.pathname).toBe('/debug/debug');
        expect(eventsPageUrl.searchParams.get('collector')).toBe('AppDevPanel\\Kernel\\Collector\\EventCollector');
        expect(eventsPageUrl.searchParams.get('debugEntry')).toBe('toolbar-entry-001');

        // Same iframe element, same src: the panel was never reloaded.
        expect(document.querySelector('iframe')).toBe(iframe);
        expect(iframe.src).toBe(initialSrc);
    });
});
