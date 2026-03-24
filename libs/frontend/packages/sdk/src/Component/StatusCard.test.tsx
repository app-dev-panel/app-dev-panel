import {renderWithProviders} from '@app-dev-panel/sdk/test-utils';
import {screen} from '@testing-library/react';
import userEvent from '@testing-library/user-event';
import {describe, expect, it, vi} from 'vitest';
import {StatusCard} from './StatusCard';

describe('StatusCard', () => {
    it('renders title', () => {
        renderWithProviders(<StatusCard title="Backend" icon="dns" status="connected" />);
        expect(screen.getByText('Backend')).toBeInTheDocument();
    });

    it('renders connected status text', () => {
        renderWithProviders(<StatusCard title="Backend" icon="dns" status="connected" />);
        expect(screen.getByText('connected')).toBeInTheDocument();
    });

    it('renders disconnected status text', () => {
        renderWithProviders(<StatusCard title="Backend" icon="dns" status="disconnected" />);
        expect(screen.getByText('disconnected')).toBeInTheDocument();
    });

    it('renders loading status text', () => {
        renderWithProviders(<StatusCard title="Backend" icon="dns" status="loading" />);
        expect(screen.getByText('loading')).toBeInTheDocument();
    });

    it('calls onClick when clicked', async () => {
        const user = userEvent.setup();
        const onClick = vi.fn();
        renderWithProviders(<StatusCard title="Backend" icon="dns" status="connected" onClick={onClick} />);
        await user.click(screen.getByText('Backend'));
        expect(onClick).toHaveBeenCalledOnce();
    });

    it('renders without onClick (no crash)', () => {
        renderWithProviders(<StatusCard title="Backend" icon="dns" status="connected" />);
        expect(screen.getByText('Backend')).toBeInTheDocument();
    });
});
