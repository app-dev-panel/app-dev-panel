import {Middleware, MiddlewareAPI} from '@reduxjs/toolkit';

export const consoleLogActionsMiddleware: Middleware = (_api: MiddlewareAPI) => (next) => (action) => {
    console.log('middleware action', action);

    return next(action);
};
