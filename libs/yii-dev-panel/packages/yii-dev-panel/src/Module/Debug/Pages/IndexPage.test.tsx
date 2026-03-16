import {screen} from '@testing-library/react';
import {renderWithProviders} from '@yiisoft/yii-dev-panel-sdk/test-utils';
import {describe, expect, it} from 'vitest';
import {IndexPage} from './IndexPage';

describe('IndexPage (Overview)', () => {
    const webEntry = {
        id: 'test-123',
        collectors: ['Yiisoft\\Yii\\Debug\\Collector\\LogCollector'],
        logger: {total: 15},
        event: {total: 42},
        service: {total: 8},
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

    it('renders summary metrics for web entry', () => {
        renderWithProviders(<IndexPage />, {
            preloadedState: {'store.debug': {entry: webEntry, currentPageRequestIds: []}},
        });
        expect(screen.getByText('Summary')).toBeInTheDocument();
        expect(screen.getByText('Response Time')).toBeInTheDocument();
        expect(screen.getByText('Peak Memory')).toBeInTheDocument();
        expect(screen.getByText('Log Entries')).toBeInTheDocument();
        expect(screen.getByText('Events')).toBeInTheDocument();
        expect(screen.getByText('Services')).toBeInTheDocument();
    });

    it('renders request section for web entry', () => {
        renderWithProviders(<IndexPage />, {
            preloadedState: {'store.debug': {entry: webEntry, currentPageRequestIds: []}},
        });
        expect(screen.getByText('Request')).toBeInTheDocument();
        expect(screen.getByText('GET')).toBeInTheDocument();
        expect(screen.getByText('127.0.0.1')).toBeInTheDocument();
    });

    it('renders route section', () => {
        renderWithProviders(<IndexPage />, {
            preloadedState: {'store.debug': {entry: webEntry, currentPageRequestIds: []}},
        });
        expect(screen.getByText('Route')).toBeInTheDocument();
        expect(screen.getByText('api-users')).toBeInTheDocument();
        expect(screen.getAllByText('/api/users').length).toBeGreaterThanOrEqual(1);
    });

    it('renders environment section', () => {
        renderWithProviders(<IndexPage />, {
            preloadedState: {'store.debug': {entry: webEntry, currentPageRequestIds: []}},
        });
        expect(screen.getByText('Environment')).toBeInTheDocument();
        expect(screen.getByText('8.4.0')).toBeInTheDocument();
        expect(screen.getByText('test-123')).toBeInTheDocument();
    });
});
