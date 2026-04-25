import {renderWithProviders} from '@app-dev-panel/sdk/test-utils';
import {fireEvent, screen} from '@testing-library/react';
import {describe, expect, it, vi} from 'vitest';
import {FilterChip} from './FilterChip';

describe('FilterChip', () => {
    it('renders the label', () => {
        renderWithProviders(<FilterChip label="GET" />);
        expect(screen.getByText('GET')).toBeInTheDocument();
    });

    it('appends count when provided', () => {
        renderWithProviders(<FilterChip label="GET" count={5} />);
        expect(screen.getByText('GET (5)')).toBeInTheDocument();
    });

    it('omits count when not provided', () => {
        renderWithProviders(<FilterChip label="Clear" />);
        expect(screen.getByText('Clear')).toBeInTheDocument();
    });

    it('renders count of 0 (distinct from undefined)', () => {
        renderWithProviders(<FilterChip label="GET" count={0} />);
        expect(screen.getByText('GET (0)')).toBeInTheDocument();
    });

    it('calls onClick when clicked', () => {
        const onClick = vi.fn();
        renderWithProviders(<FilterChip label="GET" onClick={onClick} />);
        fireEvent.click(screen.getByText('GET'));
        expect(onClick).toHaveBeenCalledTimes(1);
    });

    it('is not clickable without onClick', () => {
        const {container} = renderWithProviders(<FilterChip label="GET" />);
        expect(container.querySelector('.MuiChip-clickable')).toBeNull();
    });

    it('is clickable when onClick provided', () => {
        const {container} = renderWithProviders(<FilterChip label="GET" onClick={() => {}} />);
        expect(container.querySelector('.MuiChip-clickable')).toBeInTheDocument();
    });

    it('renders a chip root for the colored (inactive) variant', () => {
        const {container} = renderWithProviders(<FilterChip label="error" color="#DC2626" />);
        expect(container.querySelector('.MuiChip-root')).toBeInTheDocument();
    });

    it('renders a chip root for the active variant', () => {
        const {container} = renderWithProviders(<FilterChip label="error" color="#DC2626" active />);
        expect(container.querySelector('.MuiChip-root')).toBeInTheDocument();
    });

    it('does not forward the chipColor prop to the DOM', () => {
        const {container} = renderWithProviders(<FilterChip label="error" color="#DC2626" />);
        const chip = container.querySelector('.MuiChip-root') as HTMLElement;
        expect(chip.hasAttribute('chipColor')).toBe(false);
        expect(chip.hasAttribute('active')).toBe(false);
    });
});
