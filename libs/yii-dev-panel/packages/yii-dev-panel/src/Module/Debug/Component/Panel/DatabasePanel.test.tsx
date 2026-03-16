import {screen} from '@testing-library/react';
import {renderWithProviders} from '@yiisoft/yii-dev-panel-sdk/test-utils';
import {describe, expect, it} from 'vitest';
import {DatabasePanel} from './DatabasePanel';

describe('DatabasePanel', () => {
    it('shows empty message when no queries', () => {
        renderWithProviders(<DatabasePanel data={{queries: [], transactions: []}} />);
        expect(screen.getByText(/No queries found/)).toBeInTheDocument();
    });

    it('renders tabs when queries exist', () => {
        const data = {
            queries: [
                {
                    sql: 'SELECT * FROM users',
                    rawSql: 'SELECT * FROM users',
                    line: '/src/app.php:10',
                    params: {},
                    status: 'success' as const,
                    actions: [
                        {action: 'query.start' as const, time: 0.001},
                        {action: 'query.end' as const, time: 0.002},
                    ],
                    rowsNumber: 5,
                },
            ],
        };
        renderWithProviders(<DatabasePanel data={data} />);
        expect(screen.getByText('queries')).toBeInTheDocument();
    });
});
