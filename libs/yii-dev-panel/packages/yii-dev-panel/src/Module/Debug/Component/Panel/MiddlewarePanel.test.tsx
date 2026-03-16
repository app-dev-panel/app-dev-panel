import {screen} from '@testing-library/react';
import {renderWithProviders} from '@yiisoft/yii-dev-panel-sdk/test-utils';
import {describe, expect, it} from 'vitest';
import {MiddlewarePanel} from './MiddlewarePanel';

describe('MiddlewarePanel', () => {
    const preloadedState = {'store.debug': {entry: {id: 'test-1', collectors: []}, currentPageRequestIds: []}};

    it('renders before and after stacks', () => {
        const props = {
            beforeStack: [{memory: 1024, name: 'App\\Middleware\\AuthMiddleware', time: 1705319445.1, request: '{}'}],
            afterStack: [
                {memory: 2048, name: 'App\\Middleware\\ResponseMiddleware', time: 1705319445.2, response: '{}'},
            ],
            actionHandler: {
                memory: 1536,
                name: 'App\\Handler\\ActionHandler',
                request: '{}',
                startTime: 1705319445.15,
                endTime: 1705319445.16,
            },
        };
        renderWithProviders(<MiddlewarePanel {...props} />, {preloadedState});
        expect(screen.getByText('AuthMiddleware')).toBeInTheDocument();
        expect(screen.getByText('ResponseMiddleware')).toBeInTheDocument();
        expect(screen.getByText('ActionHandler')).toBeInTheDocument();
    });

    it('renders with empty stacks but with handler', () => {
        const props = {
            beforeStack: [],
            afterStack: [],
            actionHandler: {memory: 0, name: 'App\\Handler', request: '{}', startTime: 0, endTime: 0},
        };
        renderWithProviders(<MiddlewarePanel {...props} />, {preloadedState});
        expect(screen.getByText('Handler')).toBeInTheDocument();
    });

    it('shows empty message when all stacks are empty and no handler', () => {
        const props = {beforeStack: [], afterStack: [], actionHandler: null as any};
        renderWithProviders(<MiddlewarePanel {...props} />, {preloadedState});
        expect(screen.getByText(/No middleware data found/)).toBeInTheDocument();
    });

    it('shows phase badges', () => {
        const props = {
            beforeStack: [{memory: 1024, name: 'App\\Middleware\\Test', time: 1705319445.1, request: '{}'}],
            afterStack: [],
            actionHandler: {memory: 0, name: 'App\\Handler', request: '{}', startTime: 0, endTime: 0},
        };
        renderWithProviders(<MiddlewarePanel {...props} />, {preloadedState});
        expect(screen.getByText('BEFORE')).toBeInTheDocument();
        expect(screen.getByText('HANDLER')).toBeInTheDocument();
    });
});
