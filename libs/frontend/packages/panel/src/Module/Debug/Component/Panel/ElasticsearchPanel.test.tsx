import {renderWithProviders} from '@app-dev-panel/sdk/test-utils';
import {screen} from '@testing-library/react';
import userEvent from '@testing-library/user-event';
import {describe, expect, it} from 'vitest';
import {ElasticsearchPanel} from './ElasticsearchPanel';

const makeRequest = (
    overrides: Partial<{
        method: string;
        endpoint: string;
        index: string;
        body: string;
        status: string;
        statusCode: number;
        duration: number;
        hitsCount: number | null;
    }> = {},
) => ({
    method: 'GET',
    endpoint: '/users/_search',
    index: 'users',
    body: '{"query":{"match_all":{}}}',
    line: '/src/Repo.php:10',
    status: 'success',
    startTime: 1000,
    endTime: 1000.05,
    duration: 0.05,
    statusCode: 200,
    responseBody: '{"hits":{"total":{"value":15}}}',
    responseSize: 512,
    hitsCount: 15,
    exception: null,
    ...overrides,
});

const emptyDuplicates = {groups: [], totalDuplicatedCount: 0};

describe('ElasticsearchPanel', () => {
    it('shows empty message when no requests', () => {
        renderWithProviders(<ElasticsearchPanel data={{requests: [], duplicates: emptyDuplicates}} />);
        expect(screen.getByText(/No Elasticsearch requests found/)).toBeInTheDocument();
    });

    it('renders request list', () => {
        renderWithProviders(<ElasticsearchPanel data={{requests: [makeRequest()], duplicates: emptyDuplicates}} />);
        expect(screen.getByText(/1 elasticsearch requests/)).toBeInTheDocument();
    });

    it('renders method badge', () => {
        renderWithProviders(
            <ElasticsearchPanel data={{requests: [makeRequest({method: 'POST'})], duplicates: emptyDuplicates}} />,
        );
        expect(screen.getByText('POST')).toBeInTheDocument();
    });

    it('renders status code', () => {
        renderWithProviders(
            <ElasticsearchPanel data={{requests: [makeRequest({statusCode: 201})], duplicates: emptyDuplicates}} />,
        );
        expect(screen.getByText('201')).toBeInTheDocument();
    });

    it('renders hits count', () => {
        renderWithProviders(
            <ElasticsearchPanel data={{requests: [makeRequest({hitsCount: 42})], duplicates: emptyDuplicates}} />,
        );
        expect(screen.getByText('42 hits')).toBeInTheDocument();
    });

    it('renders filter input', () => {
        renderWithProviders(<ElasticsearchPanel data={{requests: [makeRequest()], duplicates: emptyDuplicates}} />);
        expect(screen.getByPlaceholderText('Filter requests...')).toBeInTheDocument();
    });

    it('filters requests by endpoint', async () => {
        const user = userEvent.setup();
        const data = {
            requests: [
                makeRequest({endpoint: '/users/_search', index: 'users'}),
                makeRequest({endpoint: '/orders/_doc', index: 'orders', method: 'POST'}),
            ],
            duplicates: emptyDuplicates,
        };
        renderWithProviders(<ElasticsearchPanel data={data} />);
        const input = screen.getByPlaceholderText('Filter requests...');
        await user.type(input, 'orders');
        expect(screen.queryByText(/users/)).not.toBeInTheDocument();
    });

    it('expands request detail on click', async () => {
        const user = userEvent.setup();
        renderWithProviders(<ElasticsearchPanel data={{requests: [makeRequest()], duplicates: emptyDuplicates}} />);
        await user.click(screen.getByText('GET'));
        expect(screen.getByText('Source')).toBeInTheDocument();
        expect(screen.getByText('Timing')).toBeInTheDocument();
        expect(screen.getByText('Request Body')).toBeInTheDocument();
    });

    it('shows duplicate warning when duplicates exist', () => {
        const duplicates = {
            groups: [{key: 'GET /users/_search', count: 3, indices: [0, 1, 2]}],
            totalDuplicatedCount: 3,
        };
        renderWithProviders(
            <ElasticsearchPanel data={{requests: [makeRequest(), makeRequest(), makeRequest()], duplicates}} />,
        );
        expect(screen.getByText(/1 duplicate groups/)).toBeInTheDocument();
    });

    it('shows error badge for error requests', () => {
        renderWithProviders(
            <ElasticsearchPanel
                data={{
                    requests: [makeRequest({status: 'error', statusCode: 0, exception: 'Connection refused'})],
                    duplicates: emptyDuplicates,
                }}
            />,
        );
        expect(screen.getByText('ERR')).toBeInTheDocument();
    });
});
