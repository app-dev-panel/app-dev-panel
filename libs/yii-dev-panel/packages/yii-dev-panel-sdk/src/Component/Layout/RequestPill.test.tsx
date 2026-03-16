import {screen} from '@testing-library/react';
import userEvent from '@testing-library/user-event';
import {describe, expect, it, vi} from 'vitest';
import {renderWithProviders} from '../../test-utils';
import {RequestPill} from './RequestPill';

describe('RequestPill', () => {
    it('renders method, path, status, and duration', () => {
        renderWithProviders(<RequestPill method="GET" path="/api/users" status={200} duration="12.500 ms" />);
        expect(screen.getByText('GET')).toBeInTheDocument();
        expect(screen.getByText('/api/users')).toBeInTheDocument();
        expect(screen.getByText('200')).toBeInTheDocument();
        expect(screen.getByText('12.500 ms')).toBeInTheDocument();
    });

    it('calls onClick when clicked', async () => {
        const user = userEvent.setup();
        const onClick = vi.fn();
        renderWithProviders(<RequestPill method="POST" path="/api" status={201} duration="5 ms" onClick={onClick} />);
        await user.click(screen.getByText('POST'));
        expect(onClick).toHaveBeenCalledOnce();
    });

    it('renders different HTTP methods', () => {
        renderWithProviders(<RequestPill method="DELETE" path="/api/1" status={404} duration="1 ms" />);
        expect(screen.getByText('DELETE')).toBeInTheDocument();
        expect(screen.getByText('404')).toBeInTheDocument();
    });
});
