import {renderWithProviders} from '@app-dev-panel/sdk/test-utils';
import {screen, within} from '@testing-library/react';
import userEvent from '@testing-library/user-event';
import {describe, expect, it} from 'vitest';
import {FilesystemPanel} from './FilesystemPanel';

const makeData = (ops: Record<string, {path: string; args: Record<string, any>}[]> = {}) => ({
    read: [{path: '/var/www/config.php', args: {mode: 'r'}}],
    readdir: [{path: '/var/www/uploads', args: {}}],
    ...ops,
});

describe('FilesystemPanel', () => {
    it('shows empty message when no data', () => {
        renderWithProviders(<FilesystemPanel data={[] as any} />);
        expect(screen.getByText(/No filesystem operations found/)).toBeInTheDocument();
    });

    it('shows empty message when data is null', () => {
        renderWithProviders(<FilesystemPanel data={null as any} />);
        expect(screen.getByText(/No filesystem operations found/)).toBeInTheDocument();
    });

    it('renders tabs for each operation type', () => {
        renderWithProviders(<FilesystemPanel data={makeData() as any} />);
        const tablist = screen.getByRole('tablist');
        expect(within(tablist).getByText('read')).toBeInTheDocument();
        expect(within(tablist).getByText('readdir')).toBeInTheDocument();
    });

    it('renders count chips on tabs', () => {
        renderWithProviders(<FilesystemPanel data={makeData() as any} />);
        expect(screen.getAllByText('1').length).toBeGreaterThanOrEqual(2);
    });

    it('renders file path in first tab', () => {
        renderWithProviders(<FilesystemPanel data={makeData() as any} />);
        expect(screen.getByText('/var/www/config.php')).toBeInTheDocument();
    });

    it('renders Open chip links', () => {
        renderWithProviders(<FilesystemPanel data={makeData() as any} />);
        expect(screen.getAllByText('Open').length).toBeGreaterThanOrEqual(1);
    });

    it('renders operations count', () => {
        renderWithProviders(<FilesystemPanel data={makeData() as any} />);
        expect(screen.getByText('1 operation')).toBeInTheDocument();
    });

    it('switches tab on click', async () => {
        const user = userEvent.setup();
        renderWithProviders(<FilesystemPanel data={makeData() as any} />);
        const tablist = screen.getByRole('tablist');
        await user.click(within(tablist).getByText('readdir'));
        expect(screen.getByText('/var/www/uploads')).toBeInTheDocument();
    });

    it('expands detail on click when args exist', async () => {
        const user = userEvent.setup();
        renderWithProviders(<FilesystemPanel data={makeData() as any} />);
        await user.click(screen.getByText('/var/www/config.php'));
        expect(screen.getByText('Arguments')).toBeInTheDocument();
    });

    it('shows empty message for operation with no items', async () => {
        const user = userEvent.setup();
        const data = makeData({readdir: [], mkdir: []});
        renderWithProviders(<FilesystemPanel data={data as any} />);
        const tablist = screen.getByRole('tablist');
        await user.click(within(tablist).getByText('readdir'));
        expect(screen.getByText(/No readdir operations found/)).toBeInTheDocument();
    });

    it('renders section title', () => {
        renderWithProviders(<FilesystemPanel data={makeData() as any} />);
        expect(screen.getByText('Operations')).toBeInTheDocument();
    });

    it('renders filter input', () => {
        renderWithProviders(<FilesystemPanel data={makeData() as any} />);
        expect(screen.getByPlaceholderText('Filter by path...')).toBeInTheDocument();
    });

    it('filters operations by path', async () => {
        const user = userEvent.setup();
        renderWithProviders(<FilesystemPanel data={makeData() as any} />);
        const input = screen.getByPlaceholderText('Filter by path...');
        await user.type(input, 'nonexistent');
        expect(screen.getByText(/No matching paths/)).toBeInTheDocument();
    });
});
