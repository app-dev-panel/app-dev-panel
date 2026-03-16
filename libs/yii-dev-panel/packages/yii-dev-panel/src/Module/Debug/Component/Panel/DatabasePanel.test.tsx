import {screen} from '@testing-library/react';
import userEvent from '@testing-library/user-event';
import {renderWithProviders} from '@yiisoft/yii-dev-panel-sdk/test-utils';
import {describe, expect, it} from 'vitest';
import {DatabasePanel} from './DatabasePanel';

const makeQuery = (
    overrides: Partial<{sql: string; rawSql: string; params: Record<string, any>; rowsNumber: number}> = {},
) => ({
    sql: 'SELECT * FROM users WHERE id = :id',
    rawSql: "SELECT * FROM users WHERE id = '1'",
    line: '/src/Repository.php:25',
    params: {':id': 1},
    status: 'success' as const,
    actions: [
        {action: 'query.start' as const, time: 0.001},
        {action: 'query.end' as const, time: 0.005},
    ],
    rowsNumber: 1,
    ...overrides,
});

describe('DatabasePanel', () => {
    it('shows empty message when no queries', () => {
        renderWithProviders(<DatabasePanel data={{queries: [], transactions: []}} />);
        expect(screen.getByText(/No queries found/)).toBeInTheDocument();
    });

    it('renders tabs when queries exist', () => {
        renderWithProviders(<DatabasePanel data={{queries: [makeQuery()]}} />);
        expect(screen.getByText('queries')).toBeInTheDocument();
    });

    it('renders SQL text', () => {
        renderWithProviders(<DatabasePanel data={{queries: [makeQuery({sql: 'INSERT INTO logs VALUES (1)'})]}} />);
        expect(screen.getByText('INSERT INTO logs VALUES (1)')).toBeInTheDocument();
    });

    it('renders SQL type badge from first keyword', () => {
        renderWithProviders(<DatabasePanel data={{queries: [makeQuery({sql: 'SELECT * FROM users'})]}} />);
        expect(screen.getByText('SELECT')).toBeInTheDocument();
    });

    it('renders row count', () => {
        renderWithProviders(<DatabasePanel data={{queries: [makeQuery({rowsNumber: 5})]}} />);
        expect(screen.getByText('5 rows')).toBeInTheDocument();
    });

    it('renders singular row count', () => {
        renderWithProviders(<DatabasePanel data={{queries: [makeQuery({rowsNumber: 1})]}} />);
        expect(screen.getByText('1 row')).toBeInTheDocument();
    });

    it('renders query count summary', () => {
        renderWithProviders(<DatabasePanel data={{queries: [makeQuery(), makeQuery()]}} />);
        expect(screen.getByText(/2 queries/)).toBeInTheDocument();
    });

    it('renders filter input', () => {
        renderWithProviders(<DatabasePanel data={{queries: [makeQuery()]}} />);
        expect(screen.getByPlaceholderText('Filter SQL...')).toBeInTheDocument();
    });

    it('filters queries by SQL content', async () => {
        const user = userEvent.setup();
        const data = {
            queries: [makeQuery({sql: 'SELECT * FROM users'}), makeQuery({sql: 'INSERT INTO logs VALUES (1)'})],
        };
        renderWithProviders(<DatabasePanel data={data} />);
        const input = screen.getByPlaceholderText('Filter SQL...');
        await user.type(input, 'INSERT');
        expect(screen.getByText('INSERT INTO logs VALUES (1)')).toBeInTheDocument();
        expect(screen.queryByText('SELECT * FROM users')).not.toBeInTheDocument();
    });

    it('expands query detail on click', async () => {
        const user = userEvent.setup();
        renderWithProviders(<DatabasePanel data={{queries: [makeQuery({params: {':id': 42}})]}} />);
        await user.click(screen.getAllByText(/SELECT \* FROM users/)[0]);
        expect(screen.getByText('Parameters')).toBeInTheDocument();
        expect(screen.getByText('Raw SQL')).toBeInTheDocument();
    });

    it('shows transactions tab as not supported', async () => {
        const user = userEvent.setup();
        renderWithProviders(<DatabasePanel data={{queries: [makeQuery()], transactions: []}} />);
        await user.click(screen.getByText('transactions'));
        expect(screen.getByText(/Not supported yet/)).toBeInTheDocument();
    });
});
