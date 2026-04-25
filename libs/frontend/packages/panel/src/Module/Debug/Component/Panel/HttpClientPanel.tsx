import {JsonRenderer} from '@app-dev-panel/panel/Module/Debug/Component/JsonRenderer';
import {useDebugEntry} from '@app-dev-panel/sdk/API/Debug/Context';
import {useLazyGetCollectorInfoQuery} from '@app-dev-panel/sdk/API/Debug/Debug';
import {BodyPreview} from '@app-dev-panel/sdk/Component/BodyPreview';
import {CodeHighlight} from '@app-dev-panel/sdk/Component/CodeHighlight';
import {EmptyState} from '@app-dev-panel/sdk/Component/EmptyState';
import {FileLink} from '@app-dev-panel/sdk/Component/FileLink';
import {FilterChip} from '@app-dev-panel/sdk/Component/FilterChip';
import {FilterInput} from '@app-dev-panel/sdk/Component/FilterInput';
import {SectionTitle} from '@app-dev-panel/sdk/Component/SectionTitle';
import {monoFontFamily} from '@app-dev-panel/sdk/Component/Theme/DefaultTheme';
import {CollectorsMap} from '@app-dev-panel/sdk/Helper/collectors';
import {formatMicrotime} from '@app-dev-panel/sdk/Helper/formatDate';
import {TabContext, TabPanel} from '@mui/lab';
import TabList from '@mui/lab/TabList';
import {Box, Chip, Collapse, Icon, IconButton, Tab, type Theme, Typography} from '@mui/material';
import {styled, useTheme} from '@mui/material/styles';
import {type SyntheticEvent, memo, useCallback, useDeferredValue, useEffect, useMemo, useState} from 'react';

type HttpClientEntry = {
    startTime: number;
    endTime: number;
    totalTime: number;
    method: string;
    uri: string;
    headers: Record<string, string[]>;
    line: string;
    responseRaw: string;
    responseStatus: number;
};

type HttpClientPanelProps = {data: HttpClientEntry[]};

const methodColor = (method: string, theme: Theme): string => {
    switch (method?.toUpperCase()) {
        case 'GET':
            return theme.palette.success.main;
        case 'POST':
            return theme.palette.primary.main;
        case 'PUT':
        case 'PATCH':
            return theme.palette.warning.main;
        case 'DELETE':
            return theme.palette.error.main;
        default:
            return theme.palette.text.disabled;
    }
};

const statusColor = (code: number, theme: Theme): string => {
    if (code >= 500) return theme.palette.error.main;
    if (code >= 400) return theme.palette.warning.main;
    if (code >= 300) return theme.palette.primary.main;
    return theme.palette.success.main;
};

const durationColor = (seconds: number, theme: Theme): string => {
    if (seconds >= 1) return theme.palette.error.main;
    if (seconds >= 0.3) return theme.palette.warning.main;
    return theme.palette.text.disabled;
};

const formatDuration = (seconds: number): string => {
    if (seconds >= 1) return `${seconds.toFixed(2)} s`;
    return `${(seconds * 1000).toFixed(1)} ms`;
};

// ---------------------------------------------------------------------------
// Styled components
// ---------------------------------------------------------------------------

const RequestRow = styled(Box, {shouldForwardProp: (p) => p !== 'expanded'})<{expanded?: boolean}>(
    ({theme, expanded}) => ({
        display: 'flex',
        alignItems: 'center',
        gap: theme.spacing(1.5),
        padding: theme.spacing(1, 1.5),
        borderBottom: `1px solid ${theme.palette.divider}`,
        cursor: 'pointer',
        transition: 'background-color 0.1s ease',
        backgroundColor: expanded ? theme.palette.action.hover : 'transparent',
        '&:hover': {backgroundColor: theme.palette.action.hover},
    }),
);

const UriCell = styled(Typography)(({theme}) => ({
    fontFamily: theme.adp.fontFamilyMono,
    fontSize: '12px',
    flex: 1,
    wordBreak: 'break-all',
}));

const DurationCell = styled(Typography)(({theme}) => ({
    fontFamily: theme.adp.fontFamilyMono,
    fontSize: '11px',
    flexShrink: 0,
    textAlign: 'right',
    width: 80,
}));

