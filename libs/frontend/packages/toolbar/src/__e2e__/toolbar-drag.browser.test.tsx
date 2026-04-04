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

describe('Toolbar Drag & Drop', () => {
    it('bottom bar can be dragged to undock into float mode', async () => {
        renderToolbar();
        await expandToolbar();

        // Find the toolbar Paper (bottom bar)
        const collapseBtn = screen.getByLabelText('Collapse toolbar');
        const toolbar = collapseBtn.closest('[class*="MuiPaper"]') as HTMLElement;
        expect(toolbar).not.toBeNull();

        // Drag from center of bar upward — should undock
        const rect = toolbar.getBoundingClientRect();
        const startX = rect.left + rect.width / 2;
        const startY = rect.top + rect.height / 2;

        simulateDrag(toolbar, startX, startY, 400, 300);

        // After drag, toolbar should be in float mode — no longer full-width
        await waitFor(
            () => {
                const newRect = toolbar.getBoundingClientRect();
                expect(newRect.width).toBeLessThan(window.innerWidth * 0.8);
            },
            {timeout: 3000},
        );
    });

    it('undocked widget is positioned under the cursor, not at left edge', async () => {
        renderToolbar();
        await expandToolbar();

        const collapseBtn = screen.getByLabelText('Collapse toolbar');
        const toolbar = collapseBtn.closest('[class*="MuiPaper"]') as HTMLElement;
        expect(toolbar).not.toBeNull();

        const rect = toolbar.getBoundingClientRect();
        // Drag from center to 600, 400
        simulateDrag(toolbar, rect.left + rect.width / 2, rect.top + rect.height / 2, 600, 400);

        await waitFor(
            () => {
                const newRect = toolbar.getBoundingClientRect();
                // Widget should be near the drop point (600, 400), not at x=0
                expect(newRect.left).toBeGreaterThan(200);
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
            expect(screen.getByText('Debug Duck')).toBeInTheDocument();
        });

        // Should have suggestion chips
        expect(screen.getByText('Show queries')).toBeInTheDocument();
    });
});
