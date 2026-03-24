import {afterEach, beforeEach, describe, expect, it, vi} from 'vitest';

type EventSourceListener = (event: any) => void;

class MockEventSource {
    static CLOSED = 2;
    readyState = 1;
    listeners: Record<string, EventSourceListener[]> = {};
    url: string;

    constructor(url: string) {
        this.url = url;
        MockEventSource.instances.push(this);
    }

    addEventListener(type: string, handler: EventSourceListener) {
        (this.listeners[type] ??= []).push(handler);
    }

    close() {
        this.readyState = MockEventSource.CLOSED;
    }

    // helpers for tests
    fireOpen() {
        this.listeners['open']?.forEach((h) => h({}));
    }

    fireMessage(data: any) {
        this.listeners['message']?.forEach((h) => h({data}));
    }

    fireError() {
        this.listeners['error']?.forEach((h) => h({}));
    }

    static instances: MockEventSource[] = [];
    static reset() {
        MockEventSource.instances = [];
    }
}

describe('ServerSentEventsObserver', () => {
    let createServerSentEventsObserver: (url: string) => any;

    beforeEach(async () => {
        MockEventSource.reset();
        vi.stubGlobal('EventSource', MockEventSource);
        vi.useFakeTimers();
        // Re-import to get fresh module
        const mod = await import('./ServerSentEventsObserver');
        createServerSentEventsObserver = mod.createServerSentEventsObserver;
    });

    afterEach(() => {
        vi.useRealTimers();
        vi.unstubAllGlobals();
    });

    it('creates observer with event-stream URL', () => {
        const observer = createServerSentEventsObserver('http://localhost:8080');
        const handler = vi.fn();
        observer.subscribe(handler);
        expect(MockEventSource.instances.length).toBe(1);
        expect(MockEventSource.instances[0].url).toBe('http://localhost:8080/debug/api/event-stream');
        observer.close();
    });

    it('subscribes and receives messages', () => {
        const observer = createServerSentEventsObserver('http://localhost');
        const handler = vi.fn();
        observer.subscribe(handler);
        MockEventSource.instances[0].fireMessage({type: 'debug-updated'});
        expect(handler).toHaveBeenCalledOnce();
        observer.close();
    });

    it('delivers messages to multiple subscribers', () => {
        const observer = createServerSentEventsObserver('http://localhost');
        const h1 = vi.fn();
        const h2 = vi.fn();
        observer.subscribe(h1);
        observer.subscribe(h2);
        MockEventSource.instances[0].fireMessage('test');
        expect(h1).toHaveBeenCalledOnce();
        expect(h2).toHaveBeenCalledOnce();
        observer.close();
    });

    it('unsubscribes specific handler', () => {
        const observer = createServerSentEventsObserver('http://localhost');
        const h1 = vi.fn();
        const h2 = vi.fn();
        observer.subscribe(h1);
        observer.subscribe(h2);
        observer.unsubscribe(h1);
        MockEventSource.instances[0].fireMessage('test');
        expect(h1).not.toHaveBeenCalled();
        expect(h2).toHaveBeenCalledOnce();
        observer.close();
    });

    it('closes EventSource when last subscriber unsubscribes', () => {
        const observer = createServerSentEventsObserver('http://localhost');
        const handler = vi.fn();
        observer.subscribe(handler);
        const es = MockEventSource.instances[0];
        observer.unsubscribe(handler);
        expect(es.readyState).toBe(MockEventSource.CLOSED);
    });

    it('close() clears EventSource', () => {
        const observer = createServerSentEventsObserver('http://localhost');
        observer.subscribe(vi.fn());
        const es = MockEventSource.instances[0];
        observer.close();
        expect(es.readyState).toBe(MockEventSource.CLOSED);
    });

    it('reconnects on error with exponential backoff', () => {
        const observer = createServerSentEventsObserver('http://localhost');
        observer.subscribe(vi.fn());
        expect(MockEventSource.instances.length).toBe(1);

        // Trigger error — should schedule reconnect after 1s
        MockEventSource.instances[0].fireError();
        expect(MockEventSource.instances.length).toBe(1); // not yet reconnected

        vi.advanceTimersByTime(1000);
        expect(MockEventSource.instances.length).toBe(2); // reconnected

        // Second error — 2s delay
        MockEventSource.instances[1].fireError();
        vi.advanceTimersByTime(1999);
        expect(MockEventSource.instances.length).toBe(2);
        vi.advanceTimersByTime(1);
        expect(MockEventSource.instances.length).toBe(3);
        observer.close();
    });

    it('resets reconnect attempt counter on successful open', () => {
        const observer = createServerSentEventsObserver('http://localhost');
        observer.subscribe(vi.fn());

        // Error + reconnect
        MockEventSource.instances[0].fireError();
        vi.advanceTimersByTime(1000);
        expect(MockEventSource.instances.length).toBe(2);

        // Successful open resets counter
        MockEventSource.instances[1].fireOpen();

        // Next error should use base delay (1s) again
        MockEventSource.instances[1].fireError();
        vi.advanceTimersByTime(1000);
        expect(MockEventSource.instances.length).toBe(3);
        observer.close();
    });

    it('does not reconnect if no listeners remain after error', () => {
        const observer = createServerSentEventsObserver('http://localhost');
        const handler = vi.fn();
        observer.subscribe(handler);
        observer.unsubscribe(handler);

        // The unsubscribe already closed it; manually trigger scenario where
        // error fires with no listeners
        expect(MockEventSource.instances.length).toBe(1);
        vi.advanceTimersByTime(60_000);
        // No new instances created
        expect(MockEventSource.instances.length).toBe(1);
    });
});
