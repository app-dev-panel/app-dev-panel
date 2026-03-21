import {createServerSentEventsObserver} from '@app-dev-panel/sdk/Component/ServerSentEventsObserver';
import {useEffect, useRef} from 'react';

type DebugUpdatedType = {type: EventTypesEnum.DebugUpdated; payload: Record<string, never>};

export enum EventTypesEnum {
    DebugUpdated = 'debug-updated',
}

export type EventTypes = DebugUpdatedType;

export const useServerSentEvents = <T = EventTypes>(
    backendUrl: string,
    onMessage: (event: MessageEvent<T>) => void,
    subscribe = true,
    endpoint = '/debug/api/event-stream',
) => {
    const onMessageRef = useRef(onMessage);
    onMessageRef.current = onMessage;

    useEffect(() => {
        if (!subscribe || !backendUrl) return;

        const observer = createServerSentEventsObserver(backendUrl + endpoint);
        const handler = (event: MessageEvent<T>) => onMessageRef.current(event);
        observer.subscribe(handler);

        return () => {
            observer.unsubscribe(handler);
            observer.close();
        };
    }, [backendUrl, subscribe, endpoint]);
};
