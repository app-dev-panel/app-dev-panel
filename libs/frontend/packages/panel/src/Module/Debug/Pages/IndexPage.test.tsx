import {renderWithProviders} from '@app-dev-panel/sdk/test-utils';
import {screen} from '@testing-library/react';
import {describe, expect, it} from 'vitest';
import {IndexPage} from './IndexPage';

describe('IndexPage (Overview Dashboard)', () => {
    const webEntry = {
        id: 'test-123',
        collectors: [
            'AppDevPanel\\Kernel\\Collector\\LogCollector',
            'AppDevPanel\\Kernel\\Collector\\EventCollector',
            'AppDevPanel\\Kernel\\Collector\\ServiceCollector',
            'AppDevPanel\\Kernel\\Collector\\ExceptionCollector',
            'AppDevPanel\\Kernel\\Collector\\TimelineCollector',
        ],
        logger: {total: 15},
        event: {total: 42},
        service: {total: 8},
        exception: {},
        timeline: {total: 3},
        web: {
            php: {version: '8.4.0'},
            request: {startTime: 1705319445, processingTime: 0.025},
            memory: {peakUsage: 4194304},
        },
        request: {
            url: '/api/users',
            path: '/api/users',
            query: '',
            method: 'GET' as const,
            isAjax: false,
            userIp: '127.0.0.1',
        },
        response: {statusCode: 200},
        router: {
            matchTime: 0.001,
            name: 'api-users',
            pattern: '/api/users',
            arguments: '',
            host: '',
            uri: '/api/users',
            action: 'App\\Controller\\UserController',
        },
    };

    it('shows no entry message when no debug entry', () => {
        renderWithProviders(<IndexPage />);
        expect(screen.getByText('No debug entry selected')).toBeInTheDocument();
    });

    it('renders summary bar with request info', () => {
        renderWithProviders(<IndexPage />, {
            preloadedState: {'store.debug': {entry: webEntry, currentPageRequestIds: []}},
        });
        expect(screen.getByText('Request')).toBeInTheDocument();
        expect(screen.getByText('Status')).toBeInTheDocument();
        expect(screen.getByText('Duration')).toBeInTheDocument();
        expect(screen.getByText('Peak Memory')).toBeInTheDocument();
    });

    it('renders collector cards with labels', () => {
        renderWithProviders(<IndexPage />, {
            preloadedState: {'store.debug': {entry: webEntry, currentPageRequestIds: []}},
        });
        expect(screen.getByText('Log')).toBeInTheDocument();
        expect(screen.getByText('Events')).toBeInTheDocument();
        expect(screen.getByText('Service')).toBeInTheDocument();
        expect(screen.getByText('Timeline')).toBeInTheDocument();
    });

    it('renders badge counts on collector cards', () => {
        renderWithProviders(<IndexPage />, {
            preloadedState: {'store.debug': {entry: webEntry, currentPageRequestIds: []}},
        });
        expect(screen.getByText('15')).toBeInTheDocument();
        expect(screen.getByText('42')).toBeInTheDocument();
        expect(screen.getByText('8')).toBeInTheDocument();
    });

    it('renders status code in summary bar', () => {
        renderWithProviders(<IndexPage />, {
            preloadedState: {'store.debug': {entry: webEntry, currentPageRequestIds: []}},
        });
        expect(screen.getByText('200')).toBeInTheDocument();
    });

    it('renders card summaries', () => {
        renderWithProviders(<IndexPage />, {
            preloadedState: {'store.debug': {entry: webEntry, currentPageRequestIds: []}},
        });
        expect(screen.getByText('15 log')).toBeInTheDocument();
        expect(screen.getByText('42 events')).toBeInTheDocument();
    });
});
