import {renderWithProviders} from '@app-dev-panel/sdk/test-utils';
import {screen} from '@testing-library/react';
import userEvent from '@testing-library/user-event';
import {describe, expect, it, vi} from 'vitest';
import {CommandPalette} from './CommandPalette';

describe('CommandPalette', () => {
    it('does not render content when closed', () => {
        renderWithProviders(<CommandPalette open={false} onClose={vi.fn()} />);
        expect(screen.queryByPlaceholderText('Search pages, actions, entries...')).not.toBeInTheDocument();
    });

    it('renders search input when open', () => {
        renderWithProviders(<CommandPalette open={true} onClose={vi.fn()} />);
        expect(screen.getByPlaceholderText('Search pages, actions, entries...')).toBeInTheDocument();
    });

    it('renders default page items', () => {
        renderWithProviders(<CommandPalette open={true} onClose={vi.fn()} />);
        expect(screen.getByText('Pages')).toBeInTheDocument();
        expect(screen.getAllByText('Debug').length).toBeGreaterThanOrEqual(1);
        expect(screen.getByText('Inspector > Routes')).toBeInTheDocument();
        expect(screen.getByText('Inspector > Configuration')).toBeInTheDocument();
    });

    it('filters items by query', async () => {
        const user = userEvent.setup();
        renderWithProviders(<CommandPalette open={true} onClose={vi.fn()} />);
        const input = screen.getByPlaceholderText('Search pages, actions, entries...');
        await user.type(input, 'database');
        expect(screen.getByText('Inspector > Storage > Database')).toBeInTheDocument();
        expect(screen.queryByText('Inspector > Routes')).not.toBeInTheDocument();
    });

    it('shows no results message for unmatched query', async () => {
        const user = userEvent.setup();
        renderWithProviders(<CommandPalette open={true} onClose={vi.fn()} />);
        const input = screen.getByPlaceholderText('Search pages, actions, entries...');
        await user.type(input, 'xyznonexistent');
        expect(screen.getByText('No results found')).toBeInTheDocument();
    });

    it('renders Esc keyboard shortcut', () => {
        renderWithProviders(<CommandPalette open={true} onClose={vi.fn()} />);
        expect(screen.getByText('Esc')).toBeInTheDocument();
    });
});
