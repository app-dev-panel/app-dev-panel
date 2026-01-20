import {createServerSentEventsObserver} from '@yiisoft/yii-dev-panel-sdk/Component/ServerSentEventsObserver';
import {useEffect, useRef} from 'react';

type DebugUpdatedType = {
    type: EventTypesEnum.DebugUpdated;
    payload: {};
};

export enum EventTypesEnum {
    DebugUpdated = 'debug-updated',
}

export type EventTypes = DebugUpdatedType;

export const useServerSentEvents = (
    backendUrl: string,
    onMessage: (event: MessageEvent<EventTypes>) => void,
    subscribe = true,
) => {
    const prevOnMessage = useRef(onMessage);
    const ServerSentEventsObserverRef = useRef(createServerSentEventsObserver(backendUrl));

    useEffect(() => {
        if (prevOnMessage.current) {
            ServerSentEventsObserverRef.current.unsubscribe(prevOnMessage.current);
        }
        if (!subscribe) {
            return () => {
                ServerSentEventsObserverRef.current.unsubscribe(onMessage);
            };
        }

        ServerSentEventsObserverRef.current.subscribe(onMessage);
        prevOnMessage.current = onMessage;

        return () => {
            ServerSentEventsObserverRef.current.unsubscribe(onMessage);
            ServerSentEventsObserverRef.current.close();
        };
    }, [onMessage, subscribe]);
};
