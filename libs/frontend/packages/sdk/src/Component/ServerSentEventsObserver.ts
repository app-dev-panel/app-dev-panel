class ServerSentEvents {
    private eventSource: EventSource | null = null;
    private listeners: ((event: MessageEvent) => void)[] = [];
    private reconnectAttempt = 0;
    private reconnectTimer: ReturnType<typeof setTimeout> | null = null;
    private readonly maxReconnectDelay = 30_000; // 30 seconds
    private readonly baseDelay = 1_000; // 1 second

    constructor(private url: string) {}

    subscribe(subscriber: (event: MessageEvent) => void) {
        this.listeners.push(subscriber);
        if (this.eventSource === null || this.eventSource.readyState === EventSource.CLOSED) {
            this.connect();
        }
    }

    unsubscribe(subscriber: (event: MessageEvent) => void) {
        this.listeners = this.listeners.filter((listener) => listener !== subscriber);
        if (this.listeners.length === 0) {
            this.close();
        }
    }

    close() {
        if (this.reconnectTimer !== null) {
            clearTimeout(this.reconnectTimer);
            this.reconnectTimer = null;
        }
        if (this.eventSource !== null) {
            this.eventSource.close();
            this.eventSource = null;
        }
        this.reconnectAttempt = 0;
    }

    private connect() {
        if (this.eventSource !== null) {
            this.eventSource.close();
        }

        this.eventSource = new EventSource(this.url);

        this.eventSource.addEventListener('message', this.handle);

        this.eventSource.addEventListener('open', () => {
            this.reconnectAttempt = 0;
        });

        this.eventSource.addEventListener('error', () => {
            if (this.eventSource !== null) {
                this.eventSource.close();
                this.eventSource = null;
            }

            if (this.listeners.length === 0) {
                return;
            }

            const delay = Math.min(this.baseDelay * 2 ** this.reconnectAttempt, this.maxReconnectDelay);
            this.reconnectAttempt++;
            this.reconnectTimer = setTimeout(() => {
                this.reconnectTimer = null;
                if (this.listeners.length > 0) {
                    this.connect();
                }
            }, delay);
        });
    }

    private handle = (event: MessageEvent) => {
        this.listeners.forEach((listener) => listener(event));
    };
}

export const createServerSentEventsObserver = (backendUrl: string) =>
    new ServerSentEvents(backendUrl.replace(/\/$/, '') + '/debug/api/event-stream');
