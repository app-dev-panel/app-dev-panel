import {fireEvent, screen, waitFor} from '@testing-library/react';
import {describe, expect, it, vi} from 'vitest';
import {renderToolbar} from './renderToolbar';
import './setup';

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
    it('clicking Logs badge opens iframe and sends navigation message', async () => {
        renderToolbar();
        await expandToolbar();
        await waitForBadges();

        // Verify the Logs badge is visible (mock data has logger.total: 5)
        const logsBadge = screen.getByText('Logs 5');
        expect(logsBadge).toBeInTheDocument();

        // No iframe before clicking
        expect(document.querySelector('iframe')).toBeNull();

        // Click the Logs badge
        fireEvent.click(logsBadge);

        // Iframe should appear
        await waitFor(
            () => {
                const iframe = document.querySelector('iframe');
                expect(iframe).not.toBeNull();
            },
            {timeout: 3000},
        );

        // Spy on postMessage to verify the navigation message will be sent
        const iframe = document.querySelector('iframe')!;

        // The iframe's src should point to the debug panel
        expect(iframe.src).toContain('/debug?toolbar=0');

        // Simulate the panel sending 'panel.loaded' — in real usage, the panel
        // sends this on mount; here we trigger it manually since the iframe
        // src won't actually load in the test environment
        const postMessageSpy = vi.fn();
        Object.defineProperty(iframe, 'contentWindow', {value: {postMessage: postMessageSpy}, writable: true});

        // Fire panel.loaded from the "iframe"
        window.dispatchEvent(
            new MessageEvent('message', {data: {event: 'panel.loaded', value: true}, origin: window.location.origin}),
        );

        // The pending navigation should be drained via postMessage
        await waitFor(
            () => {
                expect(postMessageSpy).toHaveBeenCalledWith(
                    expect.objectContaining({event: 'router.navigate', value: expect.stringContaining('collector=')}),
                    '*',
                );
            },
            {timeout: 3000},
        );

        // Verify the URL contains the LogCollector
        const call = postMessageSpy.mock.calls[0];
        const sentUrl = call[0].value as string;
        expect(sentUrl).toContain('LogCollector');
        expect(sentUrl).toContain('debugEntry=toolbar-entry-001');
    });

    it('clicking Events badge opens iframe and sends navigation to EventCollector', async () => {
        renderToolbar();
        await expandToolbar();
        await waitForBadges();

        const eventsBadge = screen.getByText('Events 12');
        expect(eventsBadge).toBeInTheDocument();

        fireEvent.click(eventsBadge);

        await waitFor(
            () => {
                expect(document.querySelector('iframe')).not.toBeNull();
            },
            {timeout: 3000},
        );

        const iframe = document.querySelector('iframe')!;
        const postMessageSpy = vi.fn();
        Object.defineProperty(iframe, 'contentWindow', {value: {postMessage: postMessageSpy}, writable: true});

        window.dispatchEvent(new MessageEvent('message', {data: {event: 'panel.loaded', value: true}}));

        await waitFor(
            () => {
                expect(postMessageSpy).toHaveBeenCalledWith(
                    expect.objectContaining({
                        event: 'router.navigate',
                        value: expect.stringContaining('EventCollector'),
                    }),
                    '*',
                );
            },
            {timeout: 3000},
        );
    });

    it('clicking badge when iframe is already open dispatches immediately', async () => {
        renderToolbar();
        await expandToolbar();
        await waitForBadges();

        // Open iframe via Toggle button first
        const toggleBtn = screen.getByLabelText('Toggle debug panel');
        fireEvent.click(toggleBtn);

        await waitFor(
            () => {
                expect(document.querySelector('iframe')).not.toBeNull();
            },
            {timeout: 3000},
        );

        const iframe = document.querySelector('iframe')!;
        const postMessageSpy = vi.fn();
        Object.defineProperty(iframe, 'contentWindow', {value: {postMessage: postMessageSpy}, writable: true});

        // Signal panel ready
        window.dispatchEvent(new MessageEvent('message', {data: {event: 'panel.loaded', value: true}}));

        // Now click Logs badge — iframe already open and ready
        const logsBadge = screen.getByText('Logs 5');
        fireEvent.click(logsBadge);

        // Should dispatch immediately (no queuing needed)
        await waitFor(
            () => {
                expect(postMessageSpy).toHaveBeenCalledWith(
                    expect.objectContaining({event: 'router.navigate', value: expect.stringContaining('LogCollector')}),
                    '*',
                );
            },
            {timeout: 3000},
        );
    });
});
