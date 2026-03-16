import {screen} from '@testing-library/react';
import userEvent from '@testing-library/user-event';
import {renderWithProviders} from '@yiisoft/yii-dev-panel-sdk/test-utils';
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
        expect(screen.getByText(/No operations with file system/)).toBeInTheDocument();
    });

    it('shows empty message when data is null', () => {
        renderWithProviders(<FilesystemPanel data={null as any} />);
        expect(screen.getByText(/No operations with file system/)).toBeInTheDocument();
    });

    it('renders tabs for each operation type', () => {
        renderWithProviders(<FilesystemPanel data={makeData() as any} />);
        expect(screen.getByText('read')).toBeInTheDocument();
        expect(screen.getByText('readdir')).toBeInTheDocument();
    });

    it('renders count chips on tabs', () => {
        renderWithProviders(<FilesystemPanel data={makeData() as any} />);
        // Each tab should show its item count
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
        expect(screen.getByText('1 operations')).toBeInTheDocument();
    });

    it('switches tab on click', async () => {
        const user = userEvent.setup();
        renderWithProviders(<FilesystemPanel data={makeData() as any} />);
        await user.click(screen.getByText('readdir'));
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
        await user.click(screen.getByText('readdir'));
        expect(screen.getByText(/No readdir operations found/)).toBeInTheDocument();
    });
});
