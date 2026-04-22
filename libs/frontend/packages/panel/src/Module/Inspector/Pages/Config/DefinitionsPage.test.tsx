import {DataContextProvider} from '@app-dev-panel/panel/Module/Inspector/Context/DataContext';
import {createAdpTheme} from '@app-dev-panel/sdk/Component/Theme/DefaultTheme';
import {ThemeProvider} from '@mui/material/styles';
import {configureStore} from '@reduxjs/toolkit';
import {render, screen} from '@testing-library/react';
import userEvent from '@testing-library/user-event';
import {Provider} from 'react-redux';
import {MemoryRouter} from 'react-router';
import {describe, expect, it, vi} from 'vitest';
import {DefinitionsPage} from './DefinitionsPage';

// ---------------------------------------------------------------------------
// Mock Inspector API
// ---------------------------------------------------------------------------

const mockDefinitions: Record<string, string> = {
    assetManager: 'yii\\web\\AssetManager',
    db: 'yii\\db\\Connection',
    errorHandler: 'yii\\web\\ErrorHandler',
    log: 'yii\\log\\Dispatcher',
    session: 'yii\\web\\Session',
};

const mockLazyLoadObject = vi
    .fn()
    .mockResolvedValue({data: {object: {class: 'Test', host: 'localhost'}, path: '/src'}});

vi.mock('@app-dev-panel/panel/Module/Inspector/API/Inspector', () => ({
    useGetConfigurationQuery: vi.fn(() => ({data: mockDefinitions, isLoading: false})),
    useLazyGetObjectQuery: vi.fn(() => [mockLazyLoadObject]),
}));

// ---------------------------------------------------------------------------
// Helpers
// ---------------------------------------------------------------------------

function renderPage(route = '/inspector/config/definitions') {
    const store = configureStore({
        reducer: {
            application: (
                state = {baseUrl: '', pageSize: 20, autoLatest: false, toolbar: {}, favoriteUrls: []},
                _action: any,
            ) => state,
        },
        middleware: (getDefaultMiddleware) => getDefaultMiddleware({serializableCheck: false}),
    });
    const theme = createAdpTheme('light', {openLinksInNewWindow: false, baseUrl: ''});

    return render(
        <Provider store={store}>
            <ThemeProvider theme={theme}>
                <MemoryRouter initialEntries={[route]}>
                    <DataContextProvider>
                        <DefinitionsPage />
                    </DataContextProvider>
                </MemoryRouter>
            </ThemeProvider>
        </Provider>,
    );
}

// ---------------------------------------------------------------------------
// Tests
// ---------------------------------------------------------------------------

describe('DefinitionsPage', () => {
    it('renders definitions after loading', async () => {
        renderPage();
        expect(screen.getByText('assetManager')).toBeInTheDocument();
        expect(screen.getByText('db')).toBeInTheDocument();
        expect(screen.getByText('errorHandler')).toBeInTheDocument();
        expect(screen.getByText('log')).toBeInTheDocument();
        expect(screen.getByText('session')).toBeInTheDocument();
    });

    it('shows class values for each definition', () => {
        renderPage();
        expect(screen.getByText('yii\\web\\AssetManager')).toBeInTheDocument();
        expect(screen.getByText('yii\\db\\Connection')).toBeInTheDocument();
    });

    it('displays total definition count', () => {
        renderPage();
        expect(screen.getAllByText('5 definitions').length).toBeGreaterThanOrEqual(1);
    });

    it('groups definitions without a namespace under Services', () => {
        renderPage();
        expect(screen.getByText('Services')).toBeInTheDocument();
    });

    it('filters definitions by name', async () => {
        const user = userEvent.setup();
        renderPage();

        const input = screen.getByPlaceholderText('Search definitions...');
        await user.type(input, 'db');

        expect(screen.getByText('db')).toBeInTheDocument();
        expect(screen.queryByText('assetManager')).not.toBeInTheDocument();
        expect(screen.queryByText('session')).not.toBeInTheDocument();
    });

    it('shows filtered count', async () => {
        const user = userEvent.setup();
        renderPage();

        const input = screen.getByPlaceholderText('Search definitions...');
        await user.type(input, 'error');

        expect(screen.getByText('1 of 5 definitions')).toBeInTheDocument();
    });

    it('shows empty state when filter matches nothing', async () => {
        const user = userEvent.setup();
        renderPage();

        const input = screen.getByPlaceholderText('Search definitions...');
        await user.type(input, 'zzzzzzzzz');

        expect(screen.getByText('No definitions found')).toBeInTheDocument();
    });

    it('renders copy and examine action buttons for each row', () => {
        renderPage();

        const copyButtons = screen.getAllByLabelText('Copy name');
        const examineButtons = screen.getAllByLabelText('Examine in container');

        expect(copyButtons).toHaveLength(5);
        expect(examineButtons).toHaveLength(5);
    });

    it('renders load buttons for class-name values', () => {
        renderPage();

        const loadButtons = screen.getAllByLabelText('Load object state');
        expect(loadButtons).toHaveLength(5);
    });

    it('loads object when load button clicked', async () => {
        const user = userEvent.setup();
        renderPage();

        const loadButtons = screen.getAllByLabelText('Load object state');
        await user.click(loadButtons[0]);

        expect(mockLazyLoadObject).toHaveBeenCalledWith('assetManager');
    });

    it('shows error message when load fails', async () => {
        mockLazyLoadObject.mockResolvedValueOnce({
            error: {status: 500, data: {error: 'Class "Foo" is not registered in the DI container.'}},
        });
        const user = userEvent.setup();
        renderPage();

        const loadButtons = screen.getAllByLabelText('Load object state');
        await user.click(loadButtons[0]);

        expect(await screen.findByText('Class "Foo" is not registered in the DI container.')).toBeInTheDocument();
    });

    it('shows retry button after load error', async () => {
        mockLazyLoadObject.mockResolvedValueOnce({error: {status: 500, data: {error: 'Some error'}}});
        const user = userEvent.setup();
        renderPage();

        const loadButtons = screen.getAllByLabelText('Load object state');
        await user.click(loadButtons[0]);

        expect(await screen.findByLabelText('Retry loading')).toBeInTheDocument();
    });
});
