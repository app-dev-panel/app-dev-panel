import {renderWithProviders} from '@app-dev-panel/sdk/test-utils';
import {screen} from '@testing-library/react';
import userEvent from '@testing-library/user-event';
import {describe, expect, it} from 'vitest';
import {TimelinePanel} from './TimelinePanel';

// Data format: [microtime, reference, collectorClass, additionalData?]
// row[0] = microtime(true) — start timestamp
// row[1] = reference — object ID or count (NOT a duration)
// row[2] = collector class name
// row[3] = additional data (optional)

describe('TimelinePanel', () => {
    it('shows empty message when no data', () => {
        renderWithProviders(<TimelinePanel data={[]} />);
        expect(screen.getByText(/No timeline items found/)).toBeInTheDocument();
    });

    it('shows empty message when data is null', () => {
        renderWithProviders(<TimelinePanel data={null as any} />);
        expect(screen.getByText(/No timeline items found/)).toBeInTheDocument();
    });

    it('renders correct number of timeline events', () => {
        const data: [number, number, string][] = [
            [1705319445.0, 1, 'App\\Middleware\\AuthMiddleware'],
            [1705319445.025, 2, 'App\\Controller\\UserController'],
            [1705319445.05, 3, 'App\\Service\\CacheService'],
        ];
        renderWithProviders(<TimelinePanel data={data} />);
        expect(screen.getByText('3 timeline events')).toBeInTheDocument();
    });

    it('shows short class names without namespace', () => {
        const data: [number, number, string][] = [
            [1705319445.0, 1, 'App\\Middleware\\AuthMiddleware'],
            [1705319445.01, 2, 'App\\Controller\\UserController'],
        ];
        renderWithProviders(<TimelinePanel data={data} />);
        // Short names appear in both legend and row labels
        expect(screen.getAllByText('AuthMiddleware').length).toBeGreaterThanOrEqual(2);
        expect(screen.getAllByText('UserController').length).toBeGreaterThanOrEqual(2);
    });

    it('renders legend items for unique collector classes', () => {
        const data: [number, number, string][] = [
            [1705319445.0, 1, 'App\\Middleware\\AuthMiddleware'],
            [1705319445.01, 2, 'App\\Controller\\UserController'],
            [1705319445.02, 3, 'App\\Middleware\\AuthMiddleware'],
        ];
        renderWithProviders(<TimelinePanel data={data} />);
        // Legend deduplicates: only 2 unique short names
        // Each short name appears in legend + row labels
        expect(screen.getAllByText('AuthMiddleware').length).toBe(3); // 1 legend + 2 rows
        expect(screen.getAllByText('UserController').length).toBe(2); // 1 legend + 1 row
    });

    it('renders time axis ticks', () => {
        const data: [number, number, string][] = [
            [1705319445.0, 1, 'App\\Collector\\LogCollector'],
            [1705319445.1, 2, 'App\\Collector\\EventCollector'],
        ];
        renderWithProviders(<TimelinePanel data={data} />);
        // Total span is 0.1s — 7 ticks: 0µs, 16.7ms, 33.3ms, 50.0ms, 66.7ms, 83.3ms, 100.0ms
        // Some tick values may also appear as row offset labels, so use getAllByText
        expect(screen.getAllByText('0µs').length).toBeGreaterThanOrEqual(1);
        // These middle ticks only appear in the axis (not as row offsets)
        expect(screen.getByText('16.7ms')).toBeInTheDocument();
        expect(screen.getByText('33.3ms')).toBeInTheDocument();
        expect(screen.getByText('50.0ms')).toBeInTheDocument();
        expect(screen.getByText('66.7ms')).toBeInTheDocument();
        expect(screen.getByText('83.3ms')).toBeInTheDocument();
        expect(screen.getAllByText('100.0ms').length).toBeGreaterThanOrEqual(1);
    });

    it('renders relative time offset labels for point markers', () => {
        const data: [number, number, string][] = [
            [1705319445.0, 1, 'App\\Collector\\LogCollector'],
            [1705319445.025, 2, 'App\\Collector\\EventCollector'],
        ];
        renderWithProviders(<TimelinePanel data={data} />);
        // First event offset is 0µs (also appears in axis tick)
        expect(screen.getAllByText('0µs').length).toBeGreaterThanOrEqual(2);
        // Second event offset is 25.0ms (also appears in axis tick)
        expect(screen.getAllByText('25.0ms').length).toBeGreaterThanOrEqual(1);
    });

    it('expands row to show details on click', async () => {
        const user = userEvent.setup();
        const data: [number, number, string][] = [[1705319445.0, 42, 'App\\Middleware\\AuthMiddleware']];
        renderWithProviders(<TimelinePanel data={data} />);
        // Click on the row label (getAllByText since name appears in legend too)
        const elements = screen.getAllByText('AuthMiddleware');
        await user.click(elements[elements.length - 1]);
        // Expanded detail shows full class name (may also appear in tooltip)
        expect(screen.getAllByText('App\\Middleware\\AuthMiddleware').length).toBeGreaterThanOrEqual(1);
        // Shows reference value
        expect(screen.getByText(/Ref: 42/)).toBeInTheDocument();
    });
});
