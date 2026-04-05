import {JsonRenderer} from '@app-dev-panel/panel/Module/Debug/Component/JsonRenderer';
import {useDebugEntry} from '@app-dev-panel/sdk/API/Debug/Context';
import {useLazyGetCollectorInfoQuery} from '@app-dev-panel/sdk/API/Debug/Debug';
import {FileLink} from '@app-dev-panel/sdk/Component/FileLink';
import {isClassString} from '@app-dev-panel/sdk/Helper/classMatcher';
import {getCollectorLabel} from '@app-dev-panel/sdk/Helper/collectorMeta';
import {CollectorsMap} from '@app-dev-panel/sdk/Helper/collectors';
import {formatMicrotime} from '@app-dev-panel/sdk/Helper/formatDate';
import {toObjectString} from '@app-dev-panel/sdk/Helper/objectString';
import {Box, Collapse, Typography} from '@mui/material';
import {styled, useTheme} from '@mui/material/styles';
import {useCallback, useEffect, useMemo, useState} from 'react';

type Item = [number, number, string] | [number, number, string, string];

type TimelineListViewProps = {data: Item[]; filter: string; activeFilters: Set<string>};

// ---------------------------------------------------------------------------
// Collector FQCN → color key mapping
// ---------------------------------------------------------------------------

const collectorColorKeyMap: Partial<Record<string, string>> = {
    [CollectorsMap.RequestCollector]: 'request',
    [CollectorsMap.LogCollector]: 'log',
    [CollectorsMap.EventCollector]: 'event',
    [CollectorsMap.DatabaseCollector]: 'database',
    [CollectorsMap.MiddlewareCollector]: 'middleware',
    [CollectorsMap.ExceptionCollector]: 'exception',
    [CollectorsMap.ServiceCollector]: 'service',
    [CollectorsMap.TimelineCollector]: 'timeline',
    [CollectorsMap.VarDumperCollector]: 'varDumper',
    [CollectorsMap.MailerCollector]: 'mailer',
    [CollectorsMap.FilesystemStreamCollector]: 'filesystem',
    [CollectorsMap.HttpClientCollector]: 'filesystem',
    [CollectorsMap.CacheCollector]: 'cache',
    [CollectorsMap.TemplateCollector]: 'template',
    [CollectorsMap.AuthorizationCollector]: 'authorization',
    [CollectorsMap.DeprecationCollector]: 'deprecation',
    [CollectorsMap.EnvironmentCollector]: 'environment',
    [CollectorsMap.TranslatorCollector]: 'translator',
    [CollectorsMap.WebAppInfoCollector]: 'environment',
    [CollectorsMap.ConsoleAppInfoCollector]: 'environment',
    [CollectorsMap.CommandCollector]: 'request',
    [CollectorsMap.QueueCollector]: 'service',
    [CollectorsMap.RouterCollector]: 'middleware',
    [CollectorsMap.ValidatorCollector]: 'service',
    [CollectorsMap.OpenTelemetryCollector]: 'timeline',
    [CollectorsMap.ElasticsearchCollector]: 'database',
    [CollectorsMap.RedisCollector]: 'cache',
};

// ---------------------------------------------------------------------------
// Styled components
// ---------------------------------------------------------------------------

const Row = styled(Box, {shouldForwardProp: (p) => p !== 'selected'})<{selected?: boolean}>(({theme, selected}) => ({
    display: 'flex',
    alignItems: 'center',
    minHeight: 32,
    padding: theme.spacing(0.5, 1.5),
    borderBottom: `1px solid ${theme.palette.divider}`,
    cursor: 'pointer',
    transition: 'background 0.1s',
    backgroundColor: selected ? theme.palette.action.selected : 'transparent',
    '&:hover': {backgroundColor: theme.palette.action.hover},
    gap: theme.spacing(1.5),
}));

const Dot = styled(Box)({width: 8, height: 8, borderRadius: '50%', flexShrink: 0});

const OffsetLabel = styled(Typography)(({theme}) => ({
    width: 72,
    flexShrink: 0,
    fontFamily: theme.adp.fontFamilyMono,
    fontSize: '11px',
    color: theme.palette.text.disabled,
    textAlign: 'right',
}));

const CollectorLabel = styled(Typography)({
    width: 140,
    flexShrink: 0,
    fontSize: '12px',
    fontWeight: 600,
    whiteSpace: 'nowrap',
    overflow: 'hidden',
    textOverflow: 'ellipsis',
});

const DetailText = styled(Typography)(({theme}) => ({
    flex: 1,
    fontSize: '12px',
    fontFamily: theme.adp.fontFamilyMono,
    color: theme.palette.text.secondary,
    whiteSpace: 'nowrap',
    overflow: 'hidden',
    textOverflow: 'ellipsis',
    minWidth: 0,
}));

