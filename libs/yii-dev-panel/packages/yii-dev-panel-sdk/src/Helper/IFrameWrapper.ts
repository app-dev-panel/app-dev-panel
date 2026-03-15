import {
    CrossWindowEventType,
    CrossWindowValueType,
    dispatchWindowEvent,
} from '@yiisoft/yii-dev-panel-sdk/Helper/dispatchWindowEvent';
import {Queue} from '@yiisoft/yii-dev-panel-sdk/Helper/queue';

export class IFrameWrapper {
    private eventQueue = new Queue();

    constructor(public frame: HTMLIFrameElement) {
        window.addEventListener('message', (e) => {
            if (e.origin !== window.location.origin) {
                return;
            }
            if (e.data && typeof e.data === 'object' && 'event' in e.data) {
                switch (e.data.event as CrossWindowEventType) {
                    case 'panel.loaded':
                        this.eventQueue.ready();
                        break;
                }
            }
        });
    }

    dispatchEvent(event: CrossWindowEventType, value: CrossWindowValueType) {
        this.eventQueue.next(() => {
            dispatchWindowEvent(this.frame.contentWindow, event, value);
        });
    }
}
