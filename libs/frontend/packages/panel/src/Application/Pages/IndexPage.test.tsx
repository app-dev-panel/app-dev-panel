import {ApplicationSlice} from '@app-dev-panel/sdk/API/Application/ApplicationContext';
import {debugApi} from '@app-dev-panel/sdk/API/Debug/Debug';
import {createAdpTheme} from '@app-dev-panel/sdk/Component/Theme/DefaultTheme';
import {ThemeProvider} from '@mui/material/styles';
import {configureStore} from '@reduxjs/toolkit';
import {createApi} from '@reduxjs/toolkit/query/react';
import {fireEvent, render, screen} from '@testing-library/react';
import {PropsWithChildren} from 'react';
import {Provider} from 'react-redux';
import {MemoryRouter} from 'react-router-dom';
import {describe, expect, it} from 'vitest';
import {IndexPage} from './IndexPage';

// Stub APIs that IndexPage uses
const stubInspectorApi = createApi({
    reducerPath: 'api.inspector',
    baseQuery: () => ({data: []}),
    endpoints: (builder) => ({
        getParameters: builder.query<unknown[], void>({query: () => 'params'}),
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

const stubGiiApi = createApi({
    reducerPath: 'api.gii',
    baseQuery: () => ({data: {generators: []}}),
    endpoints: (builder) => ({getGenerators: builder.query<unknown[], void>({query: () => '/generator'})}),
});

const renderIndexPage = (favoriteUrls: string[] = []) => {
    const store = configureStore({
        reducer: {
            [ApplicationSlice.name]: ApplicationSlice.reducer,
            notifications: (state = [], _action: any) => state,
            [debugApi.reducerPath]: debugApi.reducer,
            [stubInspectorApi.reducerPath]: stubInspectorApi.reducer,
            [stubInspectorGitApi.reducerPath]: stubInspectorGitApi.reducer,
            [stubGiiApi.reducerPath]: stubGiiApi.reducer,
        },
        preloadedState: {
            application: {
                baseUrl: 'http://localhost:8080',
                preferredPageSize: 20,
                toolbarOpen: true,
                favoriteUrls,
                autoLatest: false,
                iframeHeight: 400,
                selectedService: 'local',
                themeMode: 'system' as const,
                showInactiveCollectors: false,
                editorConfig: {editor: 'none' as const, customUrlTemplate: '', pathMapping: {}},
            },
        },
        middleware: (getDefaultMiddleware) =>
            getDefaultMiddleware({serializableCheck: false}).concat(
                debugApi.middleware,
                stubInspectorApi.middleware,
                stubInspectorGitApi.middleware,
                stubGiiApi.middleware,
            ),
    });

    const theme = createAdpTheme('light', {openLinksInNewWindow: false, baseUrl: ''});

    const Wrapper = ({children}: PropsWithChildren) => (
        <Provider store={store}>
            <ThemeProvider theme={theme}>
                <MemoryRouter>{children}</MemoryRouter>
            </ThemeProvider>
        </Provider>
    );

    return {store, ...render(<IndexPage />, {wrapper: Wrapper})};
};

describe('IndexPage (Settings)', () => {
    it('renders page header', () => {
        renderIndexPage();
        expect(screen.getByText('Application Development Panel')).toBeInTheDocument();
    });

    it('shows current backend URL', () => {
        renderIndexPage();
        expect(screen.getByText('http://localhost:8080')).toBeInTheDocument();
    });

    it('does not show favorites section when no favorites', () => {
        renderIndexPage([]);
        expect(screen.queryByText('Favorites')).not.toBeInTheDocument();
    });

    it('shows favorites section when favorites exist', () => {
        renderIndexPage(['http://127.0.0.1:8101']);
        expect(screen.getByText('Favorites')).toBeInTheDocument();
        expect(screen.getByText('http://127.0.0.1:8101')).toBeInTheDocument();
    });

    it('renders multiple favorites', () => {
        renderIndexPage(['http://127.0.0.1:8101', 'http://127.0.0.1:8102']);
        expect(screen.getByText('http://127.0.0.1:8101')).toBeInTheDocument();
        expect(screen.getByText('http://127.0.0.1:8102')).toBeInTheDocument();
    });

    it('clicking favorite row dispatches changeBaseUrl', () => {
        const {store} = renderIndexPage(['http://127.0.0.1:8101']);
        const favText = screen.getByText('http://127.0.0.1:8101');
        // Click the parent FavoriteItem row (not the text itself, but the row)
        fireEvent.click(favText.closest('div')!);

        expect(store.getState().application.baseUrl).toBe('http://127.0.0.1:8101');
    });

    it('clicking delete button removes favorite without switching URL', () => {
        const {store} = renderIndexPage(['http://127.0.0.1:8101', 'http://127.0.0.1:8102']);
        // Find delete buttons (close icons rendered by Chip onDelete)
        const deleteButtons = screen.getAllByText('close');
        fireEvent.click(deleteButtons[0]);

        const state = store.getState().application;
        expect(state.favoriteUrls).not.toContain('http://127.0.0.1:8101');
        expect(state.favoriteUrls).toContain('http://127.0.0.1:8102');
        // baseUrl should NOT have changed to the deleted favorite
        expect(state.baseUrl).toBe('http://localhost:8080');
    });

    it('renders API status cards', () => {
        renderIndexPage();
        expect(screen.getByText('Debug')).toBeInTheDocument();
        expect(screen.getByText('Inspector')).toBeInTheDocument();
        expect(screen.getByText('Gii')).toBeInTheDocument();
    });

    it('renders backend URL input', () => {
        renderIndexPage();
        expect(screen.getByPlaceholderText('http://localhost:8080')).toBeInTheDocument();
    });
});
