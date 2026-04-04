import {renderWithProviders} from '@app-dev-panel/sdk/test-utils';
import {screen} from '@testing-library/react';
import userEvent from '@testing-library/user-event';
import {describe, expect, it} from 'vitest';
import {HttpClientPanel} from './HttpClientPanel';

type HttpClientEntry = {
    startTime: number;
    endTime: number;
    totalTime: number;
    method: string;
    uri: string;
    headers: Record<string, string[]>;
    line: string;
    responseRaw: string;
    responseStatus: number;
};

const makeEntry = (overrides: Partial<HttpClientEntry> = {}): HttpClientEntry => ({
    startTime: 1700000000.123,
    endTime: 1700000000.456,
    totalTime: 0.333,
    method: 'GET',
    uri: 'https://api.example.com/users?page=1',
    headers: {'Content-Type': ['application/json'], Accept: ['*/*']},
    line: '/var/www/app/src/Service/ApiClient.php:42',
    responseRaw: 'HTTP/1.1 200 OK\r\nContent-Type: application/json\r\n\r\n{"ok":true}',
    responseStatus: 200,
    ...overrides,
});

const preloadedState = {'store.debug': {entry: {id: 'test-1', collectors: []}, currentPageRequestIds: []}};

describe('HttpClientPanel', () => {
    it('shows empty message when data is null', () => {
        renderWithProviders(<HttpClientPanel data={null as any} />, {preloadedState});
        expect(screen.getByText(/No HTTP client requests found/)).toBeInTheDocument();
    });

    it('shows empty message when data is empty array', () => {
        renderWithProviders(<HttpClientPanel data={[]} />, {preloadedState});
        expect(screen.getByText(/No HTTP client requests found/)).toBeInTheDocument();
    });

    it('renders Client and Stream tabs with counts', () => {
        renderWithProviders(<HttpClientPanel data={[makeEntry(), makeEntry()]} />, {preloadedState});
        expect(screen.getByRole('tab', {name: 'Client (2)'})).toBeInTheDocument();
        expect(screen.getByRole('tab', {name: 'Stream (0)'})).toBeInTheDocument();
    });

    it('renders request count in section title', () => {
        const {container} = renderWithProviders(<HttpClientPanel data={[makeEntry()]} />, {preloadedState});
        expect(container.textContent).toContain('1 http requests');
    });

    it('renders method chip for each request', () => {
        renderWithProviders(<HttpClientPanel data={[makeEntry({method: 'POST'})]} />, {preloadedState});
        expect(screen.getByText('POST')).toBeInTheDocument();
    });

    it('renders status code chip', () => {
        renderWithProviders(<HttpClientPanel data={[makeEntry({responseStatus: 404})]} />, {preloadedState});
        expect(screen.getByText('404')).toBeInTheDocument();
    });

    it('renders URI host and path', () => {
        renderWithProviders(<HttpClientPanel data={[makeEntry({uri: 'https://api.example.com/users?page=1'})]} />, {
            preloadedState,
        });
        expect(screen.getByText('api.example.com')).toBeInTheDocument();
        expect(screen.getByText('/users?page=1')).toBeInTheDocument();
    });

    it('renders filter input', () => {
        renderWithProviders(<HttpClientPanel data={[makeEntry()]} />, {preloadedState});
        expect(screen.getByPlaceholderText('Filter requests...')).toBeInTheDocument();
    });

    it('filters requests by URI', async () => {
        const user = userEvent.setup();
        const data = [
            makeEntry({uri: 'https://api.example.com/users', method: 'GET'}),
            makeEntry({uri: 'https://api.example.com/orders', method: 'POST'}),
        ];
        const {container} = renderWithProviders(<HttpClientPanel data={data} />, {preloadedState});
        await user.type(screen.getByPlaceholderText('Filter requests...'), 'orders');
        expect(container.textContent).toContain('1 http requests');
        expect(screen.queryByText('/users')).not.toBeInTheDocument();
    });

    it('renders method filter chips when multiple methods present', () => {
        const data = [makeEntry({method: 'GET'}), makeEntry({method: 'POST'})];
        renderWithProviders(<HttpClientPanel data={data} />, {preloadedState});
        expect(screen.getByText('GET (1)')).toBeInTheDocument();
        expect(screen.getByText('POST (1)')).toBeInTheDocument();
    });

    it('filters by method chip click', async () => {
        const user = userEvent.setup();
        const data = [
            makeEntry({method: 'GET', uri: 'https://example.com/a'}),
            makeEntry({method: 'POST', uri: 'https://example.com/b'}),
        ];
        const {container} = renderWithProviders(<HttpClientPanel data={data} />, {preloadedState});
        await user.click(screen.getByText('GET (1)'));
        expect(container.textContent).toContain('1 http requests');
        // Clear button appears when filter is active
        expect(screen.getByText('Clear')).toBeInTheDocument();
    });

    it('expands request detail on click', async () => {
        const user = userEvent.setup();
        renderWithProviders(<HttpClientPanel data={[makeEntry()]} />, {preloadedState});
        await user.click(screen.getByText('/users?page=1'));
        expect(screen.getByText('Source')).toBeInTheDocument();
        expect(screen.getByText('Timing')).toBeInTheDocument();
        expect(screen.getByText('Request Headers')).toBeInTheDocument();
    });

    it('switches to Stream tab', async () => {
        const user = userEvent.setup();
        renderWithProviders(<HttpClientPanel data={[makeEntry()]} />, {preloadedState});
        await user.click(screen.getByRole('tab', {name: 'Stream (0)'}));
        expect(await screen.findByText(/No HTTP stream data/)).toBeInTheDocument();
    });
});