const TimeCell = styled(Typography)(({theme}) => ({
    fontFamily: theme.adp.fontFamilyMono,
    fontSize: '11px',
    flexShrink: 0,
    width: 110,
}));

const DetailBox = styled(Box)(({theme}) => ({
    padding: theme.spacing(2, 2, 2, 6),
    backgroundColor: theme.palette.action.hover,
    borderBottom: `1px solid ${theme.palette.divider}`,
    fontSize: '12px',
}));

const HeaderTable = styled('table')(({theme}) => ({
    width: '100%',
    borderCollapse: 'collapse',
    fontFamily: theme.adp.fontFamilyMono,
    fontSize: '12px',
    '& th': {
        textAlign: 'left',
        padding: theme.spacing(0.5, 1.5),
        fontWeight: 600,
        color: theme.palette.text.secondary,
        borderBottom: `1px solid ${theme.palette.divider}`,
        whiteSpace: 'nowrap',
        width: '30%',
        verticalAlign: 'top',
    },
    '& td': {
        padding: theme.spacing(0.5, 1.5),
        color: theme.palette.text.primary,
        borderBottom: `1px solid ${theme.palette.divider}`,
        wordBreak: 'break-all',
    },
    '& tr:last-child th, & tr:last-child td': {borderBottom: 'none'},
}));

// ---------------------------------------------------------------------------
// HTTP Stream types & view
// ---------------------------------------------------------------------------

type HttpStreamEntry = {uri: string; args: Record<string, any>};
type HttpStreamData = Record<string, HttpStreamEntry[]>;

const StreamOperationRow = styled(Box, {shouldForwardProp: (p) => p !== 'expanded'})<{expanded?: boolean}>(
    ({theme, expanded}) => ({
        display: 'flex',
        alignItems: 'center',
        gap: theme.spacing(1.5),
        padding: theme.spacing(1, 1.5),
        borderBottom: `1px solid ${theme.palette.divider}`,
        cursor: 'pointer',
        transition: 'background-color 0.1s ease',
        backgroundColor: expanded ? theme.palette.action.hover : 'transparent',
        '&:hover': {backgroundColor: theme.palette.action.hover},
    }),
);

const StreamUriCell = styled(Typography)(({theme}) => ({
    fontFamily: theme.adp.fontFamilyMono,
    fontSize: '12px',
    flex: 1,
    wordBreak: 'break-all',
}));

const HttpStreamView = ({data}: {data: HttpStreamData}) => {
    const operations = Object.keys(data);
    const totalCount = operations.reduce((sum, op) => sum + (data[op]?.length ?? 0), 0);
    const [expandedIndex, setExpandedIndex] = useState<number | null>(null);

    if (totalCount === 0) {
        return <EmptyState icon="stream" title="No HTTP stream operations found" />;
    }

    const flatItems = useMemo(() => {
        const result: Array<{operation: string; item: HttpStreamEntry; index: number}> = [];
        let idx = 0;
        for (const op of operations) {
            for (const item of data[op] ?? []) {
                result.push({operation: op, item, index: idx++});
            }
        }
        return result;
    }, [data, operations]);

    const groupedOps = useMemo(() => {
        const groups: Array<{operation: string; items: typeof flatItems}> = [];
        let current: (typeof groups)[0] | null = null;
        for (const entry of flatItems) {
            if (!current || current.operation !== entry.operation) {
                current = {operation: entry.operation, items: []};
                groups.push(current);
            }
            current.items.push(entry);
        }
        return groups;
    }, [flatItems]);

    return (
        <Box>
            <SectionTitle>{[`${totalCount} stream operations`]}</SectionTitle>
            {groupedOps.map(({operation, items}) => (
                <Box key={operation}>
                    <Box sx={{px: 1.5, py: 1, backgroundColor: 'action.hover'}}>
                        <Typography sx={{fontSize: '12px', fontWeight: 600, color: 'text.secondary'}}>
                            {operation}
                            <Chip
                                label={items.length}
                                size="small"
                                sx={{ml: 1, fontSize: '10px', height: 18, minWidth: 24, borderRadius: 1}}
                            />
                        </Typography>
                    </Box>
                    {items.map(({item, index: idx}) => {
                        const expanded = expandedIndex === idx;
                        const hasArgs = item.args && Object.keys(item.args).length > 0;
                        return (
                            <Box key={idx}>
                                <StreamOperationRow
                                    expanded={expanded}
                                    onClick={() => setExpandedIndex(expanded ? null : idx)}
                                >
                                    <StreamUriCell>{item.uri}</StreamUriCell>
                                    {hasArgs && (
                                        <IconButton size="small" sx={{flexShrink: 0}}>
                                            <Icon sx={{fontSize: 16}}>{expanded ? 'expand_less' : 'expand_more'}</Icon>
                                        </IconButton>
                                    )}
                                </StreamOperationRow>
                                {expanded && hasArgs && (
                                    <DetailBox>
                                        <Typography
                                            sx={{fontSize: '11px', fontWeight: 600, color: 'text.disabled', mb: 0.5}}
                                        >
                                            Arguments
                                        </Typography>
                                        <JsonRenderer value={item.args} />
                                    </DetailBox>
                                )}
                            </Box>
                        );
                    })}
                </Box>
            ))}
        </Box>
    );
};

