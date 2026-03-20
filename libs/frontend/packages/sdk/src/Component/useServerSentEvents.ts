import {createServerSentEventsObserver} from '@app-dev-panel/sdk/Component/ServerSentEventsObserver';
import {useEffect, useRef} from 'react';

type DebugUpdatedType = {type: EventTypesEnum.DebugUpdated; payload: Record<string, never>};

export enum EventTypesEnum {
    DebugUpdated = 'debug-updated',
}

export type EventTypes = DebugUpdatedType;

export const useServerSentEvents = (
    backendUrl: string,
    onMessage: (event: MessageEvent<EventTypes>) => void,
    subscribe = true,
) => {
    const onMessageRef = useRef(onMessage);
    onMessageRef.current = onMessage;

    useEffect(() => {
        if (!subscribe || !backendUrl) return;

        const observer = createServerSentEventsObserver(backendUrl);
        const handler = (event: MessageEvent<EventTypes>) => onMessageRef.current(event);
        observer.subscribe(handler);

        return () => {
            observer.unsubscribe(handler);
            observer.close();
        };
    }, [backendUrl, subscribe]);
};
