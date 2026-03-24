import {renderWithProviders} from '@app-dev-panel/sdk/test-utils';
import {screen} from '@testing-library/react';
import userEvent from '@testing-library/user-event';
import {describe, expect, it} from 'vitest';
import {CachePanel} from './CachePanel';

const makeOperation = (
    overrides: Partial<{
        pool: string;
        operation: string;
        key: string;
        hit: boolean;
        duration: number;
        value: unknown;
    }> = {},
) => ({pool: 'default', operation: 'get', key: 'user:42', hit: true, duration: 0.0015, value: null, ...overrides});

const makeData = (
    overrides: Partial<{operations: any[]; hits: number; misses: number; totalOperations: number}> = {},
) => ({operations: [makeOperation()], hits: 1, misses: 0, totalOperations: 1, ...overrides});

describe('CachePanel', () => {
    it('shows empty message when no data', () => {
        renderWithProviders(<CachePanel data={null as any} />);
        expect(screen.getAllByText(/No cache operations/).length).toBeGreaterThan(0);
    });

    it('shows empty message when totalOperations is 0', () => {
        renderWithProviders(<CachePanel data={makeData({totalOperations: 0})} />);
        expect(screen.getAllByText(/No cache operations/).length).toBeGreaterThan(0);
    });

    it('renders summary cards', () => {
        renderWithProviders(<CachePanel data={makeData({hits: 8, misses: 2, totalOperations: 10})} />);
        expect(screen.getByText('Total Operations')).toBeInTheDocument();
        expect(screen.getByText('10')).toBeInTheDocument();
        expect(screen.getByText('Hit Rate')).toBeInTheDocument();
        expect(screen.getByText('80%')).toBeInTheDocument();
        expect(screen.getByText('Hits')).toBeInTheDocument();
        expect(screen.getByText('8')).toBeInTheDocument();
        expect(screen.getByText('Misses')).toBeInTheDocument();
        expect(screen.getByText('2')).toBeInTheDocument();
    });

    it('renders operation count in section title', () => {
        renderWithProviders(<CachePanel data={makeData()} />);
        expect(screen.getByText('1 operations')).toBeInTheDocument();
    });

    it('renders cache key in row', () => {
        renderWithProviders(<CachePanel data={makeData({operations: [makeOperation({key: 'session:abc'})]})} />);
        expect(screen.getByText('session:abc')).toBeInTheDocument();
    });

    it('renders GET chip with HIT/MISS indicator', () => {
        const ops = [makeOperation({hit: true}), makeOperation({key: 'miss-key', hit: false})];
        renderWithProviders(<CachePanel data={makeData({operations: ops, totalOperations: 2})} />);
        expect(screen.getAllByText('GET').length).toBe(2);
        expect(screen.getByText('HIT')).toBeInTheDocument();
        expect(screen.getByText('MISS')).toBeInTheDocument();
    });

    it('renders SET operation chip', () => {
        const ops = [makeOperation({operation: 'set', key: 'user:1'})];
        renderWithProviders(<CachePanel data={makeData({operations: ops})} />);
        expect(screen.getByText('SET')).toBeInTheDocument();
    });

    it('renders pool chip', () => {
        const ops = [makeOperation({pool: 'redis'})];
        renderWithProviders(<CachePanel data={makeData({operations: ops})} />);
        expect(screen.getByText('redis')).toBeInTheDocument();
    });

    it('filters operations by key', async () => {
        const user = userEvent.setup();
        const ops = [makeOperation({key: 'user:42'}), makeOperation({key: 'session:abc'})];
        renderWithProviders(<CachePanel data={makeData({operations: ops, totalOperations: 2})} />);
        await user.type(screen.getByPlaceholderText('Filter operations...'), 'session');
        expect(screen.getByText('session:abc')).toBeInTheDocument();
        expect(screen.queryByText('user:42')).not.toBeInTheDocument();
    });

    it('expands operation to show value on click', async () => {
        const user = userEvent.setup();
        const ops = [makeOperation({key: 'user:42', value: {name: 'John'}})];
        renderWithProviders(<CachePanel data={makeData({operations: ops})} />);
        await user.click(screen.getByText('user:42'));
        expect(screen.getByText('Value')).toBeInTheDocument();
    });

    it('renders pool breakdown when multiple pools exist', () => {
        const ops = [makeOperation({pool: 'redis'}), makeOperation({pool: 'file', operation: 'set'})];
        renderWithProviders(<CachePanel data={makeData({operations: ops, totalOperations: 2})} />);
        expect(screen.getAllByText('redis').length).toBeGreaterThan(0);
        expect(screen.getAllByText('file').length).toBeGreaterThan(0);
        expect(screen.getByText('Pools')).toBeInTheDocument();
    });
});
