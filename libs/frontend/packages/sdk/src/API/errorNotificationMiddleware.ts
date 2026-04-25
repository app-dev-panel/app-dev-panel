import {addNotification} from '@app-dev-panel/sdk/Component/Notifications';
import {isRejectedWithValue, Middleware, MiddlewareAPI} from '@reduxjs/toolkit';

export const errorNotificationMiddleware: Middleware = (api: MiddlewareAPI) => (next) => (action) => {
    if (isRejectedWithValue(action)) {
        const requestUrl = (action.meta as any)?.baseQueryMeta?.request?.url ?? 'unknown';
        const payload = action.payload as Record<string, any>;

        if (payload.status === 'FETCH_ERROR') {
            api.dispatch(
                addNotification({
                    title: payload.error,
                    text: `An error occurred during the request to ${requestUrl}`,
                    color: 'error',
                }),
            );
        } else if (typeof payload.status === 'number' && payload.status >= 400) {
            const data = payload.data;
            const errorMessage = data?.error || data?.message || `HTTP ${payload.status}`;
            api.dispatch(
                addNotification({title: `Request failed (${payload.status})`, text: errorMessage, color: 'error'}),
            );
        }
    }

    return next(action);
};
