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

const openIframePanel = async () => {
    const toggleBtn = screen.getByLabelText('Toggle debug panel');
    fireEvent.click(toggleBtn);
    await waitFor(
        () => {
            expect(document.querySelector('iframe')).not.toBeNull();
        },
        {timeout: 3000},
    );
};

const getResizeHandle = (): HTMLElement | null => {
    // react-resizable-layout renders a separator with role="separator" and aria-orientation="horizontal"
    // (horizontal orientation for y-axis resizing)
    const separator = document.querySelector('[role="separator"][aria-orientation="horizontal"]');
    if (separator) {
        return separator as HTMLElement;
    }
    return null;
};

const getIframeContainerHeight = (): number => {
    const iframe = document.querySelector('iframe');
    if (!iframe) return NaN;
    const container = iframe.parentElement;
    if (!container) return NaN;
    // Try inline style first, then computed style
    const inlineHeight = container.style.height;
    if (inlineHeight) {
        return parseInt(inlineHeight, 10);
    }
    return container.getBoundingClientRect().height;
};

/**
 * Simulate a full pointer drag sequence on an element.
 * react-resizable-layout uses pointer events with document-level listeners.
 */
const simulateDrag = (element: HTMLElement, startY: number, endY: number) => {
    const startX = 500;

    fireEvent.pointerDown(element, {clientX: startX, clientY: startY, pointerId: 1});
    // Move in small steps to simulate real drag
    const steps = 5;
    const deltaY = (endY - startY) / steps;
    for (let i = 1; i <= steps; i++) {
        fireEvent.pointerMove(document, {clientX: startX, clientY: startY + deltaY * i, pointerId: 1});
    }
    fireEvent.pointerUp(document, {clientX: startX, clientY: endY, pointerId: 1});
};

describe('Toolbar Resize', () => {
    it('shows resize handle when iframe panel is open', async () => {
        renderToolbar();
        await expandToolbar();

        // Before opening panel, no resize handle
        expect(getResizeHandle()).toBeNull();

        await openIframePanel();

        // After opening panel, resize handle should appear
        await waitFor(() => {
            const handle = getResizeHandle();
            expect(handle).not.toBeNull();
        });
    });

    it('iframe panel has initial height from store', async () => {
        renderToolbar();
        await expandToolbar();
        await openIframePanel();

        await waitFor(() => {
            const height = getIframeContainerHeight();
            expect(height).toBeGreaterThanOrEqual(100);
            expect(height).toBeLessThanOrEqual(1000);
        });
    });

    it('dragging resize handle up increases iframe height', async () => {
        renderToolbar();
        await expandToolbar();
        await openIframePanel();

        await waitFor(() => {
            expect(getResizeHandle()).not.toBeNull();
        });

        const handle = getResizeHandle()!;
        const initialHeight = getIframeContainerHeight();

        const handleRect = handle.getBoundingClientRect();
        const startY = handleRect.top + handleRect.height / 2;

        // Drag UP (negative Y direction) should increase height with reverse:true
        simulateDrag(handle, startY, startY - 150);

        await waitFor(
            () => {
                const newHeight = getIframeContainerHeight();
                expect(newHeight).toBeGreaterThan(initialHeight);
            },
            {timeout: 3000},
        );
    });

    it('dragging resize handle down decreases iframe height', async () => {
        renderToolbar();
        await expandToolbar();
        await openIframePanel();

        await waitFor(() => {
            expect(getResizeHandle()).not.toBeNull();
        });

        const handle = getResizeHandle()!;

        // First drag up to have room to shrink
        const handleRect = handle.getBoundingClientRect();
        const startY = handleRect.top + handleRect.height / 2;
        simulateDrag(handle, startY, startY - 200);

        await waitFor(
            () => {
                const h = getIframeContainerHeight();
                expect(h).toBeGreaterThan(200);
            },
            {timeout: 3000},
        );

        const expandedHeight = getIframeContainerHeight();

        // Now drag DOWN to shrink
        const newHandleRect = handle.getBoundingClientRect();
        const newStartY = newHandleRect.top + newHandleRect.height / 2;
        simulateDrag(handle, newStartY, newStartY + 150);

        await waitFor(
            () => {
                const newHeight = getIframeContainerHeight();
                expect(newHeight).toBeLessThan(expandedHeight);
            },
            {timeout: 3000},
        );
    });

    it('respects minimum height constraint (100px)', async () => {
        renderToolbar();
        await expandToolbar();
        await openIframePanel();

        await waitFor(() => {
            expect(getResizeHandle()).not.toBeNull();
        });

        const handle = getResizeHandle()!;

        // Drag DOWN aggressively to try to go below min
        const handleRect = handle.getBoundingClientRect();
        const startY = handleRect.top + handleRect.height / 2;
        simulateDrag(handle, startY, startY + 2000);

        await waitFor(
            () => {
                const height = getIframeContainerHeight();
                expect(height).toBeGreaterThanOrEqual(100);
            },
            {timeout: 3000},
        );
    });

    it('respects maximum height constraint (1000px)', async () => {
        renderToolbar();
        await expandToolbar();
        await openIframePanel();

        await waitFor(() => {
            expect(getResizeHandle()).not.toBeNull();
        });

        const handle = getResizeHandle()!;

        // Drag UP aggressively to try to exceed max
        const handleRect = handle.getBoundingClientRect();
        const startY = handleRect.top + handleRect.height / 2;
        simulateDrag(handle, startY, startY - 2000);

        await waitFor(
            () => {
                const height = getIframeContainerHeight();
                expect(height).toBeLessThanOrEqual(1000);
            },
            {timeout: 3000},
        );
    });

    it('hides resize handle when iframe panel is closed', async () => {
        renderToolbar();
        await expandToolbar();
        await openIframePanel();

        await waitFor(() => {
            expect(getResizeHandle()).not.toBeNull();
        });

        // Close the panel
        const toggleBtn = screen.getByLabelText('Toggle debug panel');
        fireEvent.click(toggleBtn);

        await waitFor(() => {
            expect(document.querySelector('iframe')).toBeNull();
            expect(getResizeHandle()).toBeNull();
        });
    });

    it('resize handle has correct cursor style', async () => {
        renderToolbar();
        await expandToolbar();
        await openIframePanel();

        await waitFor(() => {
            const handle = getResizeHandle();
            expect(handle).not.toBeNull();
        });

        const handle = getResizeHandle()!;
        const style = window.getComputedStyle(handle);
        expect(style.cursor).toBe('row-resize');
    });

    it('resize handle has correct ARIA attributes', async () => {
        renderToolbar();
        await expandToolbar();
        await openIframePanel();

        await waitFor(() => {
            const handle = getResizeHandle();
            expect(handle).not.toBeNull();
        });

        const handle = getResizeHandle()!;
        expect(handle.getAttribute('role')).toBe('separator');
        expect(handle.getAttribute('aria-valuemin')).toBe('100');
        expect(handle.getAttribute('aria-valuemax')).toBe('1000');
    });
});
