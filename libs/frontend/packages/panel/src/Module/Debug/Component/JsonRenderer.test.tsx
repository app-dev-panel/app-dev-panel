import {renderWithProviders} from '@app-dev-panel/sdk/test-utils';
import {screen, waitFor} from '@testing-library/react';
import userEvent from '@testing-library/user-event';
import {describe, expect, it, vi} from 'vitest';
import {JsonRenderer} from './JsonRenderer';

vi.mock('@app-dev-panel/panel/Module/Inspector/API/Inspector', () => ({
    useGetClassQuery: vi.fn(() => ({data: undefined})),
}));

const debugEntry = {id: 'entry-1', collectors: []};

const renderWithEntry = (value: any) => {
    const result = renderWithProviders(<JsonRenderer value={value} />, {
        preloadedState: {'store.debug': {entry: debugEntry, currentPageRequestIds: []}},
        route: '/debug?collector=test',
    });
    return result;
};

describe('JsonRenderer', () => {
    it('renders object with string values', () => {
        renderWithEntry({message: 'hello world'});
        expect(screen.getByText('message')).toBeInTheDocument();
    });

    it('renders object reference as a clickable link', async () => {
        renderWithEntry({key: 'object@App\\MyClass#42'});

        const link = await screen.findByText('App\\MyClass#42');
        expect(link).toBeInTheDocument();
        expect(link.tagName).toBe('BUTTON');
    });

    it('renders load object button next to object reference', async () => {
        renderWithEntry({key: 'object@App\\Service#7'});

        const link = await screen.findByText('App\\Service#7');
        expect(link).toBeInTheDocument();

        const loadButton = screen.getByLabelText('Load object state');
        expect(loadButton).toBeInTheDocument();
    });

    it('object link navigates via React Router on click', async () => {
        const user = userEvent.setup();
        renderWithEntry({key: 'object@App\\Foo#99'});

        const link = await screen.findByText('App\\Foo#99');
        await user.click(link);

        // After click, the URL should have changed via React Router (no full reload)
        // The MemoryRouter in test-utils tracks this
        await waitFor(() => {
            expect(window.location.pathname).not.toBe('/debug/object');
        });
    });

    it('renders numeric values', () => {
        renderWithEntry(42);
        expect(screen.getByText('42')).toBeInTheDocument();
    });

    it('renders boolean values', () => {
        renderWithEntry(true);
        expect(screen.getByText('true')).toBeInTheDocument();
    });

    it('renders a FQCN string as the ClassName component with a File Explorer button', async () => {
        renderWithEntry({service: 'App\\Service\\UserService'});

        expect(await screen.findByText('App\\Service\\UserService')).toBeInTheDocument();
        expect(screen.getByLabelText('Open in File Explorer')).toBeInTheDocument();
    });

    it('renders a nested FQCN inside an array as ClassName', async () => {
        renderWithEntry({handlers: ['App\\Handler\\Foo', 'App\\Handler\\Bar']});

        expect(await screen.findByText('App\\Handler\\Foo')).toBeInTheDocument();
        expect(screen.getByText('App\\Handler\\Bar')).toBeInTheDocument();
        expect(screen.getAllByLabelText('Open in File Explorer')).toHaveLength(2);
    });

    it('does not render a plain string as ClassName', () => {
        renderWithEntry({message: 'just a regular string'});

        expect(screen.getByText('just a regular string')).toBeInTheDocument();
        expect(screen.queryByLabelText('Open in File Explorer')).not.toBeInTheDocument();
    });

    it('does not render a short non-namespaced identifier as ClassName', () => {
        renderWithEntry({handler: 'UserService'});

        expect(screen.queryByLabelText('Open in File Explorer')).not.toBeInTheDocument();
    });
});
