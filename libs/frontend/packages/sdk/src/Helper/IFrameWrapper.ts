import {
    CrossWindowEventType,
    CrossWindowValueType,
    dispatchWindowEvent,
} from '@app-dev-panel/sdk/Helper/dispatchWindowEvent';
import {Queue} from '@app-dev-panel/sdk/Helper/queue';

export class IFrameWrapper {
    private eventQueue = new Queue();

    constructor(public frame: HTMLIFrameElement) {
        window.addEventListener('message', (e) => {
            // Accept from any origin — iframe and parent can be on different host/port
            if (!e.data || typeof e.data !== 'object' || !('event' in e.data)) {
                return;
            }
            switch (e.data.event as CrossWindowEventType) {
                case 'panel.loaded':
                    this.eventQueue.ready();
                    break;
            }
        });
    }

    dispatchEvent(event: CrossWindowEventType, value: CrossWindowValueType) {
        this.eventQueue.next(() => {
            dispatchWindowEvent(this.frame.contentWindow, event, value);
        });
    }
}