const DetailBox = styled(Box)(({theme}) => ({
    padding: theme.spacing(1.5, 2, 1.5, 5),
    backgroundColor: theme.palette.action.hover,
    borderBottom: `1px solid ${theme.palette.divider}`,
    fontSize: '12px',
}));

const ScaleBar = styled(Box)(({theme}) => ({
    display: 'flex',
    justifyContent: 'space-between',
    padding: theme.spacing(1, 1.5),
    fontSize: '10px',
    fontFamily: theme.adp.fontFamilyMono,
    color: theme.palette.text.disabled,
    borderTop: `1px solid ${theme.palette.divider}`,
    position: 'relative',
    '&::before': {
        content: '""',
        position: 'absolute',
        top: 0,
        left: theme.spacing(1.5),
        right: theme.spacing(1.5),
        height: 4,
        background: `linear-gradient(to right, ${theme.palette.divider}, ${theme.palette.divider})`,
        borderRadius: 1,
    },
}));

// ---------------------------------------------------------------------------
// Helpers
// ---------------------------------------------------------------------------

function formatOffset(relativeTime: number): string {
    if (relativeTime < 0.001) {
        return `+${(relativeTime * 1000000).toFixed(0)}µs`;
    }
    if (relativeTime < 1) {
        return `+${(relativeTime * 1000).toFixed(1)}ms`;
    }
    return `+${relativeTime.toFixed(3)}s`;
}

// Collectors we can enrich with cross-referenced data
const enrichableCollectors = new Set([
    CollectorsMap.LogCollector,
    CollectorsMap.EventCollector,
    CollectorsMap.DatabaseCollector,
]);

type CollectorDataMap = Map<string, any[]>;

function getEnrichedDetail(
    row: Item,
    collectorData: CollectorDataMap,
    collectorIndexes: Map<string, number>,
): string | null {
    const collectorClass = row[2];
    const data = collectorData.get(collectorClass);

    // Track per-collector index
    const currentIndex = collectorIndexes.get(collectorClass) ?? 0;
    collectorIndexes.set(collectorClass, currentIndex + 1);

    // EventCollector: row[3] already has event class name
    if (collectorClass === CollectorsMap.EventCollector && row[3]) {
        const eventClass = typeof row[3] === 'string' ? row[3] : Array.isArray(row[3]) ? row[3][0] : null;
        if (eventClass && typeof eventClass === 'string') {
            return eventClass.split('\\').pop() ?? eventClass;
        }
    }

    if (!data || !Array.isArray(data)) return null;

    // LogCollector: show level + message
    if (collectorClass === CollectorsMap.LogCollector && data[currentIndex]) {
        const entry = data[currentIndex];
        const message = typeof entry.message === 'string' ? entry.message : JSON.stringify(entry.message);
        const truncated = message.length > 80 ? message.slice(0, 80) + '...' : message;
        return `[${entry.level}] ${truncated}`;
    }

    // DatabaseCollector: show SQL
    if (collectorClass === CollectorsMap.DatabaseCollector) {
        const queries = data.queries ?? data;
        const queryList = Array.isArray(queries) ? queries : [];
        if (queryList[currentIndex]) {
            const sql = queryList[currentIndex].sql || queryList[currentIndex].rawSql || '';
            const truncated = sql.length > 80 ? sql.slice(0, 80) + '...' : sql;
            return truncated;
        }
    }

    // ExceptionCollector: row[1] is the exception class
    if (collectorClass === CollectorsMap.ExceptionCollector) {
        return String(row[1]);
    }

    // RequestCollector: row[1] is 'request' or 'response'
    if (collectorClass === CollectorsMap.RequestCollector || collectorClass === CollectorsMap.CommandCollector) {
        return String(row[1]);
    }

    return null;
}

// ---------------------------------------------------------------------------
// Component
// ---------------------------------------------------------------------------

