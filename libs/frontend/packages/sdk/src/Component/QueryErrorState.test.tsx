import {fireEvent, screen} from '@testing-library/react';
import {describe, expect, it, vi} from 'vitest';
import {renderWithProviders} from '../test-utils';
import {QueryErrorState} from './QueryErrorState';

describe('QueryErrorState', () => {
    it('renders the title and extracted error message', () => {
        renderWithProviders(
            <QueryErrorState error={{data: {error: 'Server is on fire'}}} title="Failed to load routes" />,
        );
        expect(screen.getByText('Failed to load routes')).toBeInTheDocument();
        expect(screen.getByText('Server is on fire')).toBeInTheDocument();
    });

    it('shows connection message for FETCH_ERROR', () => {
        renderWithProviders(<QueryErrorState error={{status: 'FETCH_ERROR'}} />);
        expect(screen.getByText(/Unable to connect to the server/)).toBeInTheDocument();
    });

    it('renders retry button when onRetry is provided', () => {
        const onRetry = vi.fn();
        renderWithProviders(<QueryErrorState error={null} onRetry={onRetry} />);
        const button = screen.getByRole('button', {name: /retry/i});
        fireEvent.click(button);
        expect(onRetry).toHaveBeenCalledTimes(1);
    });

    it('does not render retry button when onRetry is not provided', () => {
        renderWithProviders(<QueryErrorState error={null} />);
        expect(screen.queryByRole('button', {name: /retry/i})).not.toBeInTheDocument();
    });
});