// ---------------------------------------------------------------------------
// Helpers
// ---------------------------------------------------------------------------

function parseRawResponse(raw: string): {
    statusLine: string;
    headers: Array<{name: string; value: string}>;
    body: string;
} {
    if (!raw) return {statusLine: '', headers: [], body: ''};
    const parts = raw.split('\r\n\r\n');
    const headerSection = parts[0] || '';
    const body = parts.slice(1).join('\r\n\r\n');
    const lines = headerSection.split('\r\n').filter(Boolean);
    const statusLine = lines[0] || '';
    const headers: Array<{name: string; value: string}> = [];
    for (let i = 1; i < lines.length; i++) {
        const colonIndex = lines[i].indexOf(':');
        if (colonIndex > 0) {
            headers.push({
                name: lines[i].substring(0, colonIndex).trim(),
                value: lines[i].substring(colonIndex + 1).trim(),
            });
        }
    }
    return {statusLine, headers, body};
}

function extractHost(uri: string): string {
    try {
        const url = new URL(uri);
        return url.host;
    } catch {
        return '';
    }
}

function extractPath(uri: string): string {
    try {
        const url = new URL(uri);
        return url.pathname + url.search + url.hash;
    } catch {
        return uri;
    }
}

// ---------------------------------------------------------------------------
// Detail view
// ---------------------------------------------------------------------------

