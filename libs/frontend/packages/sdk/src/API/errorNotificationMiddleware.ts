import {addNotification} from '@app-dev-panel/sdk/Component/Notifications';
import {isRejectedWithValue, Middleware, MiddlewareAPI} from '@reduxjs/toolkit';

export const errorNotificationMiddleware: Middleware = (api: MiddlewareAPI) => (next) => (action) => {
    if (isRejectedWithValue(action)) {
        const requestUrl = action.meta?.baseQueryMeta?.request?.url ?? 'unknown';

        if (action.payload.status === 'FETCH_ERROR') {
            api.dispatch(
                addNotification({
                    title: action.payload.error,
                    text: `An error occurred during the request to ${requestUrl}`,
                    color: 'error',
                }),
            );
        } else if (typeof action.payload.status === 'number' && action.payload.status >= 400) {
            const data = action.payload.data;
            const errorMessage = data?.error || data?.message || `HTTP ${action.payload.status}`;
            api.dispatch(
                addNotification({
                    title: `Request failed (${action.payload.status})`,
                    text: errorMessage,
                    color: 'error',
                }),
            );
        }
    }

    return next(action);
};
