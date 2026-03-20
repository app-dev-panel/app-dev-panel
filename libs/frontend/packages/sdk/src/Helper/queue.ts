export class Queue {
    private queue: (() => void)[] = [];

    constructor(private state: 'initialized' | 'ready' = 'initialized') {}

    public next(callback: () => void) {
        if (this.state === 'ready') {
            callback();
        } else {
            this.queue.push(callback);
        }
    }

    public ready() {
        this.state = 'ready';
        this.queue.forEach((callback) => callback());
    }
}
