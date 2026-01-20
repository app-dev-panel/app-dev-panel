// TODO support custom events and decode payload to object
class ServerSentEvents {
    private eventSource: EventSource = null;
    private listeners: ((event: MessageEvent) => void)[] = [];
    constructor(private url: string) {}

    subscribe(subscriber: (event: MessageEvent) => void) {
        if (this.eventSource === null || this.eventSource.readyState === EventSource.CLOSED) {
            this.eventSource = new EventSource(this.url);
        }
        this.listeners.push(subscriber);
        this.eventSource.addEventListener('message', this.handle.bind(this));
    }

    unsubscribe(subscriber: (event: MessageEvent) => void) {
        if (this.eventSource === null) {
            return;
        }
        this.listeners = this.listeners.filter((listener) => listener !== subscriber);
        if (this.eventSource.readyState === EventSource.OPEN && this.listeners.length === 0) {
            this.close();
        }
    }

    close() {
        this.eventSource.close();
        this.eventSource.removeEventListener('message', this.handle.bind(this));
    }

    private handle(event: MessageEvent) {
        this.listeners && this.listeners.forEach((listener) => listener(event));
    }
}

export const createServerSentEventsObserver = (backendUrl: string) =>
    new ServerSentEvents(backendUrl + '/debug/api/event-stream');
