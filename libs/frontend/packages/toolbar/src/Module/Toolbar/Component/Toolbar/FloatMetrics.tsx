import {DebugEntry} from '@app-dev-panel/sdk/API/Debug/Debug';
import {CollectorsMap} from '@app-dev-panel/sdk/Helper/collectors';
import {isDebugEntryAboutConsole, isDebugEntryAboutWeb} from '@app-dev-panel/sdk/Helper/debugEntry';
import {openInNewTabOnModifier} from '@app-dev-panel/sdk/Helper/openInNewTabOnModifier';
import {panelPagePath} from '@app-dev-panel/sdk/Helper/panelMountPath';
import {Box, Chip} from '@mui/material';
import type {MouseEvent} from 'react';

const chipSx = {
    height: 27,
    borderRadius: 1.5,
    fontSize: 11,
    fontFamily: "'JetBrains Mono', monospace",
    fontWeight: 500,
    cursor: 'pointer',
    '& .MuiChip-label': {px: 1},
};

// Emoji glyphs are kept in JS strings on purpose: JSX attribute literals do
// not process `\uXXXX` escapes, so `icon="⏱"` renders the six literal
// characters instead of the stopwatch glyph.
export const METRIC_ICONS = {
    time: '⏱',
    memory: '💾',
    db: '🗄',
    http: '🌐',
    logs: '📋',
    events: '⚡',
    deprecations: '⚠️',
    exception: '💥',
    validation: '✅',
    route: '🔀',
} as const;

const formatTime = (seconds: number): string => {
    const ms = seconds * 1000;
    if (ms < 1000) return `${Math.round(ms)}ms`;
    return `${seconds.toFixed(2)}s`;
};

const formatMemory = (bytes: number): string => {
    if (bytes < 1024 * 1024) return `${(bytes / 1024).toFixed(0)} KB`;
    return `${(bytes / (1024 * 1024)).toFixed(1)} MB`;
};

/**
 * `{mount}/debug?collector=<fqcn>&debugEntry=<id>` — the same URL shape every
 * bottom-bar badge (`MemoryItem`, `DatabaseItem`, …) hands to `iframeUrlHandler`.
 * The first `/debug` is the mount, the second the panel-internal collector route.
 */
export const collectorPagePath = (entry: DebugEntry, collector: string): string =>
    panelPagePath(`/debug?collector=${encodeURIComponent(collector)}&debugEntry=${encodeURIComponent(entry.id)}`);

/** `{mount}/inspector/files?class=<fqcn>` — mirrors `ExceptionItem`'s "Open in panel". */
export const exceptionPagePath = (entry: DebugEntry): string =>
    panelPagePath(`/inspector/files?class=${encodeURIComponent(entry.exception?.class ?? '')}`);

const appInfoCollector = (entry: DebugEntry): string =>
    isDebugEntryAboutWeb(entry) ? CollectorsMap.WebAppInfoCollector : CollectorsMap.ConsoleAppInfoCollector;

type MetricTarget = {
    key: keyof typeof METRIC_ICONS;
    label: string;
    value: string | number;
    url: string;
    color?: string;
};

/**
 * Every metric an entry exposes, in display order, with the panel URL a click
 * should open. Shared by the float chips and the side-rail rows so both modes
 * navigate exactly like the bottom-bar badges do.
 */
