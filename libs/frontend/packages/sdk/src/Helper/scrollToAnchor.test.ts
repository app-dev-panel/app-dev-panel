import {afterEach, beforeEach, describe, expect, it, vi} from 'vitest';
import {scrollToAnchor} from './scrollToAnchor';

describe('scrollToAnchor', () => {
    beforeEach(() => {
        vi.useFakeTimers();
        vi.spyOn(window, 'scrollTo').mockImplementation(() => {});
    });

    afterEach(() => {
        vi.useRealTimers();
        vi.restoreAllMocks();
    });

    it('scrolls to element matching the provided anchor', () => {
        const element = {offsetTop: 600} as HTMLElement;
        vi.spyOn(document, 'getElementById').mockReturnValue(element);

        scrollToAnchor(100, 'my-section');
        vi.runAllTimers();

        expect(document.getElementById).toHaveBeenCalledWith('my-section');
        expect(window.scrollTo).toHaveBeenCalledWith({top: 500, behavior: 'smooth'});
    });

    it('uses window.location.hash when no anchor is provided', () => {
        const element = {offsetTop: 800} as HTMLElement;
        vi.spyOn(document, 'getElementById').mockReturnValue(element);
        Object.defineProperty(window, 'location', {value: {hash: '#target-element'}, writable: true});

        scrollToAnchor(200);
        vi.runAllTimers();

        expect(document.getElementById).toHaveBeenCalledWith('target-element');
        expect(window.scrollTo).toHaveBeenCalledWith({top: 600, behavior: 'smooth'});
    });

    it('does not scroll when element is not found', () => {
        vi.spyOn(document, 'getElementById').mockReturnValue(null);

        scrollToAnchor(100, 'nonexistent');
        vi.runAllTimers();

        expect(window.scrollTo).not.toHaveBeenCalled();
    });

    it('uses default offset of 450', () => {
        const element = {offsetTop: 1000} as HTMLElement;
        vi.spyOn(document, 'getElementById').mockReturnValue(element);

        scrollToAnchor(undefined, 'section');
        vi.runAllTimers();

        expect(window.scrollTo).toHaveBeenCalledWith({top: 550, behavior: 'smooth'});
    });

    it('executes asynchronously via setTimeout', () => {
        const element = {offsetTop: 500} as HTMLElement;
        vi.spyOn(document, 'getElementById').mockReturnValue(element);

        scrollToAnchor(100, 'test');

        expect(window.scrollTo).not.toHaveBeenCalled();

        vi.runAllTimers();

        expect(window.scrollTo).toHaveBeenCalled();
    });
});
