import {liveSlice} from '@app-dev-panel/sdk/API/Debug/LiveContext';
import {renderWithProviders} from '@app-dev-panel/sdk/test-utils';
import {screen} from '@testing-library/react';
import userEvent from '@testing-library/user-event';
import {describe, expect, it, vi} from 'vitest';
import {LiveFeedPanel} from './LiveFeedPanel';

const renderPanel = (entries: any[] = [], onClose = vi.fn()) =>
    renderWithProviders(<LiveFeedPanel onClose={onClose} />, {
        preloadedState: {[liveSlice.name]: {entries, paused: false}},
        reducers: {
            application: (
                state = {baseUrl: '', pageSize: 20, autoLatest: false, toolbar: {}, favoriteUrls: []},
                _a: any,
            ) => state,
            notifications: (state = [], _a: any) => state,
            [liveSlice.name]: liveSlice.reducer,
        },
    });

const makeLogEntry = (overrides: Record<string, any> = {}) => ({
    id: 'log-1',
    kind: 'log' as const,
    timestamp: 1705319445000,
    payload: {level: 'info', message: 'Test message', context: {}, ...overrides},
});

const makeDumpEntry = (overrides: Record<string, any> = {}) => ({
    id: 'dump-1',
    kind: 'dump' as const,
    timestamp: 1705319445000,
    payload: {variable: {foo: 'bar'}, ...overrides},
});

describe('LiveFeedPanel', () => {
    it('shows empty state when no entries', () => {
        renderPanel();
        expect(screen.getByText('No live events yet')).toBeInTheDocument();
    });

    it('renders log entry with level badge', () => {
        renderPanel([makeLogEntry()]);
        expect(screen.getByText('INFO')).toBeInTheDocument();
        expect(screen.getByText('Test message')).toBeInTheDocument();
    });

    it('renders log entry with uppercase level', () => {
        renderPanel([makeLogEntry({level: 'error'})]);
        expect(screen.getByText('ERROR')).toBeInTheDocument();
    });

    it('handles missing level gracefully (defaults to DEBUG)', () => {
        renderPanel([makeLogEntry({level: undefined})]);
        expect(screen.getByText('DEBUG')).toBeInTheDocument();
    });

    it('renders dump entry with DUMP badge', () => {
        renderPanel([makeDumpEntry()]);
        expect(screen.getByText('DUMP')).toBeInTheDocument();
    });

    it('renders file link for dump entry with line', () => {
        renderPanel([makeDumpEntry({line: '/src/app.php:42'})]);
        expect(screen.getByText('/src/app.php:42')).toBeInTheDocument();
    });

    it('renders multiple entries', () => {
        renderPanel([makeLogEntry({level: 'warning', message: 'First'}), {...makeDumpEntry(), id: 'dump-2'}]);
        expect(screen.getByText('WARNING')).toBeInTheDocument();
        expect(screen.getByText('DUMP')).toBeInTheDocument();
    });

    it('shows entry count', () => {
        renderPanel([makeLogEntry(), {...makeLogEntry(), id: 'log-2'}]);
        expect(screen.getByText('(2)')).toBeInTheDocument();
    });

    it('calls onClose when close button clicked', async () => {
        const onClose = vi.fn();
        const user = userEvent.setup();
        renderPanel([makeLogEntry()], onClose);
        await user.click(screen.getByLabelText('Close'));
        expect(onClose).toHaveBeenCalled();
    });

    it('clears entries when clear button clicked', async () => {
        const user = userEvent.setup();
        renderPanel([makeLogEntry()]);
        await user.click(screen.getByLabelText('Clear all'));
        expect(screen.getByText('No live events yet')).toBeInTheDocument();
    });

    it('does not show clear button when no entries', () => {
        renderPanel();
        expect(screen.queryByLabelText('Clear all')).not.toBeInTheDocument();
    });
});
