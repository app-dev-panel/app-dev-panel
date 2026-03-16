import {addNotification} from '@app-dev-panel/sdk/Component/Notifications';
import {isRejectedWithValue, Middleware, MiddlewareAPI} from '@reduxjs/toolkit';

export const errorNotificationMiddleware: Middleware = (api: MiddlewareAPI) => (next) => (action) => {
    if (isRejectedWithValue(action)) {
        console.log('action.payload.error', action, api, next);
        if (action.payload.status === 'FETCH_ERROR') {
            api.dispatch(
                addNotification({
                    title: action.payload.error,
                    text: `An error occurred during the request to ${action.meta.baseQueryMeta.request.url}`,
                    color: 'error',
                }),
            );
        }
    }

    return next(action);
};
