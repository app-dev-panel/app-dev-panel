import {afterEach, describe, expect, it, vi} from 'vitest';
import {IFrameWrapper} from './IFrameWrapper';

const makeFrame = () => {
    const postMessage = vi.fn();
    const frame = {contentWindow: {postMessage}} as unknown as HTMLIFrameElement;
    return {frame, postMessage};
};

const panelLoaded = () => new MessageEvent('message', {data: {event: 'panel.loaded', value: true}});

describe('IFrameWrapper', () => {
    afterEach(() => vi.restoreAllMocks());

    it('queues events until the panel reports it has loaded', () => {
        const {frame, postMessage} = makeFrame();
        const wrapper = new IFrameWrapper(frame);

        wrapper.dispatchEvent('router.navigate', '/debug');
        expect(postMessage).not.toHaveBeenCalled();

        window.dispatchEvent(panelLoaded());
        expect(postMessage).toHaveBeenCalledTimes(1);
        expect(postMessage.mock.calls[0][0]).toEqual({event: 'router.navigate', value: '/debug'});

        wrapper.dispose();
    });

    it('stops listening once disposed', () => {
        const {frame, postMessage} = makeFrame();
        const wrapper = new IFrameWrapper(frame);
        wrapper.dispatchEvent('router.navigate', '/debug');

        wrapper.dispose();
        window.dispatchEvent(panelLoaded());

        expect(postMessage).not.toHaveBeenCalled();
    });

    it('registers exactly one listener and removes the same one on dispose', () => {
        const add = vi.spyOn(window, 'addEventListener');
        const remove = vi.spyOn(window, 'removeEventListener');
        const {frame} = makeFrame();

        const wrapper = new IFrameWrapper(frame);
        const registered = add.mock.calls.filter(([type]) => type === 'message');
        expect(registered).toHaveLength(1);

        wrapper.dispose();
        const removed = remove.mock.calls.filter(([type]) => type === 'message');
        expect(removed).toHaveLength(1);
        expect(removed[0][1]).toBe(registered[0][1]);
    });
});
