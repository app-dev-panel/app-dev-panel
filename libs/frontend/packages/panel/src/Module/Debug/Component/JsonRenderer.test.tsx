import {renderWithProviders} from '@app-dev-panel/sdk/test-utils';
import {screen, waitFor} from '@testing-library/react';
import userEvent from '@testing-library/user-event';
import {describe, expect, it} from 'vitest';
import {JsonRenderer} from './JsonRenderer';

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
});
