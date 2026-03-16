import {screen} from '@testing-library/react';
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

    it('renders log entries', () => {
        const data = [
            {
                context: {key: 'value'},
                level: 'info' as const,
                line: '/src/app.php:42',
                message: 'Test log message',
                time: 1705319445,
            },
        ];
        renderWithProviders(<LogPanel data={data} />);
        expect(screen.getByText('Test log message')).toBeInTheDocument();
    });

    it('renders multiple log entries', () => {
        const data = [
            {context: {}, level: 'info' as const, line: '/src/a.php:1', message: 'First', time: 1},
            {context: {}, level: 'error' as const, line: '/src/b.php:2', message: 'Second', time: 2},
        ];
        renderWithProviders(<LogPanel data={data} />);
        expect(screen.getByText('First')).toBeInTheDocument();
        expect(screen.getByText('Second')).toBeInTheDocument();
    });
});
