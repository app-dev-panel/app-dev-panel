import {createServerSentEventsObserver} from '@app-dev-panel/sdk/Component/ServerSentEventsObserver';
import {useEffect, useRef} from 'react';

type DebugUpdatedType = {type: EventTypesEnum.DebugUpdated; payload: Record<string, never>};
type EntryCreatedType = {type: EventTypesEnum.EntryCreated; payload: {id: string}};
type LiveLogType = {type: EventTypesEnum.LiveLog; payload: string};
type LiveDumpType = {type: EventTypesEnum.LiveDump; payload: string};

export enum EventTypesEnum {
    DebugUpdated = 'debug-updated',
    EntryCreated = 'entry-created',
    LiveLog = 'live-log',
    LiveDump = 'live-dump',
}

export type EventTypes = DebugUpdatedType | EntryCreatedType | LiveLogType | LiveDumpType;

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
