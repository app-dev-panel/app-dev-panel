import {renderWithProviders} from '@app-dev-panel/sdk/test-utils';
import {screen} from '@testing-library/react';
import userEvent from '@testing-library/user-event';
import {describe, expect, it} from 'vitest';
import {RequestPanel} from './RequestPanel';

const makeData = (overrides: Partial<Parameters<typeof RequestPanel>[0]['data']> = {}) => ({
    content: '',
    request: '{"headers": {"Host": "localhost"}}',
    requestIsAjax: false,
    requestMethod: 'GET' as const,
    requestPath: '/api/users',
    requestQuery: 'page=1&limit=10',
    requestRaw: 'GET /api/users?page=1&limit=10 HTTP/1.1\r\nHost: localhost\r\nAccept: application/json\r\n\r\n',
    requestUrl: 'http://localhost/api/users?page=1&limit=10',
    response: '{"status": "ok"}',
    responseRaw: 'HTTP/1.1 200 OK\r\nContent-Type: application/json;\r\n\r\n{"users":[]}',
    responseStatusCode: 200,
    userIp: '127.0.0.1',
    ...overrides,
});

describe('RequestPanel', () => {
    it('shows empty message when data is null', () => {
        renderWithProviders(<RequestPanel data={null as any} />);
        expect(screen.getByText(/Request is not associated/)).toBeInTheDocument();
    });

    it('renders method chip', () => {
        renderWithProviders(<RequestPanel data={makeData({requestMethod: 'POST' as any})} />);
        expect(screen.getByText('POST')).toBeInTheDocument();
    });

    it('renders request URL', () => {
        renderWithProviders(<RequestPanel data={makeData({requestUrl: 'http://example.com/test'})} />);
        expect(screen.getByText('http://example.com/test')).toBeInTheDocument();
    });

    it('renders status code chip', () => {
        renderWithProviders(<RequestPanel data={makeData({responseStatusCode: 404})} />);
        expect(screen.getByText('404')).toBeInTheDocument();
    });

    it('renders AJAX chip when request is AJAX', () => {
        renderWithProviders(<RequestPanel data={makeData({requestIsAjax: true})} />);
        expect(screen.getByText('AJAX')).toBeInTheDocument();
    });

    it('hides AJAX chip when request is not AJAX', () => {
        renderWithProviders(<RequestPanel data={makeData({requestIsAjax: false})} />);
        expect(screen.queryByText('AJAX')).not.toBeInTheDocument();
    });

    it('renders user IP', () => {
        renderWithProviders(<RequestPanel data={makeData({userIp: '192.168.1.1'})} />);
        expect(screen.getByText('192.168.1.1')).toBeInTheDocument();
    });

    it('renders tab labels', () => {
        renderWithProviders(<RequestPanel data={makeData()} />);
        expect(screen.getByRole('tab', {name: 'Request'})).toBeInTheDocument();
        expect(screen.getByRole('tab', {name: 'Response'})).toBeInTheDocument();
        expect(screen.getByRole('tab', {name: 'Raw'})).toBeInTheDocument();
        expect(screen.getByRole('tab', {name: 'Parsed'})).toBeInTheDocument();
    });

    it('shows request headers table on Request tab', () => {
        renderWithProviders(<RequestPanel data={makeData()} />);
        expect(screen.getByText('Headers')).toBeInTheDocument();
        expect(screen.getByText('Host')).toBeInTheDocument();
        expect(screen.getByText('localhost')).toBeInTheDocument();
    });

    it('shows query parameters on Request tab', () => {
        renderWithProviders(<RequestPanel data={makeData()} />);
        expect(screen.getByText('Query Parameters')).toBeInTheDocument();
        expect(screen.getByText('page')).toBeInTheDocument();
        expect(screen.getByText('1')).toBeInTheDocument();
    });

    it('switches to Response tab and shows response headers', async () => {
        const user = userEvent.setup();
        renderWithProviders(<RequestPanel data={makeData()} />);
        await user.click(screen.getByRole('tab', {name: 'Response'}));
        expect(screen.getByText('Content-Type')).toBeInTheDocument();
    });

    it('switches to Raw tab and shows raw data', async () => {
        const user = userEvent.setup();
        renderWithProviders(<RequestPanel data={makeData()} />);
        await user.click(screen.getByRole('tab', {name: 'Raw'}));
        expect(screen.getByText('Raw Request')).toBeInTheDocument();
        expect(screen.getByText('Raw Response')).toBeInTheDocument();
    });

    it('switches to Parsed tab and shows JSON views', async () => {
        const user = userEvent.setup();
        renderWithProviders(<RequestPanel data={makeData()} />);
        await user.click(screen.getByRole('tab', {name: 'Parsed'}));
        expect(screen.getAllByText('Request').length).toBeGreaterThan(0);
        expect(screen.getAllByText('Response').length).toBeGreaterThan(0);
    });

    it('handles missing responseRaw gracefully', () => {
        renderWithProviders(<RequestPanel data={makeData({responseRaw: undefined as any})} />);
        expect(screen.getByRole('tab', {name: 'Request'})).toBeInTheDocument();
    });
});
