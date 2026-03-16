import {screen} from '@testing-library/react';
import userEvent from '@testing-library/user-event';
import {renderWithProviders} from '@yiisoft/yii-dev-panel-sdk/test-utils';
import {describe, expect, it} from 'vitest';
import {MiddlewarePanel} from './MiddlewarePanel';

const preloadedState = {'store.debug': {entry: {id: 'test-1', collectors: []}, currentPageRequestIds: []}};

const makeProps = () => ({
    beforeStack: [
        {memory: 1048576, name: 'App\\Middleware\\AuthMiddleware', time: 1705319445.1, request: '{}'},
        {memory: 2097152, name: 'App\\Middleware\\CorsMiddleware', time: 1705319445.12, request: '{}'},
    ],
    afterStack: [{memory: 3145728, name: 'App\\Middleware\\ResponseMiddleware', time: 1705319445.2, response: '{}'}],
    actionHandler: {
        memory: 2621440,
        name: 'App\\Handler\\UserController',
        request: '{}',
        startTime: 1705319445.15,
        endTime: 1705319445.18,
    },
});

describe('MiddlewarePanel', () => {
    it('shows empty message when all stacks are empty and no handler', () => {
        renderWithProviders(<MiddlewarePanel beforeStack={[]} afterStack={[]} actionHandler={null as any} />, {
            preloadedState,
        });
        expect(screen.getByText(/No middleware data found/)).toBeInTheDocument();
    });

    it('renders section title with total count', () => {
        renderWithProviders(<MiddlewarePanel {...makeProps()} />, {preloadedState});
        // 2 before + 1 handler + 1 after = 4
        expect(screen.getByText('4 middleware steps')).toBeInTheDocument();
    });

    it('renders BEFORE phase badges', () => {
        renderWithProviders(<MiddlewarePanel {...makeProps()} />, {preloadedState});
        expect(screen.getAllByText('BEFORE').length).toBe(2);
    });

    it('renders HANDLER phase badge', () => {
        renderWithProviders(<MiddlewarePanel {...makeProps()} />, {preloadedState});
        expect(screen.getByText('HANDLER')).toBeInTheDocument();
    });

    it('renders AFTER phase badge', () => {
        renderWithProviders(<MiddlewarePanel {...makeProps()} />, {preloadedState});
        expect(screen.getByText('AFTER')).toBeInTheDocument();
    });

    it('renders short class names', () => {
        renderWithProviders(<MiddlewarePanel {...makeProps()} />, {preloadedState});
        expect(screen.getByText('AuthMiddleware')).toBeInTheDocument();
        expect(screen.getByText('CorsMiddleware')).toBeInTheDocument();
        expect(screen.getByText('UserController')).toBeInTheDocument();
        expect(screen.getByText('ResponseMiddleware')).toBeInTheDocument();
    });

    it('renders memory usage for non-zero memory', () => {
        renderWithProviders(<MiddlewarePanel {...makeProps()} />, {preloadedState});
        expect(screen.getByText('1.0 MB')).toBeInTheDocument(); // 1048576 bytes
        expect(screen.getByText('2.0 MB')).toBeInTheDocument(); // 2097152 bytes
    });

    it('expands detail on click', async () => {
        const user = userEvent.setup();
        renderWithProviders(<MiddlewarePanel {...makeProps()} />, {preloadedState});
        await user.click(screen.getByText('AuthMiddleware'));
        expect(screen.getByText('App\\Middleware\\AuthMiddleware')).toBeInTheDocument();
    });

    it('renders only handler when stacks are empty', () => {
        const props = {
            beforeStack: [],
            afterStack: [],
            actionHandler: {memory: 0, name: 'App\\Handler', request: '{}', startTime: 0, endTime: 0},
        };
        renderWithProviders(<MiddlewarePanel {...props} />, {preloadedState});
        expect(screen.getByText('Handler')).toBeInTheDocument();
        expect(screen.getByText('1 middleware steps')).toBeInTheDocument();
    });
});
