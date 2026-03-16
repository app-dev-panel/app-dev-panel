import {render, screen} from '@testing-library/react';
import {describe, expect, it} from 'vitest';
import {renderWithProviders} from '../../test-utils';
import {NavBadge} from './NavBadge';

describe('NavBadge', () => {
    it('renders count value', () => {
        renderWithProviders(<NavBadge count={5} />);
        expect(screen.getByText('5')).toBeInTheDocument();
    });

    it('renders string count', () => {
        renderWithProviders(<NavBadge count="12" />);
        expect(screen.getByText('12')).toBeInTheDocument();
    });

    it('returns null for count 0', () => {
        const {container} = render(<NavBadge count={0} />);
        expect(container.innerHTML).toBe('');
    });

    it('returns null for string "0"', () => {
        const {container} = render(<NavBadge count="0" />);
        expect(container.innerHTML).toBe('');
    });
});