export const TimelineListView = ({data, filter, activeFilters}: TimelineListViewProps) => {
    const theme = useTheme();
    const debugEntry = useDebugEntry();
    const [fetchCollector] = useLazyGetCollectorInfoQuery();
    const [collectorData, setCollectorData] = useState<CollectorDataMap>(new Map());
    const [expandedIndex, setExpandedIndex] = useState<number | null>(null);

    // Identify which enrichable collectors are in the timeline
    const collectorsToFetch = useMemo(() => {
        if (!debugEntry) return [];
        const entryCollectors = new Set(debugEntry.collectors.map((c: any) => (typeof c === 'string' ? c : c.id)));
        const timelineCollectors = new Set(data.map((r) => r[2]));
        return [...enrichableCollectors].filter((c) => timelineCollectors.has(c) && entryCollectors.has(c));
    }, [data, debugEntry]);

    // Fetch collector data for enrichment
    useEffect(() => {
        if (!debugEntry || collectorsToFetch.length === 0) return;

        const newData = new Map(collectorData);
        let changed = false;

        for (const collector of collectorsToFetch) {
            if (newData.has(collector)) continue;
            changed = true;
            fetchCollector({id: debugEntry.id, collector}).then(({data: result}) => {
                setCollectorData((prev) => {
                    const next = new Map(prev);
                    next.set(collector, Array.isArray(result) ? result : result);
                    return next;
                });
            });
        }

        if (!changed) return;
    }, [debugEntry, collectorsToFetch, fetchCollector]);

    // Filter data
    const filtered = useMemo(() => {
        let result = data;
        if (activeFilters.size > 0) {
            result = result.filter((r) => {
                const shortName = r[2].split('\\').pop() ?? r[2];
                return activeFilters.has(shortName);
            });
        }
        if (filter) {
            const lower = filter.toLowerCase();
            result = result.filter((r) => r[2].toLowerCase().includes(lower));
        }
        return result;
    }, [data, filter, activeFilters]);

    // Time calculations (use full data for consistent scale)
    const timestamps = data.map((r) => r[0]);
    const minTime = Math.min(...timestamps);
    const maxTime = Math.max(...timestamps);
    const totalSpan = maxTime - minTime || 0.001;

    // Build scale ticks
    const tickCount = 5;
    const ticks: string[] = [];
    for (let i = 0; i <= tickCount; i++) {
        const t = (totalSpan / tickCount) * i;
        if (t < 0.001) {
            ticks.push(`${(t * 1000000).toFixed(0)}µs`);
        } else if (t < 1) {
            ticks.push(`${(t * 1000).toFixed(1)}ms`);
        } else {
            ticks.push(`${t.toFixed(2)}s`);
        }
    }

    // Build enriched details — track per-collector index for cross-referencing
    const enrichedDetails = useMemo(() => {
        const collectorIndexes = new Map<string, number>();
        return filtered.map((row) => getEnrichedDetail(row, collectorData, collectorIndexes));
    }, [filtered, collectorData]);

    const getColor = useCallback(
        (collectorClass: string) => {
            const key = collectorColorKeyMap[collectorClass] ?? 'default';
            const colors = theme.adp.collectorColors as any;
            return colors[key] ?? colors.default;
        },
        [theme],
    );

    const handleRowClick = useCallback(
        (index: number) => {
            setExpandedIndex(expandedIndex === index ? null : index);
        },
        [expandedIndex],
    );

    return (
        <Box>
            {filtered.map((row, index) => {
                const collectorClass = row[2];
                const shortName = collectorClass.split('\\').pop() ?? collectorClass;
                const relativeTime = row[0] - minTime;
                const offsetLabel = formatOffset(relativeTime);
                const color = getColor(collectorClass);
                const label = getCollectorLabel(collectorClass);
                const detail = enrichedDetails[index];
                const expanded = expandedIndex === index;

                return (
                    <Box key={index}>
                        <Row selected={expanded} onClick={() => handleRowClick(index)}>
                            <Dot sx={{backgroundColor: color.fg}} />
                            <OffsetLabel>{offsetLabel}</OffsetLabel>
                            <CollectorLabel sx={{color: color.fg}}>
                                {label !== 'Unknown' ? label : shortName}
                            </CollectorLabel>
                            {detail && <DetailText title={detail}>{detail}</DetailText>}
                        </Row>
                        <Collapse in={expanded}>
                            <DetailBox>
                                <Box sx={{display: 'flex', gap: 3, mb: 1, flexWrap: 'wrap'}}>
                                    <Typography variant="caption" sx={{color: 'text.disabled'}}>
                                        Time: {formatMicrotime(row[0])}
                                    </Typography>
                                    <Typography variant="caption" sx={{color: 'text.disabled'}}>
                                        Offset: {offsetLabel}
                                    </Typography>
                                    {row[1] != null && (
                                        <Typography variant="caption" sx={{color: 'text.disabled'}}>
                                            Ref: {String(row[1])}
                                        </Typography>
                                    )}
                                </Box>
                                <FileLink className={collectorClass}>
                                    <Typography
                                        variant="caption"
                                        component="span"
                                        sx={(t) => ({
                                            fontFamily: t.adp.fontFamilyMono,
                                            color: 'primary.main',
                                            '&:hover': {textDecoration: 'underline'},
                                        })}
                                    >
                                        {collectorClass}
                                    </Typography>
                                </FileLink>
                                {!!row[3] && (
                                    <JsonRenderer
                                        value={isClassString(row[3]) ? toObjectString(row[3], row[1]) : row[3]}
                                    />
                                )}
                            </DetailBox>
                        </Collapse>
                    </Box>
                );
            })}

            {/* Mini time scale */}
            <ScaleBar>
                {ticks.map((tick, i) => (
                    <span key={i}>{tick}</span>
                ))}
            </ScaleBar>
        </Box>
    );
};
