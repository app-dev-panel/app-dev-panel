import {MouseEvent} from 'react';
import {afterEach, describe, expect, it, vi} from 'vitest';
import {openInNewTabOnModifier} from './openInNewTabOnModifier';

const makeEvent = (modifiers: Partial<{ctrlKey: boolean; metaKey: boolean}> = {}) => {
    const event = {ctrlKey: false, metaKey: false, stopPropagation: vi.fn(), preventDefault: vi.fn(), ...modifiers};
    return event as unknown as MouseEvent;
};

describe('openInNewTabOnModifier', () => {
    const originalOpen = window.open;
    afterEach(() => {
        window.open = originalOpen;
    });

    it('returns false and leaves the event untouched when no modifier is pressed', () => {
        const open = vi.fn();
        window.open = open;
        const event = makeEvent();

        expect(openInNewTabOnModifier(event, '/debug?x=1')).toBe(false);
        expect(open).not.toHaveBeenCalled();
        expect(event.stopPropagation).not.toHaveBeenCalled();
        expect(event.preventDefault).not.toHaveBeenCalled();
    });

    it('opens a new tab and stops the event when Ctrl is held', () => {
        const open = vi.fn();
        window.open = open;
        const event = makeEvent({ctrlKey: true});

        expect(openInNewTabOnModifier(event, '/debug?x=1')).toBe(true);
        expect(open).toHaveBeenCalledWith('/debug?x=1', '_blank', 'noopener,noreferrer');
        expect(event.stopPropagation).toHaveBeenCalledTimes(1);
        expect(event.preventDefault).toHaveBeenCalledTimes(1);
    });

    it('opens a new tab and stops the event when Cmd (metaKey) is held', () => {
        const open = vi.fn();
        window.open = open;
        const event = makeEvent({metaKey: true});

        expect(openInNewTabOnModifier(event, '/debug?x=1')).toBe(true);
        expect(open).toHaveBeenCalledWith('/debug?x=1', '_blank', 'noopener,noreferrer');
        expect(event.stopPropagation).toHaveBeenCalledTimes(1);
        expect(event.preventDefault).toHaveBeenCalledTimes(1);
    });
});
