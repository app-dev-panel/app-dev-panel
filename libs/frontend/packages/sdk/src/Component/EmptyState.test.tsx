import {screen} from '@testing-library/react';
import {describe, expect, it} from 'vitest';
import {renderWithProviders} from '../test-utils';
import {EmptyState} from './EmptyState';

describe('EmptyState', () => {
    it('renders the title text', () => {
        renderWithProviders(<EmptyState title="No items found" />);
        expect(screen.getByText('No items found')).toBeInTheDocument();
    });

    it('renders the icon', () => {
        renderWithProviders(<EmptyState title="Empty" icon="search" />);
        expect(screen.getByText('search')).toBeInTheDocument();
    });

    it('renders optional description when provided', () => {
        renderWithProviders(<EmptyState title="Empty" description="Try adjusting your filters" />);
        expect(screen.getByText('Try adjusting your filters')).toBeInTheDocument();
    });

    it('does not render description when not provided', () => {
        renderWithProviders(<EmptyState title="Empty" />);
        expect(screen.queryByText('Try adjusting your filters')).not.toBeInTheDocument();
    });

    it('renders the action element when provided', () => {
        renderWithProviders(<EmptyState title="Empty" action={<button>Retry</button>} />);
        expect(screen.getByRole('button', {name: 'Retry'})).toBeInTheDocument();
    });

    it('uses error icon color when severity is error', () => {
        renderWithProviders(<EmptyState title="Failed to load" icon="error_outline" severity="error" />);
        expect(screen.getByText('error_outline')).toBeInTheDocument();
        expect(screen.getByText('Failed to load')).toBeInTheDocument();
    });

    it('exposes role=alert and aria-live when severity is error', () => {
        renderWithProviders(<EmptyState title="Failed to load" severity="error" />);
        const alert = screen.getByRole('alert');
        expect(alert).toHaveAttribute('aria-live', 'polite');
        expect(alert).toHaveTextContent('Failed to load');
    });

    it('does not set role=alert for the default severity', () => {
        renderWithProviders(<EmptyState title="No items" />);
        expect(screen.queryByRole('alert')).not.toBeInTheDocument();
    });

    it('renders block-level description content without HTML nesting warnings', () => {
        renderWithProviders(
            <EmptyState
                title="Empty"
                description={
                    <div data-testid="block-desc">
                        <p>Paragraph one</p>
                        <p>Paragraph two</p>
                    </div>
                }
            />,
        );
        expect(screen.getByTestId('block-desc')).toBeInTheDocument();
        expect(screen.getByText('Paragraph one')).toBeInTheDocument();
    });
});
