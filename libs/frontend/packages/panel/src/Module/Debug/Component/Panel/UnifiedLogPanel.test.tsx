import {renderWithProviders} from '@app-dev-panel/sdk/test-utils';
import {screen} from '@testing-library/react';
import {describe, expect, it} from 'vitest';
import {UnifiedLogPanel} from './UnifiedLogPanel';

const logs = [
    {context: {}, level: 'info' as const, line: '', message: 'hello info', time: 1},
    {context: {}, level: 'debug' as const, line: '', message: 'hello debug', time: 2},
    {context: {}, level: 'warning' as const, line: '', message: 'careful now', time: 3},
    {context: {}, level: 'error' as const, line: '', message: 'boom', time: 4},
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
});
