import {screen} from '@testing-library/react';
import userEvent from '@testing-library/user-event';
import {describe, expect, it, vi} from 'vitest';
import {renderWithProviders} from '../../test-utils';
import {NavItem} from './NavItem';

describe('NavItem', () => {
    it('renders icon and label', () => {
        renderWithProviders(<NavItem icon="http" label="Request" />);
        expect(screen.getByText('Request')).toBeInTheDocument();
        expect(screen.getByText('http')).toBeInTheDocument();
    });

    it('renders badge when count > 0', () => {
        renderWithProviders(<NavItem icon="http" label="Request" badge={5} />);
        expect(screen.getByText('5')).toBeInTheDocument();
    });

    it('does not render badge when count is 0', () => {
        renderWithProviders(<NavItem icon="http" label="Request" badge={0} />);
        expect(screen.queryByText('0')).not.toBeInTheDocument();
    });

    it('does not render badge when undefined', () => {
        renderWithProviders(<NavItem icon="http" label="Request" />);
        // Just label and icon, no badge
        expect(screen.getByText('Request')).toBeInTheDocument();
    });

    it('calls onClick when clicked', async () => {
        const user = userEvent.setup();
        const onClick = vi.fn();
        renderWithProviders(<NavItem icon="http" label="Request" onClick={onClick} />);
        await user.click(screen.getByText('Request'));
        expect(onClick).toHaveBeenCalledOnce();
    });
});
