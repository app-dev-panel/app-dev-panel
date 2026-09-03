import {ApplicationSlice, type ApplicationState} from '@app-dev-panel/sdk/API/Application/ApplicationContext';
import storage from '@app-dev-panel/sdk/API/storage';
import {NotificationsSlice} from '@app-dev-panel/sdk/Component/Notifications';
import {Middleware} from '@reduxjs/toolkit';
import {persistReducer} from 'redux-persist';
import {createStateSyncMiddleware, withReduxStateSync} from 'redux-state-sync';
const applicationSliceConfig = {key: ApplicationSlice.name, version: 1, storage};
const notificationsSliceConfig = {key: NotificationsSlice.name, version: 1, storage};

/**
 * Build a fully-typed preloaded `application` slice for `createStore()`.
 *
 * Callers used to pass `{baseUrl, favoriteUrls} as any`, which left every
 * other field (`autoLatest`, `toolbarPosition`, …) `undefined` until
 * redux-persist rehydrated — and forever when nothing was persisted yet.
 * Spreading the slice's initial state fixes that. `_persist` is deliberately
 * absent: `persistReducer` treats a preloaded `_persist` as "already
 * rehydrated" and would neither restore nor write `localStorage`.
 */
export const preloadedApplicationState = (overrides: Partial<ApplicationState> = {}): ApplicationState => ({
    ...ApplicationSlice.getInitialState(),
    ...overrides,
});

export const reducers = {
    [ApplicationSlice.name]: persistReducer(applicationSliceConfig, withReduxStateSync(ApplicationSlice.reducer)),
    [NotificationsSlice.name]: persistReducer(notificationsSliceConfig, NotificationsSlice.reducer),
};
export const middlewares: Middleware[] = [
    createStateSyncMiddleware({
        whitelist: [ApplicationSlice.actions.setToolbarOpen.type, ApplicationSlice.actions.changeBaseUrl.type],
    }) as Middleware,
];