export const metricTargets = (entry: DebugEntry): MetricTarget[] => {
    const timing = entry.web || entry.console;
    const targets: MetricTarget[] = [];

    if (timing) {
        targets.push({
            key: 'time',
            label: 'Response time',
            value: formatTime(timing.request.processingTime),
            url: collectorPagePath(entry, CollectorsMap.TimelineCollector),
        });
        targets.push({
            key: 'memory',
            label: 'Peak memory',
            value: formatMemory(timing.memory.peakUsage),
            url: collectorPagePath(entry, appInfoCollector(entry)),
        });
    }
    if (entry.db) {
        targets.push({
            key: 'db',
            label: 'DB queries',
            value: entry.db.queries.total,
            url: collectorPagePath(entry, CollectorsMap.DatabaseCollector),
        });
    }
    if (entry.http && entry.http.count > 0) {
        targets.push({
            key: 'http',
            label: 'HTTP requests',
            value: entry.http.count,
            url: collectorPagePath(entry, CollectorsMap.HttpClientCollector),
        });
    }
    if (entry.logger && entry.logger.total > 0) {
        targets.push({
            key: 'logs',
            label: 'Log entries',
            value: entry.logger.total,
            url: collectorPagePath(entry, CollectorsMap.LogCollector),
        });
    }
    if (entry.event && entry.event.total > 0) {
        targets.push({
            key: 'events',
            label: 'Events fired',
            value: entry.event.total,
            url: collectorPagePath(entry, CollectorsMap.EventCollector),
        });
    }
    if (entry.deprecation && entry.deprecation.total > 0) {
        targets.push({
            key: 'deprecations',
            label: 'Deprecations',
            value: entry.deprecation.total,
            url: collectorPagePath(entry, CollectorsMap.DeprecationCollector),
            color: '#D97706',
        });
    }
    if (entry.exception) {
        targets.push({
            key: 'exception',
            label: 'Exception',
            value: entry.exception.class,
            url: exceptionPagePath(entry),
            color: '#DC2626',
        });
    }
    if (entry.validator) {
        targets.push({
            key: 'validation',
            label: 'Validation',
            value: entry.validator.invalid > 0 ? `${entry.validator.invalid} invalid` : 'OK',
            url: collectorPagePath(entry, CollectorsMap.ValidatorCollector),
            color: entry.validator.invalid > 0 ? '#D97706' : '#16A34A',
        });
    }
    if (entry.router) {
        targets.push({
            key: 'route',
            label: 'Route',
            value: entry.router.name,
            url: collectorPagePath(entry, CollectorsMap.RouterCollector),
        });
    }

    return targets;
};

const makeClickHandler = (url: string, iframeUrlHandler: (url: string) => void) => (e: MouseEvent) => {
    if (openInNewTabOnModifier(e, url)) return;
    iframeUrlHandler(url);
    e.stopPropagation();
    e.preventDefault();
};

const FLOAT_KEYS: ReadonlySet<MetricTarget['key']> = new Set([
    'time',
    'memory',
    'db',
    'http',
    'logs',
    'events',
    'deprecations',
    'exception',
]);

const floatChipLabel = (target: MetricTarget): string => {
    const icon = METRIC_ICONS[target.key];
    switch (target.key) {
        case 'db':
            return `${icon} DB ${target.value}`;
        case 'http':
            return `${icon} HTTP ${target.value}`;
        case 'logs':
            return `${icon} Logs ${target.value}`;
        case 'events':
            return `${icon} Ev ${target.value}`;
        case 'deprecations':
            return `${icon} Depr ${target.value}`;
        default:
            return `${icon} ${target.value}`;
    }
};

type FloatMetricsProps = {entry: DebugEntry; iframeUrlHandler: (url: string) => void};

export const FloatMetrics = ({entry, iframeUrlHandler}: FloatMetricsProps) => (
    <Box sx={{display: 'flex', flexWrap: 'wrap', gap: 0.5, alignContent: 'flex-start'}}>
        {metricTargets(entry)
            .filter((target) => FLOAT_KEYS.has(target.key))
            .map((target) => (
                <Chip
                    key={target.key}
                    label={floatChipLabel(target)}
                    size="small"
                    variant="outlined"
                    color={target.key === 'deprecations' ? 'warning' : target.key === 'exception' ? 'error' : 'default'}
                    onClick={makeClickHandler(target.url, iframeUrlHandler)}
                    sx={chipSx}
                />
            ))}
    </Box>
);

