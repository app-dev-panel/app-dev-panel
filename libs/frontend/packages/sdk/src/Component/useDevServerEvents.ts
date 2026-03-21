import {useServerSentEvents} from '@app-dev-panel/sdk/Component/useServerSentEvents';

export const useDevServerEvents = (backendUrl: string, onMessage: (event: MessageEvent) => void, subscribe = true) => {
    useServerSentEvents(backendUrl, onMessage, subscribe, '/debug/api/dev');
};
