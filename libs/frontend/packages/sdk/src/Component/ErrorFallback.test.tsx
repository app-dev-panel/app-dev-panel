import {screen} from '@testing-library/react';
import userEvent from '@testing-library/user-event';
import {describe, expect, it, vi} from 'vitest';
import {renderWithProviders} from '../test-utils';
import {ErrorFallback} from './ErrorFallback';

describe('ErrorFallback', () => {
    const error = new Error('Test error message');
    error.stack = 'Error: Test error message\n    at test.ts:1:1';

    it('renders error message', () => {
        renderWithProviders(<ErrorFallback error={error} resetErrorBoundary={() => {}} />);
        expect(screen.getByText('Test error message')).toBeInTheDocument();
    });

    it('renders "Something went wrong" title', () => {
        renderWithProviders(<ErrorFallback error={error} resetErrorBoundary={() => {}} />);
        expect(screen.getByText('Something went wrong:')).toBeInTheDocument();
    });

    it('renders Try again button', () => {
        renderWithProviders(<ErrorFallback error={error} resetErrorBoundary={() => {}} />);
        expect(screen.getByText('Try again')).toBeInTheDocument();
    });

    it('calls resetErrorBoundary on Try again click', async () => {
        const user = userEvent.setup();
        const reset = vi.fn();
        renderWithProviders(<ErrorFallback error={error} resetErrorBoundary={reset} />);
        await user.click(screen.getByText('Try again'));
        expect(reset).toHaveBeenCalledOnce();
    });
});
