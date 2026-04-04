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

/**
 * Simulate a mouse drag sequence using mousedown/mousemove/mouseup on document.
 * This matches how useDrag works (document-level listeners).
 */
const _simulateMouseDrag = (element: HTMLElement, startX: number, startY: number, endX: number, endY: number) => {
    fireEvent.mouseDown(element, {clientX: startX, clientY: startY});
    const steps = 5;
    const dx = (endX - startX) / steps;
    const dy = (endY - startY) / steps;
    for (let i = 1; i <= steps; i++) {
        fireEvent.mouseMove(document, {clientX: startX + dx * i, clientY: startY + dy * i});
    }
    fireEvent.mouseUp(document, {clientX: endX, clientY: endY});
};

describe('Toolbar Snap Modes', () => {
    it('collapsed pill shows status code and response time', async () => {
        renderToolbar();
        await waitFor(
            () => {
                expect(screen.getByLabelText('Open debug toolbar')).toBeInTheDocument();
            },
            {timeout: 5000},
        );

        const pill = screen.getByLabelText('Open debug toolbar');
        // Should show status code
        expect(pill.textContent).toContain('200');
        // Should show response time (42ms from mock data)
        expect(pill.textContent).toContain('ms');
    });

    it('bottom mode shows single-row bar with metric items', async () => {
        renderToolbar();
        await expandToolbar();

        // Should have collapse button and metrics in a row
        expect(screen.getByLabelText('Collapse toolbar')).toBeInTheDocument();
        // Action buttons should be present
        expect(screen.getByLabelText('List debug entries')).toBeInTheDocument();
        expect(screen.getByLabelText('Open debug panel')).toBeInTheDocument();
    });

    it('can open AI chat popup', async () => {
        renderToolbar();
        await expandToolbar();

        const chatBtn = screen.getByLabelText('AI Chat');
        expect(chatBtn).toBeInTheDocument();
        fireEvent.click(chatBtn);

        await waitFor(() => {
            expect(screen.getByText('Debug Duck')).toBeInTheDocument();
        });

        // Chat should have input and send button
        expect(screen.getByPlaceholderText('Ask the duck...')).toBeInTheDocument();
    });

    it('can close AI chat popup', async () => {
        renderToolbar();
        await expandToolbar();

        fireEvent.click(screen.getByLabelText('AI Chat'));
        await waitFor(() => {
            expect(screen.getByText('Debug Duck')).toBeInTheDocument();
        });

        // Close chat
        fireEvent.click(screen.getByLabelText('AI Chat'));
        await waitFor(() => {
            expect(screen.queryByText('Debug Duck')).toBeNull();
        });
    });

    it('can open debug entries modal', async () => {
        renderToolbar();
        await expandToolbar();

        fireEvent.click(screen.getByLabelText('List debug entries'));

        await waitFor(() => {
            expect(screen.getByText('Debug Entries')).toBeInTheDocument();
        });

        // Should show search input
        expect(screen.getByPlaceholderText('Filter by path, method, status...')).toBeInTheDocument();
    });

    it('debug entries modal shows entries with method and path', async () => {
        renderToolbar();
        await expandToolbar();

        fireEvent.click(screen.getByLabelText('List debug entries'));

        await waitFor(() => {
            expect(screen.getByText('/api/test')).toBeInTheDocument();
        });

        expect(screen.getByText('/api/users')).toBeInTheDocument();
    });

    it('can send a message in AI chat', async () => {
        renderToolbar();
        await expandToolbar();

        fireEvent.click(screen.getByLabelText('AI Chat'));
        await waitFor(() => {
            expect(screen.getByPlaceholderText('Ask the duck...')).toBeInTheDocument();
        });

        const input = screen.getByPlaceholderText('Ask the duck...');
        fireEvent.change(input, {target: {value: 'hello'}});
        fireEvent.keyDown(input, {key: 'Enter'});

        await waitFor(() => {
            expect(screen.getByText('hello')).toBeInTheDocument();
        });
    });

    it('AI chat shows entry summary when opened', async () => {
        renderToolbar();
        await expandToolbar();

        fireEvent.click(screen.getByLabelText('AI Chat'));
        await waitFor(() => {
            expect(screen.getByText('Debug Duck')).toBeInTheDocument();
        });

        // Should show request summary from the mock entry
        await waitFor(() => {
            const msgs = document.querySelectorAll('[class*="MuiBox"]');
            const hasRequestInfo = Array.from(msgs).some(
                (el) => el.textContent?.includes('GET') && el.textContent?.includes('/api/test'),
            );
            expect(hasRequestInfo).toBe(true);
        });
    });
});
