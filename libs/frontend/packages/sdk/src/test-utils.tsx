import {ThemeProvider} from '@mui/material/styles';
import {configureStore} from '@reduxjs/toolkit';
import {createApi} from '@reduxjs/toolkit/query/react';
import {render, RenderOptions} from '@testing-library/react';
import React, {PropsWithChildren} from 'react';
import {Provider} from 'react-redux';
import {MemoryRouter} from 'react-router-dom';
import {debugSlice} from './API/Debug/Context';
import {debugApi} from './API/Debug/Debug';
import {createAdpTheme} from './Component/Theme/DefaultTheme';

// Stub for inspector API used by ExceptionPanel (useLazyGetFilesQuery, useLazyGetClassQuery)
const stubInspectorApi = createApi({
    reducerPath: 'api.inspector',
    baseQuery: () => ({data: []}),
    endpoints: (builder) => ({
        getFiles: builder.query<unknown[], string>({query: (path) => `files?path=${path}`}),
        getClass: builder.query<unknown[], {className: string; methodName: string}>({
            query: ({className, methodName = ''}) => `files?class=${className}&method=${methodName}`,
        }),
    }),
});

const stubInspectorGitApi = createApi({
    reducerPath: 'api.inspector.git',
    baseQuery: () => ({data: null}),
    endpoints: () => ({}),
});

const defaultReducer = {
    application: (
        state = {baseUrl: 'http://localhost:8080', pageSize: 20, autoLatest: false, toolbar: {}, favoriteUrls: []},
        _action: any,
    ) => state,
    notifications: (state = [], _action: any) => state,
    [debugSlice.name]: debugSlice.reducer,
    [debugApi.reducerPath]: debugApi.reducer,
    [stubInspectorApi.reducerPath]: stubInspectorApi.reducer,
    [stubInspectorGitApi.reducerPath]: stubInspectorGitApi.reducer,
};

const defaultMiddleware = [debugApi.middleware, stubInspectorApi.middleware, stubInspectorGitApi.middleware];

type RenderWithProvidersOptions = RenderOptions & {
    preloadedState?: Record<string, any>;
    reducers?: Record<string, any>;
    route?: string;
};

export const renderWithProviders = (ui: React.ReactElement, options: RenderWithProvidersOptions = {}) => {
    const {preloadedState = {}, reducers = defaultReducer, route = '/', ...renderOptions} = options;
    const store = configureStore({
        reducer: reducers,
        preloadedState,
        middleware: (getDefaultMiddleware) =>
            getDefaultMiddleware({serializableCheck: false}).concat(defaultMiddleware),
    });
    const theme = createAdpTheme('light', {openLinksInNewWindow: false, baseUrl: ''});

    const Wrapper = ({children}: PropsWithChildren) => (
        <Provider store={store}>
            <ThemeProvider theme={theme}>
                <MemoryRouter initialEntries={[route]}>{children}</MemoryRouter>
            </ThemeProvider>
        </Provider>
    );

    return {store, ...render(ui, {wrapper: Wrapper, ...renderOptions})};
};
