import {fireEvent, screen, waitFor} from '@testing-library/react';
import {describe, expect, it} from 'vitest';
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
    it('clicking Logs badge opens iframe with correct src', async () => {
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

        // Iframe should appear with the correct src containing LogCollector
        await waitFor(
            () => {
                const iframe = document.querySelector('iframe');
                expect(iframe).not.toBeNull();
                expect(iframe!.src).toContain('LogCollector');
                expect(iframe!.src).toContain('debugEntry=toolbar-entry-001');
                expect(iframe!.src).toContain('toolbar=0');
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
                expect(iframe!.src).toContain('EventCollector');
                expect(iframe!.src).toContain('debugEntry=toolbar-entry-001');
                expect(iframe!.src).toContain('toolbar=0');
            },
            {timeout: 3000},
        );
    });

    it('clicking badge when iframe is already open updates iframe src', async () => {
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

        // Default iframe src should be the debug panel without collector
        const iframe = document.querySelector('iframe')!;
        expect(iframe.src).toContain('/debug?toolbar=0');

        // Now click Logs badge — iframe src should update
        const logsBadge = screen.getByText('Logs 5');
        fireEvent.click(logsBadge);

        await waitFor(
            () => {
                const updatedIframe = document.querySelector('iframe')!;
                expect(updatedIframe.src).toContain('LogCollector');
                expect(updatedIframe.src).toContain('debugEntry=toolbar-entry-001');
            },
            {timeout: 3000},
        );
    });
});
