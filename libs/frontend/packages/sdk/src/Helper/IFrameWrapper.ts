import {
    CrossWindowEventType,
    CrossWindowValueType,
    dispatchWindowEvent,
} from '@app-dev-panel/sdk/Helper/dispatchWindowEvent';
import {Queue} from '@app-dev-panel/sdk/Helper/queue';

export class IFrameWrapper {
    private eventQueue = new Queue();

    private readonly onMessage = (e: MessageEvent) => {
        // Accept from any origin — iframe and parent can be on different host/port
        if (!e.data || typeof e.data !== 'object' || !('event' in e.data)) {
            return;
        }
        switch (e.data.event as CrossWindowEventType) {
            case 'panel.loaded':
                this.eventQueue.ready();
                break;
        }
    };

    constructor(public frame: HTMLIFrameElement) {
        window.addEventListener('message', this.onMessage);
    }

    dispatchEvent(event: CrossWindowEventType, value: CrossWindowValueType) {
        this.eventQueue.next(() => {
            dispatchWindowEvent(this.frame.contentWindow, event, value);
        });
    }

    /**
     * Detach the `message` listener. Must be called when the iframe is
     * removed, otherwise every wrapper ever created keeps listening (and
     * keeps its frame reachable) for the lifetime of the host page.
     */
    dispose() {
        window.removeEventListener('message', this.onMessage);
    }
}
