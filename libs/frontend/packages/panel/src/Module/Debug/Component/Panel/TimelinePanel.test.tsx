import {renderWithProviders} from '@app-dev-panel/sdk/test-utils';
import {screen} from '@testing-library/react';
import userEvent from '@testing-library/user-event';
import {describe, expect, it} from 'vitest';
import {TimelinePanel} from './TimelinePanel';

describe('TimelinePanel', () => {
    it('shows empty message when no data', () => {
        renderWithProviders(<TimelinePanel data={[]} />);
        expect(screen.getByText(/No timeline items found/)).toBeInTheDocument();
    });

    it('shows empty message when data is null', () => {
        renderWithProviders(<TimelinePanel data={null as any} />);
        expect(screen.getByText(/No timeline items found/)).toBeInTheDocument();
    });

    it('renders timeline events with count', () => {
        const data: [number, number, string][] = [
            [1705319445.0, 0.025, 'App\\Middleware\\AuthMiddleware'],
            [1705319445.025, 0.015, 'App\\Controller\\UserController'],
        ];
        renderWithProviders(<TimelinePanel data={data} />);
        expect(screen.getByText('2 timeline events')).toBeInTheDocument();
    });

    it('renders short class names as labels', () => {
        const data: [number, number, string][] = [
            [1705319445.0, 0.01, 'App\\Middleware\\AuthMiddleware'],
            [1705319445.01, 0.02, 'App\\Controller\\UserController'],
        ];
        renderWithProviders(<TimelinePanel data={data} />);
        // Names appear in both legend and row labels
        expect(screen.getAllByText('AuthMiddleware').length).toBeGreaterThanOrEqual(1);
        expect(screen.getAllByText('UserController').length).toBeGreaterThanOrEqual(1);
    });

    it('renders duration labels', () => {
        const data: [number, number, string][] = [[1705319445.0, 2.5, 'App\\Handler']];
        renderWithProviders(<TimelinePanel data={data} />);
        expect(screen.getAllByText('2.50s').length).toBeGreaterThanOrEqual(1);
    });

    it('expands row to show details on click', async () => {
        const user = userEvent.setup();
        const data: [number, number, string][] = [[1705319445.0, 0.01, 'App\\Middleware\\AuthMiddleware']];
        renderWithProviders(<TimelinePanel data={data} />);
        // Click on the row label (getAllByText since name appears in legend too)
        const elements = screen.getAllByText('AuthMiddleware');
        await user.click(elements[elements.length - 1]);
        expect(screen.getByText('App\\Middleware\\AuthMiddleware')).toBeInTheDocument();
    });

    it('renders legend items', () => {
        const data: [number, number, string][] = [
            [1705319445.0, 0.01, 'App\\Middleware\\AuthMiddleware'],
            [1705319445.01, 0.02, 'App\\Controller\\UserController'],
        ];
        renderWithProviders(<TimelinePanel data={data} />);
        // Legend shows short names
        expect(screen.getAllByText('AuthMiddleware').length).toBeGreaterThanOrEqual(1);
        expect(screen.getAllByText('UserController').length).toBeGreaterThanOrEqual(1);
    });
});
