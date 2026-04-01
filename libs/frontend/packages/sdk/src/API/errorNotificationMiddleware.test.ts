import {configureStore} from '@reduxjs/toolkit';
import {createApi} from '@reduxjs/toolkit/query/react';
import {describe, expect, it} from 'vitest';
import {NotificationsSlice, selectNotifications} from '../Component/Notifications';
import {errorNotificationMiddleware} from './errorNotificationMiddleware';

function createTestStore(baseQueryFn: any) {
    const api = createApi({
        reducerPath: 'api',
        baseQuery: baseQueryFn,
        endpoints: (builder) => ({getData: builder.query<unknown, void>({query: () => '/test'})}),
    });

    const store = configureStore({
        reducer: {[NotificationsSlice.name]: NotificationsSlice.reducer, [api.reducerPath]: api.reducer},
        middleware: (getDefaultMiddleware) =>
            getDefaultMiddleware({serializableCheck: false}).concat(api.middleware, errorNotificationMiddleware),
    });

    return {store, api};
}

describe('errorNotificationMiddleware', () => {
    it('dispatches notification on FETCH_ERROR', async () => {
        const {store, api} = createTestStore(() => ({
            error: {status: 'FETCH_ERROR', error: 'Network error'},
            meta: {request: {url: 'http://localhost/test'}},
        }));

        await store.dispatch(api.endpoints.getData.initiate());
        const notifications = selectNotifications({notifications: store.getState().notifications});

        expect(notifications).toHaveLength(1);
        expect(notifications[0].color).toBe('error');
        expect(notifications[0].title).toBe('Network error');
        expect(notifications[0].text).toContain('http://localhost/test');
    });

    it('dispatches notification on HTTP 500 error', async () => {
        const {store, api} = createTestStore(() => ({
            error: {status: 500, data: {error: 'Class "Foo" is not registered in the DI container.'}},
            meta: {request: {url: 'http://localhost/inspect/api/object'}},
        }));

        await store.dispatch(api.endpoints.getData.initiate());
        const notifications = selectNotifications({notifications: store.getState().notifications});

        expect(notifications).toHaveLength(1);
        expect(notifications[0].color).toBe('error');
        expect(notifications[0].title).toBe('Request failed (500)');
        expect(notifications[0].text).toBe('Class "Foo" is not registered in the DI container.');
    });

    it('dispatches notification on HTTP 404 error', async () => {
        const {store, api} = createTestStore(() => ({
            error: {status: 404, data: {error: 'Not found'}},
            meta: {request: {url: 'http://localhost/test'}},
        }));

        await store.dispatch(api.endpoints.getData.initiate());
        const notifications = selectNotifications({notifications: store.getState().notifications});

        expect(notifications).toHaveLength(1);
        expect(notifications[0].title).toBe('Request failed (404)');
        expect(notifications[0].text).toBe('Not found');
    });

    it('uses fallback message when error field is missing', async () => {
        const {store, api} = createTestStore(() => ({
            error: {status: 500, data: {}},
            meta: {request: {url: 'http://localhost/test'}},
        }));

        await store.dispatch(api.endpoints.getData.initiate());
        const notifications = selectNotifications({notifications: store.getState().notifications});

        expect(notifications).toHaveLength(1);
        expect(notifications[0].text).toBe('HTTP 500');
    });

    it('does not dispatch notification on successful request', async () => {
        const {store, api} = createTestStore(() => ({data: {result: 'ok'}}));

        await store.dispatch(api.endpoints.getData.initiate());
        const notifications = selectNotifications({notifications: store.getState().notifications});

        expect(notifications).toHaveLength(0);
    });
});
