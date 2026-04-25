import {renderWithProviders} from '@app-dev-panel/sdk/test-utils';
import {fireEvent, screen} from '@testing-library/react';
import {describe, expect, it} from 'vitest';
import {UnifiedLogPanel} from './UnifiedLogPanel';

const logs = [
    {context: {}, level: 'info' as const, line: '', message: 'hello info', time: 1},
    {context: {}, level: 'debug' as const, line: '', message: 'hello debug', time: 2},
    {context: {}, level: 'warning' as const, line: '', message: 'careful now', time: 3},
    {context: {}, level: 'error' as const, line: '', message: 'boom', time: 4},
];

const deprecations = [
    {time: 5, message: 'old api call', file: '/a.php', line: 10, category: 'user' as const, trace: []},
    {time: 6, message: 'legacy php thing', file: '/b.php', line: 20, category: 'php' as const, trace: []},
];

describe('UnifiedLogPanel', () => {
    it('shows all entries when no level query param is set', () => {
        renderWithProviders(<UnifiedLogPanel logs={logs} deprecations={[]} dumps={[]} />);
        expect(screen.getByText('hello info')).toBeInTheDocument();
        expect(screen.getByText('careful now')).toBeInTheDocument();
        expect(screen.getByText('boom')).toBeInTheDocument();
    });

    it('pre-filters by level when ?level=error is present', () => {
        renderWithProviders(<UnifiedLogPanel logs={logs} deprecations={[]} dumps={[]} />, {
            route: '/debug?collector=foo&level=error',
        });
        expect(screen.getByText('boom')).toBeInTheDocument();
        expect(screen.queryByText('hello info')).not.toBeInTheDocument();
        expect(screen.queryByText('careful now')).not.toBeInTheDocument();
    });

    it('pre-filters by multiple levels', () => {
        renderWithProviders(<UnifiedLogPanel logs={logs} deprecations={[]} dumps={[]} />, {
            route: '/debug?level=warning,notice',
        });
        expect(screen.getByText('careful now')).toBeInTheDocument();
        expect(screen.queryByText('hello info')).not.toBeInTheDocument();
        expect(screen.queryByText('boom')).not.toBeInTheDocument();
    });

    it('ignores unknown level tokens', () => {
        renderWithProviders(<UnifiedLogPanel logs={logs} deprecations={[]} dumps={[]} />, {
            route: '/debug?level=nonsense,info',
        });
        expect(screen.getByText('hello info')).toBeInTheDocument();
        expect(screen.queryByText('boom')).not.toBeInTheDocument();
    });

    it('clicking a log level sub-filter implicitly restricts to logs and hides deprecations', () => {
        renderWithProviders(<UnifiedLogPanel logs={logs} deprecations={deprecations} dumps={[]} />);
        // Both kinds + deprecations visible initially
        expect(screen.getByText('old api call')).toBeInTheDocument();
        expect(screen.getByText('boom')).toBeInTheDocument();

        fireEvent.click(screen.getByText(/^ERROR \(1\)$/));

        expect(screen.getByText('boom')).toBeInTheDocument();
        expect(screen.queryByText('hello info')).not.toBeInTheDocument();
        expect(screen.queryByText('old api call')).not.toBeInTheDocument();
        expect(screen.queryByText('legacy php thing')).not.toBeInTheDocument();
    });

    it('clicking a deprecation category sub-filter implicitly restricts to deprecations and hides logs', () => {
        renderWithProviders(<UnifiedLogPanel logs={logs} deprecations={deprecations} dumps={[]} />);

        fireEvent.click(screen.getByText(/^DEPR USER \(1\)$/));

        expect(screen.getByText('old api call')).toBeInTheDocument();
        expect(screen.queryByText('legacy php thing')).not.toBeInTheDocument();
        expect(screen.queryByText('boom')).not.toBeInTheDocument();
        expect(screen.queryByText('hello info')).not.toBeInTheDocument();
    });

    it('combining ERROR and DEPR USER sub-filters shows matches from both kinds only', () => {
        renderWithProviders(<UnifiedLogPanel logs={logs} deprecations={deprecations} dumps={[]} />);

        fireEvent.click(screen.getByText(/^ERROR \(1\)$/));
        fireEvent.click(screen.getByText(/^DEPR USER \(1\)$/));

        expect(screen.getByText('boom')).toBeInTheDocument();
        expect(screen.getByText('old api call')).toBeInTheDocument();
        expect(screen.queryByText('hello info')).not.toBeInTheDocument();
        expect(screen.queryByText('hello debug')).not.toBeInTheDocument();
        expect(screen.queryByText('careful now')).not.toBeInTheDocument();
        expect(screen.queryByText('legacy php thing')).not.toBeInTheDocument();
    });

    it('toggling Logs off clears any active log-level sub-filter', () => {
        renderWithProviders(<UnifiedLogPanel logs={logs} deprecations={deprecations} dumps={[]} />, {
            route: '/debug?level=error',
        });
        // Only 'boom' visible initially (activeKinds={log}, activeLevels={error}).
        expect(screen.getByText('boom')).toBeInTheDocument();
        expect(screen.queryByText('old api call')).not.toBeInTheDocument();

        fireEvent.click(screen.getAllByText(/^Logs \(4\)$/)[0]);

        // Toggling Logs off clears its sub-filter — deprecations become the only filter-less kind,
        // but since no filters remain, everything is visible again.
        expect(screen.getByText('boom')).toBeInTheDocument();
        expect(screen.getByText('old api call')).toBeInTheDocument();
        expect(screen.getByText('legacy php thing')).toBeInTheDocument();
        expect(screen.getByText('hello info')).toBeInTheDocument();
    });
});