const RequestDetail = memo(({entry}: {entry: HttpClientEntry}) => {
    const theme = useTheme();
    const requestHeaders = useMemo(() => Object.entries(entry.headers || {}), [entry.headers]);
    const response = useMemo(() => parseRawResponse(entry.responseRaw), [entry.responseRaw]);

    return (
        <DetailBox>
            {/* Source file */}
            {entry.line && (
                <Box sx={{mb: 2}}>
                    <Typography
                        variant="caption"
                        sx={{fontWeight: 600, color: 'text.disabled', textTransform: 'uppercase', letterSpacing: 0.5}}
                    >
                        Source
                    </Typography>
                    <Box sx={{mt: 0.5}}>
                        <FileLink path={entry.line}>
                            <Typography
                                variant="caption"
                                component="span"
                                sx={{
                                    fontFamily: monoFontFamily,
                                    color: 'primary.main',
                                    textDecoration: 'none',
                                    '&:hover': {textDecoration: 'underline'},
                                }}
                            >
                                {entry.line}
                            </Typography>
                        </FileLink>
                    </Box>
                </Box>
            )}

            {/* Timing */}
            <Box sx={{mb: 2}}>
                <Typography
                    variant="caption"
                    sx={{fontWeight: 600, color: 'text.disabled', textTransform: 'uppercase', letterSpacing: 0.5}}
                >
                    Timing
                </Typography>
                <Box sx={{mt: 0.5, display: 'flex', gap: 3}}>
                    <Typography sx={{fontFamily: monoFontFamily, fontSize: '12px'}}>
                        <Box component="span" sx={{color: 'text.disabled'}}>
                            Start:{' '}
                        </Box>
                        {formatMicrotime(entry.startTime)}
                    </Typography>
                    <Typography sx={{fontFamily: monoFontFamily, fontSize: '12px'}}>
                        <Box component="span" sx={{color: 'text.disabled'}}>
                            End:{' '}
                        </Box>
                        {formatMicrotime(entry.endTime)}
                    </Typography>
                    <Typography
                        sx={{
                            fontFamily: monoFontFamily,
                            fontSize: '12px',
                            color: durationColor(entry.totalTime, theme),
                        }}
                    >
                        <Box component="span" sx={{color: 'text.disabled'}}>
                            Duration:{' '}
                        </Box>
                        {formatDuration(entry.totalTime)}
                    </Typography>
                </Box>
            </Box>

            {/* Request headers */}
            {requestHeaders.length > 0 && (
                <Box sx={{mb: 2}}>
                    <Typography
                        variant="caption"
                        sx={{fontWeight: 600, color: 'text.disabled', textTransform: 'uppercase', letterSpacing: 0.5}}
                    >
                        Request Headers
                    </Typography>
                    <Box
                        sx={{mt: 0.5, borderRadius: 1, border: '1px solid', borderColor: 'divider', overflow: 'hidden'}}
                    >
                        <HeaderTable>
                            <tbody>
                                {requestHeaders.map(([name, values], i) => (
                                    <tr key={i}>
                                        <th>{name}</th>
                                        <td>{Array.isArray(values) ? values.join(', ') : String(values)}</td>
                                    </tr>
                                ))}
                            </tbody>
                        </HeaderTable>
                    </Box>
                </Box>
            )}

            {/* Response headers */}
            {response.headers.length > 0 && (
                <Box sx={{mb: 2}}>
                    <Typography
                        variant="caption"
                        sx={{fontWeight: 600, color: 'text.disabled', textTransform: 'uppercase', letterSpacing: 0.5}}
                    >
                        Response Headers
                    </Typography>
                    {response.statusLine && (
                        <Typography
                            sx={{
                                fontFamily: monoFontFamily,
                                fontSize: '12px',
                                color: 'text.secondary',
                                mt: 0.5,
                                mb: 0.5,
                            }}
                        >
                            {response.statusLine}
                        </Typography>
                    )}
                    <Box sx={{borderRadius: 1, border: '1px solid', borderColor: 'divider', overflow: 'hidden'}}>
                        <HeaderTable>
                            <tbody>
                                {response.headers.map((h, i) => (
                                    <tr key={i}>
                                        <th>{h.name}</th>
                                        <td>{h.value}</td>
                                    </tr>
                                ))}
                            </tbody>
                        </HeaderTable>
                    </Box>
                </Box>
            )}

            {/* Response body */}
            {response.body && (
                <BodyPreview
                    body={response.body}
                    contentType={response.headers.find((h) => h.name.toLowerCase() === 'content-type')?.value}
                    title="Response Body"
                />
            )}

            {/* Raw response fallback when no parsed headers/body */}
            {!response.headers.length && !response.body && entry.responseRaw && (
                <Box>
                    <Typography
                        variant="caption"
                        sx={{fontWeight: 600, color: 'text.disabled', textTransform: 'uppercase', letterSpacing: 0.5}}
                    >
                        Raw Response
                    </Typography>
                    <Box
                        sx={{mt: 0.5, borderRadius: 1, overflow: 'hidden', border: '1px solid', borderColor: 'divider'}}
                    >
                        <CodeHighlight code={entry.responseRaw} language="plain" showLineNumbers={false} />
                    </Box>
                </Box>
            )}
        </DetailBox>
    );
});

// ---------------------------------------------------------------------------
// Helpers: status group
// ---------------------------------------------------------------------------

function statusGroup(code: number): string {
    if (code >= 500) return '5xx';
    if (code >= 400) return '4xx';
    if (code >= 300) return '3xx';
    if (code >= 200) return '2xx';
    return '1xx';
}

// ---------------------------------------------------------------------------
// Main component
// ---------------------------------------------------------------------------

// ---------------------------------------------------------------------------
// Client tab content (extracted from original HttpClientPanel)
// ---------------------------------------------------------------------------

// ---------------------------------------------------------------------------
// Top-level tabs: "Client" and "Stream"
// ---------------------------------------------------------------------------

const TopLevelTabList = styled(TabList)(({theme}) => ({
    minHeight: 40,
    borderBottom: `1px solid ${theme.palette.divider}`,
    marginBottom: theme.spacing(2),
    '& .MuiTab-root': {
        minHeight: 40,
        fontSize: '13px',
        fontWeight: 600,
        textTransform: 'none',
        padding: theme.spacing(0.5, 2),
    },
}));

