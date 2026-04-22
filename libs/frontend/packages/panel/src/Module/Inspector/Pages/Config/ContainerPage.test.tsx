import {DataContextProvider} from '@app-dev-panel/panel/Module/Inspector/Context/DataContext';
import {createAdpTheme} from '@app-dev-panel/sdk/Component/Theme/DefaultTheme';
import {ThemeProvider} from '@mui/material/styles';
import {configureStore} from '@reduxjs/toolkit';
import {render, screen} from '@testing-library/react';
import userEvent from '@testing-library/user-event';
import {Provider} from 'react-redux';
import {MemoryRouter} from 'react-router';
import {describe, expect, it, vi} from 'vitest';
import {ContainerPage} from './ContainerPage';

// ---------------------------------------------------------------------------
// Mock Inspector API
// ---------------------------------------------------------------------------

const mockClasses = [
    'App\\Controller\\HomeController',
    'App\\Service\\UserService',
    'App\\Repository\\PostRepository',
    'Psr\\Log\\LoggerInterface',
    'Psr\\Http\\Client\\ClientInterface',
];

const mockLazyLoadObject = vi.fn().mockResolvedValue({data: {object: {class: 'Test', active: true}, path: '/src'}});

vi.mock('@app-dev-panel/panel/Module/Inspector/API/Inspector', () => ({
    useGetClassesQuery: vi.fn(() => ({data: mockClasses, isLoading: false})),
    useLazyGetObjectQuery: vi.fn(() => [mockLazyLoadObject]),
}));

// ---------------------------------------------------------------------------
// Helpers
// ---------------------------------------------------------------------------

function renderPage(route = '/inspector/config/container') {
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
                        <ContainerPage />
                    </DataContextProvider>
                </MemoryRouter>
            </ThemeProvider>
        </Provider>,
    );
}

// ---------------------------------------------------------------------------
// Tests
// ---------------------------------------------------------------------------

describe('ContainerPage', () => {
    it('renders container entries after loading', () => {
        renderPage();
        expect(screen.getByText('HomeController')).toBeInTheDocument();
        expect(screen.getByText('UserService')).toBeInTheDocument();
        expect(screen.getByText('LoggerInterface')).toBeInTheDocument();
    });

    it('displays total entry count', () => {
        renderPage();
        expect(screen.getByText('5 entries')).toBeInTheDocument();
    });

    it('groups entries by vendor namespace', () => {
        renderPage();
        expect(screen.getByText('App\\Controller')).toBeInTheDocument();
        expect(screen.getByText('App\\Service')).toBeInTheDocument();
        expect(screen.getByText('App\\Repository')).toBeInTheDocument();
        expect(screen.getByText('Psr\\Log')).toBeInTheDocument();
    });

    it('filters entries by class name', async () => {
        const user = userEvent.setup();
        renderPage();

        const input = screen.getByPlaceholderText('Search container entries...');
        await user.type(input, 'UserService');

        expect(screen.getByText('UserService')).toBeInTheDocument();
        expect(screen.queryByText('HomeController')).not.toBeInTheDocument();
        expect(screen.queryByText('LoggerInterface')).not.toBeInTheDocument();
    });

    it('shows filtered count', async () => {
        const user = userEvent.setup();
        renderPage();

        const input = screen.getByPlaceholderText('Search container entries...');
        await user.type(input, 'Psr');

        expect(screen.getByText('2 of 5 entries')).toBeInTheDocument();
    });

    it('shows empty state when filter matches nothing', async () => {
        const user = userEvent.setup();
        renderPage();

        const input = screen.getByPlaceholderText('Search container entries...');
        await user.type(input, 'zzzzzzzzz');

        expect(screen.getByText('No container entries found')).toBeInTheDocument();
    });

    it('renders copy and examine action buttons for each row', () => {
        renderPage();

        const copyButtons = screen.getAllByLabelText('Copy class name');
        const examineButtons = screen.getAllByLabelText('Examine as container entry');

        expect(copyButtons).toHaveLength(5);
        expect(examineButtons).toHaveLength(5);
    });

    it('renders load buttons for unloaded entries', () => {
        renderPage();

        const loadButtons = screen.getAllByLabelText('Load object state');
        expect(loadButtons).toHaveLength(5);
    });

    it('loads object when load button clicked', async () => {
        const user = userEvent.setup();
        renderPage();

        const loadButtons = screen.getAllByLabelText('Load object state');
        await user.click(loadButtons[0]);

        expect(mockLazyLoadObject).toHaveBeenCalledWith('App\\Controller\\HomeController');
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