/** Request hero bar for float/side modes */
export const RequestHeroBar = ({entry}: {entry: DebugEntry}) => {
    const timing = entry.web || entry.console;
    const isWeb = isDebugEntryAboutWeb(entry);
    const isConsole = isDebugEntryAboutConsole(entry);

    return (
        <Box
            sx={{
                display: 'flex',
                alignItems: 'center',
                gap: 0.75,
                px: 1.25,
                py: 0.5,
                bgcolor: 'primary.light',
                flexShrink: 0,
                fontFamily: "'JetBrains Mono', monospace",
                fontSize: 11,
                overflow: 'hidden',
            }}
        >
            {isWeb && (
                <>
                    <Box component="span" sx={{fontWeight: 700}}>
                        {entry.request?.method}
                    </Box>
                    <Box component="span" sx={{fontWeight: 700, color: 'success.main'}}>
                        {entry.response?.statusCode}
                    </Box>
                    <Box
                        component="span"
                        sx={{
                            color: 'text.secondary',
                            flex: 1,
                            overflow: 'hidden',
                            textOverflow: 'ellipsis',
                            whiteSpace: 'nowrap',
                        }}
                    >
                        {entry.request?.path}
                    </Box>
                </>
            )}
            {isConsole && (
                <>
                    <Box component="span" sx={{fontWeight: 700}}>
                        CLI
                    </Box>
                    <Box
                        component="span"
                        sx={{fontWeight: 700, color: entry.command?.exitCode === 0 ? 'success.main' : 'error.main'}}
                    >
                        exit {entry.command?.exitCode}
                    </Box>
                    <Box
                        component="span"
                        sx={{
                            color: 'text.secondary',
                            flex: 1,
                            overflow: 'hidden',
                            textOverflow: 'ellipsis',
                            whiteSpace: 'nowrap',
                        }}
                    >
                        {entry.command?.input}
                    </Box>
                </>
            )}
            {timing && (
                <Box component="span" sx={{color: 'text.disabled', flexShrink: 0}}>
                    {formatTime(timing.request.processingTime)}
                </Box>
            )}
        </Box>
    );
};

/** Side rail metric row */
export const SideMetricRow = ({
    icon,
    label,
    value,
    color,
    onClick,
}: {
    icon: string;
    label: string;
    value: string | number;
    color?: string;
    onClick?: (e: MouseEvent) => void;
}) => (
    <Box
        onClick={onClick}
        role={onClick ? 'button' : undefined}
        aria-label={onClick ? label : undefined}
        sx={{
            display: 'flex',
            alignItems: 'center',
            px: 1.75,
            py: 0.75,
            gap: 1.25,
            cursor: onClick ? 'pointer' : 'default',
            borderLeft: '3px solid transparent',
            borderBottom: 1,
            borderColor: 'divider',
            transition: 'background 100ms ease',
            '&:hover': onClick ? {bgcolor: 'primary.light', borderLeftColor: 'primary.main'} : {},
        }}
    >
        <Box sx={{fontSize: 13, width: 20, textAlign: 'center', flexShrink: 0}}>{icon}</Box>
        <Box sx={{fontSize: 13, color: 'text.secondary', flex: 1}}>{label}</Box>
        <Box
            sx={{
                fontFamily: "'JetBrains Mono', monospace",
                fontSize: 13,
                fontWeight: 500,
                color: color ?? 'text.primary',
            }}
        >
            {value}
        </Box>
    </Box>
);

type SideMetricsProps = {entry: DebugEntry; iframeUrlHandler: (url: string) => void};

/** All side metrics for an entry */
export const SideMetrics = ({entry, iframeUrlHandler}: SideMetricsProps) => (
    <Box sx={{flex: 1, overflowY: 'auto'}}>
        {metricTargets(entry).map((target) => (
            <SideMetricRow
                key={target.key}
                icon={METRIC_ICONS[target.key]}
                label={target.label}
                value={target.value}
                color={target.color}
                onClick={makeClickHandler(target.url, iframeUrlHandler)}
            />
        ))}
    </Box>
);
