import {createServerSentEventsObserver} from '@app-dev-panel/sdk/Component/ServerSentEventsObserver';
import {useEffect, useRef} from 'react';

export const useDevServerEvents = (backendUrl: string, onMessage: (event: MessageEvent) => void, subscribe = true) => {
    const onMessageRef = useRef(onMessage);
    onMessageRef.current = onMessage;

    useEffect(() => {
        if (!subscribe || !backendUrl) return;

        const observer = createServerSentEventsObserver(backendUrl + '/debug/api/dev');
        const handler = (event: MessageEvent) => onMessageRef.current(event);
        observer.subscribe(handler);

        return () => {
            observer.unsubscribe(handler);
            observer.close();
        };
    }, [backendUrl, subscribe]);
};
