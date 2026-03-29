import {addNotification} from '@app-dev-panel/sdk/Component/Notifications';
import {isRejectedWithValue, Middleware, MiddlewareAPI} from '@reduxjs/toolkit';

export const errorNotificationMiddleware: Middleware = (api: MiddlewareAPI) => (next) => (action) => {
    if (isRejectedWithValue(action)) {
        if (action.payload.status === 'FETCH_ERROR') {
            const requestUrl = action.meta?.baseQueryMeta?.request?.url ?? 'unknown';
            api.dispatch(
                addNotification({
                    title: action.payload.error,
                    text: `An error occurred during the request to ${requestUrl}`,
                    color: 'error',
                }),
            );
        }
    }

    return next(action);
};
