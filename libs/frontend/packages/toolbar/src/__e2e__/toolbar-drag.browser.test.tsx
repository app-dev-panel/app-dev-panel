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
 * Simulate a mouse drag on the document (matches useDrag implementation).
 * mouseDown on element, then mouseMoves and mouseUp on document.
 */
const simulateDrag = (element: HTMLElement, startX: number, startY: number, endX: number, endY: number) => {
    fireEvent.mouseDown(element, {clientX: startX, clientY: startY});
    const steps = 5;
    for (let i = 1; i <= steps; i++) {
        const t = i / steps;
        fireEvent.mouseMove(document, {clientX: startX + (endX - startX) * t, clientY: startY + (endY - startY) * t});
    }
    fireEvent.mouseUp(document, {clientX: endX, clientY: endY});
};

/** The docked bottom bar: the Paper that hosts the "Collapse toolbar" button. */
const getBottomBar = () => {
    const bar = screen.getByLabelText('Collapse toolbar').closest('.MuiPaper-root') as HTMLElement | null;
    expect(bar).not.toBeNull();
    return bar!;
};

/**
 * The floating card. Undocking swaps the whole widget tree (the bottom bar
 * unmounts, a float `Paper` mounts), so the bar element captured before the
 * drag is detached afterwards and reports a zero rect — always re-query.
 * `queryBy*` so it can be polled inside `waitFor`.
 */
const queryFloatWidget = () =>
    screen.queryByLabelText('Dock to bottom')?.closest('.MuiPaper-root') as HTMLElement | null | undefined;

describe('Toolbar Drag & Drop', () => {
    it('bottom bar can be dragged to undock into float mode', async () => {
        renderToolbar();
        await expandToolbar();

        const toolbar = getBottomBar();
        expect(toolbar.getBoundingClientRect().width).toBeGreaterThan(window.innerWidth * 0.8);

        // Drag from center of bar upward — should undock
        const rect = toolbar.getBoundingClientRect();
        const startX = rect.left + rect.width / 2;
        const startY = rect.top + rect.height / 2;

        simulateDrag(toolbar, startX, startY, 400, 300);

        // After drag, toolbar should be in float mode — no longer full-width
        await waitFor(
            () => {
                const widget = queryFloatWidget();
                expect(widget).toBeTruthy();
                const newRect = widget!.getBoundingClientRect();
                expect(newRect.width).toBeGreaterThan(0);
                expect(newRect.width).toBeLessThan(window.innerWidth * 0.8);
            },
            {timeout: 3000},
        );
        expect(screen.queryByLabelText('Collapse toolbar')).toBeNull();
    });

    it('undocked widget is positioned under the cursor, not at left edge', async () => {
        renderToolbar();
        await expandToolbar();

        const toolbar = getBottomBar();
        const rect = toolbar.getBoundingClientRect();
        const endX = 600;
        const endY = 400;
        simulateDrag(toolbar, rect.left + rect.width / 2, rect.top + rect.height / 2, endX, endY);

        await waitFor(
            () => {
                const widget = queryFloatWidget();
                expect(widget).toBeTruthy();
                const newRect = widget!.getBoundingClientRect();
                expect(newRect.width).toBeGreaterThan(0);
                // useDrag undocks a full-width bar by centering the float card
                // horizontally under the cursor and grabbing it 20px below its
                // top edge — so the card must land near the drop point, not at
                // the left edge where the bar used to start.
                expect(newRect.left).toBeGreaterThan(200);
                expect(Math.abs(newRect.left + newRect.width / 2 - endX)).toBeLessThanOrEqual(1);
                expect(Math.abs(newRect.top + 20 - endY)).toBeLessThanOrEqual(1);
            },
            {timeout: 3000},
        );
    });

    it('AI chat popup can be opened and shows entry info', async () => {
        renderToolbar();
        await expandToolbar();

        // Wait for AI Chat button
        await waitFor(() => {
            expect(screen.getByLabelText('AI Chat')).toBeInTheDocument();
        });

        fireEvent.click(screen.getByLabelText('AI Chat'));

        await waitFor(() => {
            expect(screen.getByText('ADP Duck AI')).toBeInTheDocument();
        });

        // Should have suggestion chips
        expect(screen.getByText('Show queries')).toBeInTheDocument();
    });
});
