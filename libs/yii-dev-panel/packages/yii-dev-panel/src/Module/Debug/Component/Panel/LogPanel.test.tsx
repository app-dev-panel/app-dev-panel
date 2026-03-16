import {screen} from '@testing-library/react';
import userEvent from '@testing-library/user-event';
import {renderWithProviders} from '@yiisoft/yii-dev-panel-sdk/test-utils';
import {describe, expect, it} from 'vitest';
import {LogPanel} from './LogPanel';

describe('LogPanel', () => {
    it('shows empty message when no data', () => {
        renderWithProviders(<LogPanel data={[]} />);
        expect(screen.getByText(/No logs found/)).toBeInTheDocument();
    });

    it('shows empty message when data is null', () => {
        renderWithProviders(<LogPanel data={null as any} />);
        expect(screen.getByText(/No logs found/)).toBeInTheDocument();
    });

    it('renders log entries with level badges', () => {
        const data = [
            {
                context: {key: 'value'},
                level: 'info' as const,
                line: '/src/app.php:42',
                message: 'Test log message',
                time: 1705319445,
            },
            {context: {}, level: 'error' as const, line: '/src/b.php:2', message: 'Error occurred', time: 1705319446},
        ];
        renderWithProviders(<LogPanel data={data} />);
        expect(screen.getByText('Test log message')).toBeInTheDocument();
        expect(screen.getByText('Error occurred')).toBeInTheDocument();
        expect(screen.getByText('INFO')).toBeInTheDocument();
        expect(screen.getByText('ERROR')).toBeInTheDocument();
    });

    it('shows entry count in section title', () => {
        const data = [
            {context: {}, level: 'info' as const, line: '', message: 'First', time: 1},
            {context: {}, level: 'debug' as const, line: '', message: 'Second', time: 2},
        ];
        renderWithProviders(<LogPanel data={data} />);
        expect(screen.getByText('2 log entries')).toBeInTheDocument();
    });

    it('filters log entries by message', async () => {
        const user = userEvent.setup();
        const data = [
            {context: {}, level: 'info' as const, line: '', message: 'Database connected', time: 1},
            {context: {}, level: 'error' as const, line: '', message: 'File not found', time: 2},
        ];
        renderWithProviders(<LogPanel data={data} />);
        const input = screen.getByPlaceholderText('Filter logs...');
        await user.type(input, 'Database');
        expect(screen.getByText('Database connected')).toBeInTheDocument();
        expect(screen.queryByText('File not found')).not.toBeInTheDocument();
    });

    it('expands log entry on click to show context', async () => {
        const user = userEvent.setup();
        const data = [
            {context: {userId: 42}, level: 'info' as const, line: '/src/app.php:10', message: 'User login', time: 1},
        ];
        renderWithProviders(<LogPanel data={data} />);
        await user.click(screen.getByText('User login'));
        expect(screen.getByText('/src/app.php:10')).toBeInTheDocument();
    });
});
