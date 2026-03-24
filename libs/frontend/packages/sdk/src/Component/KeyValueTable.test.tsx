import {renderWithProviders} from '@app-dev-panel/sdk/test-utils';
import {screen} from '@testing-library/react';
import userEvent from '@testing-library/user-event';
import {describe, expect, it} from 'vitest';
import {KeyValueTable} from './KeyValueTable';

describe('KeyValueTable', () => {
    it('renders key-value rows', () => {
        const rows = [
            {key: 'PHP Version', value: '8.4.0'},
            {key: 'OS', value: 'Linux'},
        ];
        renderWithProviders(<KeyValueTable rows={rows} />);
        expect(screen.getByText('PHP Version')).toBeInTheDocument();
        expect(screen.getByText('8.4.0')).toBeInTheDocument();
        expect(screen.getByText('OS')).toBeInTheDocument();
        expect(screen.getByText('Linux')).toBeInTheDocument();
    });

    it('renders empty table when no rows', () => {
        const {container} = renderWithProviders(<KeyValueTable rows={[]} />);
        expect(container.querySelector('tbody')?.children.length).toBe(0);
    });

    it('renders numeric values', () => {
        renderWithProviders(<KeyValueTable rows={[{key: 'Memory', value: 128}]} />);
        expect(screen.getByText('128')).toBeInTheDocument();
    });

    it('does not show filter when filterable is false (default)', () => {
        renderWithProviders(<KeyValueTable rows={[{key: 'A', value: 'B'}]} />);
        expect(screen.queryByRole('textbox')).not.toBeInTheDocument();
    });

    it('shows filter input when filterable is true', () => {
        renderWithProviders(<KeyValueTable rows={[{key: 'A', value: 'B'}]} filterable />);
        expect(screen.getByRole('textbox')).toBeInTheDocument();
    });

    it('filters rows by key', async () => {
        const user = userEvent.setup();
        const rows = [
            {key: 'PHP Version', value: '8.4'},
            {key: 'Memory Limit', value: '256M'},
        ];
        renderWithProviders(<KeyValueTable rows={rows} filterable />);
        await user.type(screen.getByRole('textbox'), 'Memory');
        expect(screen.getByText('Memory Limit')).toBeInTheDocument();
        expect(screen.queryByText('PHP Version')).not.toBeInTheDocument();
    });

    it('filters rows by value', async () => {
        const user = userEvent.setup();
        const rows = [
            {key: 'PHP Version', value: '8.4'},
            {key: 'Memory Limit', value: '256M'},
        ];
        renderWithProviders(<KeyValueTable rows={rows} filterable />);
        await user.type(screen.getByRole('textbox'), '256');
        expect(screen.getByText('Memory Limit')).toBeInTheDocument();
        expect(screen.queryByText('PHP Version')).not.toBeInTheDocument();
    });

    it('shows all rows when filter is cleared', async () => {
        const user = userEvent.setup();
        const rows = [
            {key: 'A', value: '1'},
            {key: 'B', value: '2'},
        ];
        renderWithProviders(<KeyValueTable rows={rows} filterable />);
        const input = screen.getByRole('textbox');
        await user.type(input, 'A');
        expect(screen.queryByText('B')).not.toBeInTheDocument();
        await user.clear(input);
        expect(screen.getByText('A')).toBeInTheDocument();
        expect(screen.getByText('B')).toBeInTheDocument();
    });
});
