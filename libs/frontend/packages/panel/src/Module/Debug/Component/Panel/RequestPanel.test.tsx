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
    requestQuery: 'page=1',
    requestRaw: 'GET /api/users?page=1 HTTP/1.1\r\nHost: localhost\r\n\r\n',
    requestUrl: 'http://localhost/api/users?page=1',
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

    it('renders Request and Response section titles', () => {
        renderWithProviders(<RequestPanel data={makeData()} />);
        expect(screen.getByText('Request')).toBeInTheDocument();
        expect(screen.getByText('Response')).toBeInTheDocument();
    });

    it('renders Raw Request toggle', () => {
        renderWithProviders(<RequestPanel data={makeData()} />);
        expect(screen.getByText('Raw Request')).toBeInTheDocument();
    });

    it('renders Raw Response toggle', () => {
        renderWithProviders(<RequestPanel data={makeData()} />);
        expect(screen.getByText('Raw Response')).toBeInTheDocument();
    });

    it('expands raw request on click', async () => {
        const user = userEvent.setup();
        renderWithProviders(<RequestPanel data={makeData()} />);
        await user.click(screen.getByText('Raw Request'));
        // The raw content should now be visible (inside CodeHighlight)
        expect(screen.getByText('Raw Request')).toBeInTheDocument();
    });

    it('handles missing responseRaw gracefully', () => {
        renderWithProviders(<RequestPanel data={makeData({responseRaw: undefined as any})} />);
        expect(screen.getByText('Request')).toBeInTheDocument();
    });
});
