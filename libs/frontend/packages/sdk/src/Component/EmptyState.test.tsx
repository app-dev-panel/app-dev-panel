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
});