export const HttpClientPanel = ({data}: HttpClientPanelProps) => {
    const debugEntry = useDebugEntry();
    const [activeTab, setActiveTab] = useState('client');
    const [fetchCollector, fetchResult] = useLazyGetCollectorInfoQuery();
    const [streamData, setStreamData] = useState<HttpStreamData | null>(null);

    useEffect(() => {
        if (!debugEntry) return;
        fetchCollector({id: debugEntry.id, collector: CollectorsMap.HttpStreamCollector})
            .then(({data: result, isError}) => {
                if (!isError && result) {
                    setStreamData(result as HttpStreamData);
                } else {
                    setStreamData(null);
                }
            })
            .catch(() => setStreamData(null));
    }, [debugEntry, fetchCollector]);

    const clientCount = data?.length ?? 0;
    const streamCount = useMemo(() => {
        if (!streamData) return 0;
        return Object.values(streamData).reduce((sum, entries) => sum + (entries?.length ?? 0), 0);
    }, [streamData]);

    const handleTabChange = useCallback((_: SyntheticEvent, value: string) => {
        setActiveTab(value);
    }, []);

    return (
        <TabContext value={activeTab}>
            <TopLevelTabList onChange={handleTabChange}>
                <Tab label={`Client (${clientCount})`} value="client" />
                <Tab label={`Stream (${streamCount})`} value="stream" />
            </TopLevelTabList>
            <TabPanel value="client" sx={{p: 0}}>
                <HttpClientTabContent data={data} />
            </TabPanel>
            <TabPanel value="stream" sx={{p: 0}}>
                {fetchResult.isFetching ? (
                    <EmptyState icon="hourglass_empty" title="Loading stream data..." />
                ) : streamData ? (
                    <HttpStreamView data={streamData} />
                ) : (
                    <EmptyState
                        icon="stream"
                        title="No HTTP stream data"
                        description="No stream operations were captured"
                    />
                )}
            </TabPanel>
        </TabContext>
    );
};

type RequestRowItemProps = {
    entry: HttpClientEntry;
    index: number;
    expanded: boolean;
    onToggle: (index: number) => void;
};

const RequestRowItem = memo(({entry, index, expanded, onToggle}: RequestRowItemProps) => {
    const theme = useTheme();
    const [wasExpanded, setWasExpanded] = useState(false);
    useEffect(() => {
        if (expanded) setWasExpanded(true);
    }, [expanded]);

    const handleClick = useCallback(() => onToggle(index), [onToggle, index]);

    return (
        <Box>
            <RequestRow expanded={expanded} onClick={handleClick}>
                <Chip
                    label={entry.method}
                    size="small"
                    sx={{
                        fontWeight: 700,
                        fontSize: '10px',
                        height: 22,
                        minWidth: 52,
                        backgroundColor: methodColor(entry.method, theme),
                        color: 'common.white',
                        borderRadius: 1,
                    }}
                />
                <Chip
                    label={entry.responseStatus}
                    size="small"
                    sx={{
                        fontWeight: 700,
                        fontSize: '10px',
                        height: 22,
                        minWidth: 36,
                        backgroundColor: statusColor(entry.responseStatus, theme),
                        color: 'common.white',
                        borderRadius: 1,
                    }}
                />
                <UriCell>
                    <Box component="span" sx={{color: 'text.disabled'}}>
                        {extractHost(entry.uri)}
                    </Box>
                    {extractPath(entry.uri)}
                </UriCell>
                <DurationCell sx={{color: durationColor(entry.totalTime, theme)}}>
                    {formatDuration(entry.totalTime)}
                </DurationCell>
                <TimeCell sx={{color: 'text.disabled'}}>{formatMicrotime(entry.startTime)}</TimeCell>
                <IconButton size="small" sx={{flexShrink: 0}}>
                    <Icon sx={{fontSize: 16}}>{expanded ? 'expand_less' : 'expand_more'}</Icon>
                </IconButton>
            </RequestRow>
            <Collapse in={expanded}>{wasExpanded && <RequestDetail entry={entry} />}</Collapse>
        </Box>
    );
});

