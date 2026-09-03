import {DebugEntry} from '@app-dev-panel/sdk/API/Debug/Debug';
import {CollectorsMap} from '@app-dev-panel/sdk/Helper/collectors';
import {renderWithProviders} from '@app-dev-panel/sdk/test-utils';
import {
    FloatMetrics,
    METRIC_ICONS,
    SideMetrics,
    metricTargets,
} from '@app-dev-panel/toolbar/Module/Toolbar/Component/Toolbar/FloatMetrics';
import {fireEvent, screen} from '@testing-library/react';
import {afterEach, describe, expect, it, vi} from 'vitest';

const webEntry = (overrides: Partial<DebugEntry> = {}): DebugEntry =>
    ({
        id: 'entry-42',
        collectors: [],
        web: {
            php: {version: '8.4'},
            request: {startTime: 0, processingTime: 0.042},
            memory: {peakUsage: 4 * 1024 * 1024},
        },
        request: {url: 'http://localhost/x', path: '/x', query: '', method: 'GET', isAjax: false, userIp: ''},
        response: {statusCode: 200},
        db: {queries: {total: 7, error: 0}},
        http: {count: 2},
        logger: {total: 5},
        event: {total: 3},
        deprecation: {total: 1},
        exception: {class: 'App\\Boom', message: 'boom', file: '/app/x.php', line: 1},
        validator: {total: 1, invalid: 0},
        router: {name: 'home'},
        ...overrides,
    }) as unknown as DebugEntry;

const collectorUrl = (collector: string) =>
    `/debug/debug?collector=${encodeURIComponent(collector)}&debugEntry=entry-42`;

describe('metricTargets', () => {
    afterEach(() => {
        delete window.__adp_panel_url;
    });

    it('maps every metric to the same collector URL shape the bottom-bar badges use', () => {
        const byKey = Object.fromEntries(metricTargets(webEntry()).map((t) => [t.key, t.url]));

        expect(byKey.time).toBe(collectorUrl(CollectorsMap.TimelineCollector));
        expect(byKey.memory).toBe(collectorUrl(CollectorsMap.WebAppInfoCollector));
        expect(byKey.db).toBe(collectorUrl(CollectorsMap.DatabaseCollector));
        expect(byKey.http).toBe(collectorUrl(CollectorsMap.HttpClientCollector));
        expect(byKey.logs).toBe(collectorUrl(CollectorsMap.LogCollector));
        expect(byKey.events).toBe(collectorUrl(CollectorsMap.EventCollector));
        expect(byKey.deprecations).toBe(collectorUrl(CollectorsMap.DeprecationCollector));
        expect(byKey.exception).toBe('/debug/inspector/files?class=App%5CBoom');
        expect(byKey.validation).toBe(collectorUrl(CollectorsMap.ValidatorCollector));
        expect(byKey.route).toBe(collectorUrl(CollectorsMap.RouterCollector));
    });

    it('uses the console app-info collector for console entries', () => {
        const entry = webEntry({
            web: undefined,
            request: undefined,
            console: {php: {version: '8.4'}, request: {startTime: 0, processingTime: 1.5}, memory: {peakUsage: 1024}},
            command: {input: 'app:run', exitCode: 0},
        } as unknown as Partial<DebugEntry>);

        const memory = metricTargets(entry).find((t) => t.key === 'memory')!;
        expect(memory.url).toBe(collectorUrl(CollectorsMap.ConsoleAppInfoCollector));
        expect(memory.value).toBe('1 KB');
    });

    it('respects a custom panel mount', () => {
        window.__adp_panel_url = '/adp';
        const db = metricTargets(webEntry()).find((t) => t.key === 'db')!;
        expect(db.url).toBe(
            `/adp/debug?collector=${encodeURIComponent(CollectorsMap.DatabaseCollector)}&debugEntry=entry-42`,
        );
    });

    it('omits metrics the entry does not expose', () => {
        const keys = metricTargets(
            webEntry({db: undefined, http: {count: 0}, logger: {total: 0}, event: undefined} as Partial<DebugEntry>),
        ).map((t) => t.key);
        expect(keys).not.toContain('db');
        expect(keys).not.toContain('http');
        expect(keys).not.toContain('logs');
        expect(keys).not.toContain('events');
        expect(keys).toContain('time');
    });
});

describe('FloatMetrics', () => {
    it('opens the database collector when the DB chip is clicked', () => {
        const handler = vi.fn();
        renderWithProviders(<FloatMetrics entry={webEntry()} iframeUrlHandler={handler} />);

        fireEvent.click(screen.getByText(`${METRIC_ICONS.db} DB 7`));

        expect(handler).toHaveBeenCalledTimes(1);
        expect(handler).toHaveBeenCalledWith(collectorUrl(CollectorsMap.DatabaseCollector));
    });

    it('opens the timeline collector when the response-time chip is clicked', () => {
        const handler = vi.fn();
        renderWithProviders(<FloatMetrics entry={webEntry()} iframeUrlHandler={handler} />);

        fireEvent.click(screen.getByText(`${METRIC_ICONS.time} 42ms`));

        expect(handler).toHaveBeenCalledWith(collectorUrl(CollectorsMap.TimelineCollector));
    });

    it('opens a new tab instead of the iframe on Ctrl+click', () => {
        const handler = vi.fn();
        const open = vi.fn();
        const originalOpen = window.open;
        window.open = open;
        try {
            renderWithProviders(<FloatMetrics entry={webEntry()} iframeUrlHandler={handler} />);
            fireEvent.click(screen.getByText(`${METRIC_ICONS.logs} Logs 5`), {ctrlKey: true});
        } finally {
            window.open = originalOpen;
        }

        expect(handler).not.toHaveBeenCalled();
        expect(open).toHaveBeenCalledWith(collectorUrl(CollectorsMap.LogCollector), '_blank', 'noopener,noreferrer');
    });
});

describe('SideMetrics', () => {
    it('renders emoji glyphs rather than literal escape sequences', () => {
        const {container} = renderWithProviders(<SideMetrics entry={webEntry()} iframeUrlHandler={() => {}} />);

        expect(container.textContent).toContain('⏱');
        expect(container.textContent).toContain('⚠️');
        expect(container.textContent).not.toContain('\\u23F1');
        expect(container.textContent).not.toContain('\\u26A0');
    });

    it('navigates to the matching collector when a row is clicked', () => {
        const handler = vi.fn();
        renderWithProviders(<SideMetrics entry={webEntry()} iframeUrlHandler={handler} />);

        fireEvent.click(screen.getByRole('button', {name: 'Log entries'}));
        expect(handler).toHaveBeenLastCalledWith(collectorUrl(CollectorsMap.LogCollector));

        fireEvent.click(screen.getByRole('button', {name: 'Exception'}));
        expect(handler).toHaveBeenLastCalledWith('/debug/inspector/files?class=App%5CBoom');

        fireEvent.click(screen.getByRole('button', {name: 'Route'}));
        expect(handler).toHaveBeenLastCalledWith(collectorUrl(CollectorsMap.RouterCollector));
    });
});
