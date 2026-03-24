import {describe, expect, it, vi} from 'vitest';
import {Queue} from './queue';

describe('Queue', () => {
    it('queues callbacks when not ready', () => {
        const queue = new Queue();
        const callback = vi.fn();

        queue.next(callback);

        expect(callback).not.toHaveBeenCalled();
    });

    it('executes queued callbacks when ready is called', () => {
        const queue = new Queue();
        const callback1 = vi.fn();
        const callback2 = vi.fn();

        queue.next(callback1);
        queue.next(callback2);
        queue.ready();

        expect(callback1).toHaveBeenCalledOnce();
        expect(callback2).toHaveBeenCalledOnce();
    });

    it('executes callbacks immediately when already ready', () => {
        const queue = new Queue();
        const callback = vi.fn();

        queue.ready();
        queue.next(callback);

        expect(callback).toHaveBeenCalledOnce();
    });

    it('executes both queued and post-ready callbacks', () => {
        const queue = new Queue();
        const before = vi.fn();
        const after = vi.fn();

        queue.next(before);
        queue.ready();
        queue.next(after);

        expect(before).toHaveBeenCalledOnce();
        expect(after).toHaveBeenCalledOnce();
    });

    it('can be initialized in ready state', () => {
        const queue = new Queue('ready');
        const callback = vi.fn();

        queue.next(callback);

        expect(callback).toHaveBeenCalledOnce();
    });

    it('handles empty queue on ready', () => {
        const queue = new Queue();
        expect(() => queue.ready()).not.toThrow();
    });

    it('executes callbacks in order', () => {
        const queue = new Queue();
        const order: number[] = [];

        queue.next(() => order.push(1));
        queue.next(() => order.push(2));
        queue.next(() => order.push(3));
        queue.ready();

        expect(order).toEqual([1, 2, 3]);
    });
});