const HttpClientTabContent = ({data}: HttpClientPanelProps) => {
    const theme = useTheme();
    const [filter, setFilter] = useState('');
    const deferredFilter = useDeferredValue(filter);
    const [activeFilters, setActiveFilters] = useState<Set<string>>(new Set());
    const [expandedIndex, setExpandedIndex] = useState<number | null>(null);

    const handleRowToggle = useCallback((index: number) => {
        setExpandedIndex((prev) => (prev === index ? null : index));
    }, []);

    const toggleFilter = useCallback((name: string) => {
        setActiveFilters((prev) => {
            const next = new Set(prev);
            if (next.has(name)) {
                next.delete(name);
            } else {
                next.add(name);
            }
            return next;
        });
    }, []);

    const badgeCounts = useMemo(() => {
        if (!data) return [];
        const counts = new Map<string, number>();
        for (const entry of data) {
            const method = entry.method.toUpperCase();
            counts.set(method, (counts.get(method) ?? 0) + 1);
        }
        return [...counts.entries()].sort((a, b) => b[1] - a[1]);
    }, [data]);

    const statusBadgeCounts = useMemo(() => {
        if (!data) return [];
        const counts = new Map<string, number>();
        for (const entry of data) {
            const group = statusGroup(entry.responseStatus);
            counts.set(group, (counts.get(group) ?? 0) + 1);
        }
        const order = ['2xx', '3xx', '4xx', '5xx', '1xx'];
        return [...counts.entries()].sort((a, b) => order.indexOf(a[0]) - order.indexOf(b[0]));
    }, [data]);

    const totalTime = useMemo(() => (data ? data.reduce((sum, e) => sum + e.totalTime, 0) : 0), [data]);

    const filtered = useMemo(() => {
        if (!data) return [];
        let result = data;
        if (activeFilters.size > 0) {
            result = result.filter((e) => {
                const method = e.method.toUpperCase();
                const group = statusGroup(e.responseStatus);
                return activeFilters.has(method) || activeFilters.has(group);
            });
        }
        if (deferredFilter) {
            const lowerFilter = deferredFilter.toLowerCase();
            result = result.filter(
                (e) =>
                    e.uri.toLowerCase().includes(lowerFilter) ||
                    e.method.toLowerCase().includes(lowerFilter) ||
                    String(e.responseStatus).includes(deferredFilter),
            );
        }
        return result;
    }, [data, deferredFilter, activeFilters]);

    if (!data || data.length === 0) {
        return (
            <EmptyState
                icon="cloud"
                title="No HTTP client requests found"
                description="No outgoing HTTP requests were captured during this request"
            />
        );
    }

    const statusBadgeBg = (group: string): string => {
        switch (group) {
            case '2xx':
                return theme.palette.success.main;
            case '3xx':
                return theme.palette.primary.main;
            case '4xx':
                return theme.palette.warning.main;
            case '5xx':
                return theme.palette.error.main;
            default:
                return theme.palette.text.disabled;
        }
    };

    return (
        <Box>
            <SectionTitle action={<FilterInput value={filter} onChange={setFilter} placeholder="Filter requests..." />}>
                {[`${filtered.length} http requests`, `${formatDuration(totalTime)} total`]}
            </SectionTitle>

            {(badgeCounts.length > 1 || statusBadgeCounts.length > 1) && (
                <Box sx={{display: 'flex', flexWrap: 'wrap', gap: 0.75, mb: 2}}>
                    {statusBadgeCounts.map(([group, count]) => (
                        <FilterChip
                            key={group}
                            label={group}
                            count={count}
                            color={statusBadgeBg(group)}
                            active={activeFilters.has(group)}
                            onClick={() => toggleFilter(group)}
                        />
                    ))}
                    {badgeCounts.map(([method, count]) => (
                        <FilterChip
                            key={method}
                            label={method}
                            count={count}
                            color={methodColor(method, theme)}
                            active={activeFilters.has(method)}
                            onClick={() => toggleFilter(method)}
                        />
                    ))}
                    {activeFilters.size > 0 && <FilterChip label="Clear" onClick={() => setActiveFilters(new Set())} />}
                </Box>
            )}

            {filtered.map((entry, index) => (
                <RequestRowItem
                    key={index}
                    entry={entry}
                    index={index}
                    expanded={expandedIndex === index}
                    onToggle={handleRowToggle}
                />
            ))}
        </Box>
    );
};
