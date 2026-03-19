import {JsonRenderer} from '@app-dev-panel/panel/Module/Debug/Component/JsonRenderer';
import {CodeHighlight} from '@app-dev-panel/sdk/Component/CodeHighlight';
import {EmptyState} from '@app-dev-panel/sdk/Component/EmptyState';
import {SectionTitle} from '@app-dev-panel/sdk/Component/SectionTitle';
import {primitives} from '@app-dev-panel/sdk/Component/Theme/tokens';
import {parseFilePathWithLineAnchor} from '@app-dev-panel/sdk/Helper/filePathParser';
import {formatMicrotime} from '@app-dev-panel/sdk/Helper/formatDate';
import {Box, Chip, Collapse, Icon, IconButton, TextField, type Theme, Typography} from '@mui/material';
import {styled, useTheme} from '@mui/material/styles';
import {useCallback, useDeferredValue, useMemo, useState} from 'react';

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

const UriCell = styled(Typography)({
    fontFamily: primitives.fontFamilyMono,
    fontSize: '12px',
    flex: 1,
    wordBreak: 'break-all',
});

const DurationCell = styled(Typography)({
    fontFamily: primitives.fontFamilyMono,
    fontSize: '11px',
    flexShrink: 0,
    textAlign: 'right',
    width: 80,
});

const TimeCell = styled(Typography)({
    fontFamily: primitives.fontFamilyMono,
    fontSize: '11px',
    flexShrink: 0,
    width: 110,
});

const DetailBox = styled(Box)(({theme}) => ({
    padding: theme.spacing(2, 2, 2, 6),
    backgroundColor: theme.palette.action.hover,
    borderBottom: `1px solid ${theme.palette.divider}`,
    fontSize: '12px',
}));

const HeaderTable = styled('table')(({theme}) => ({
    width: '100%',
    borderCollapse: 'collapse',
    fontFamily: primitives.fontFamilyMono,
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

const RequestDetail = ({entry}: {entry: HttpClientEntry}) => {
    const theme = useTheme();
    const requestHeaders = Object.entries(entry.headers || {});
    const response = parseRawResponse(entry.responseRaw);

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
                        <Typography
                            variant="caption"
                            component="a"
                            href={`/inspector/files?path=${parseFilePathWithLineAnchor(entry.line)}`}
                            sx={{
                                fontFamily: primitives.fontFamilyMono,
                                color: 'primary.main',
                                textDecoration: 'none',
                                '&:hover': {textDecoration: 'underline'},
                            }}
                        >
                            {entry.line}
                        </Typography>
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
                    <Typography sx={{fontFamily: primitives.fontFamilyMono, fontSize: '12px'}}>
                        <Box component="span" sx={{color: 'text.disabled'}}>
                            Start:{' '}
                        </Box>
                        {formatMicrotime(entry.startTime)}
                    </Typography>
                    <Typography sx={{fontFamily: primitives.fontFamilyMono, fontSize: '12px'}}>
                        <Box component="span" sx={{color: 'text.disabled'}}>
                            End:{' '}
                        </Box>
                        {formatMicrotime(entry.endTime)}
                    </Typography>
                    <Typography
                        sx={{
                            fontFamily: primitives.fontFamilyMono,
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
                                fontFamily: primitives.fontFamilyMono,
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
                <Box>
                    <Typography
                        variant="caption"
                        sx={{fontWeight: 600, color: 'text.disabled', textTransform: 'uppercase', letterSpacing: 0.5}}
                    >
                        Response Body
                    </Typography>
                    <Box
                        sx={{mt: 0.5, borderRadius: 1, overflow: 'hidden', border: '1px solid', borderColor: 'divider'}}
                    >
                        {(() => {
                            const contentTypeHeader = response.headers.find(
                                (h) => h.name.toLowerCase() === 'content-type',
                            );
                            const isJson = contentTypeHeader && /json/i.test(contentTypeHeader.value);
                            if (isJson) {
                                try {
                                    const parsed = JSON.parse(response.body);
                                    return (
                                        <Box sx={{p: 1.5}}>
                                            <JsonRenderer value={parsed} />
                                        </Box>
                                    );
                                } catch {
                                    /* fall through */
                                }
                            }
                            return <CodeHighlight code={response.body} language="plain" showLineNumbers={false} />;
                        })()}
                    </Box>
                </Box>
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
};

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

export const HttpClientPanel = ({data}: HttpClientPanelProps) => {
    const theme = useTheme();
    const [filter, setFilter] = useState('');
    const deferredFilter = useDeferredValue(filter);
    const [activeFilters, setActiveFilters] = useState<Set<string>>(new Set());
    const [expandedIndex, setExpandedIndex] = useState<number | null>(null);

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

    if (!data || data.length === 0) {
        return (
            <EmptyState
                icon="cloud"
                title="No HTTP client requests found"
                description="No outgoing HTTP requests were captured during this request"
            />
        );
    }

    const filtered = useMemo(() => {
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
            <Box sx={{display: 'flex', alignItems: 'center', gap: 2, mb: 2}}>
                <SectionTitle>{`${filtered.length} http requests`}</SectionTitle>
                <SectionTitle>{`${formatDuration(totalTime)} total`}</SectionTitle>
                <TextField
                    size="small"
                    placeholder="Filter requests..."
                    value={filter}
                    onChange={(e) => setFilter(e.target.value)}
                    InputProps={{sx: {fontSize: '13px'}}}
                    sx={{ml: 'auto', width: 240}}
                />
            </Box>

            {(badgeCounts.length > 1 || statusBadgeCounts.length > 1) && (
                <Box sx={{display: 'flex', flexWrap: 'wrap', gap: 0.75, mb: 2}}>
                    {statusBadgeCounts.map(([group, count]) => {
                        const isActive = activeFilters.has(group);
                        return (
                            <Chip
                                key={group}
                                label={`${group} (${count})`}
                                size="small"
                                onClick={() => toggleFilter(group)}
                                sx={{
                                    fontSize: '11px',
                                    height: 24,
                                    borderRadius: 1,
                                    fontWeight: 600,
                                    cursor: 'pointer',
                                    backgroundColor: isActive ? statusBadgeBg(group) : 'transparent',
                                    color: isActive ? 'common.white' : statusBadgeBg(group),
                                    border: `1px solid ${statusBadgeBg(group)}`,
                                }}
                            />
                        );
                    })}
                    {badgeCounts.map(([method, count]) => {
                        const isActive = activeFilters.has(method);
                        const color = methodColor(method, theme);
                        return (
                            <Chip
                                key={method}
                                label={`${method} (${count})`}
                                size="small"
                                onClick={() => toggleFilter(method)}
                                sx={{
                                    fontSize: '11px',
                                    height: 24,
                                    borderRadius: 1,
                                    fontWeight: 600,
                                    cursor: 'pointer',
                                    backgroundColor: isActive ? color : 'transparent',
                                    color: isActive ? 'common.white' : color,
                                    border: `1px solid ${color}`,
                                }}
                            />
                        );
                    })}
                    {activeFilters.size > 0 && (
                        <Chip
                            label="Clear"
                            size="small"
                            onClick={() => setActiveFilters(new Set())}
                            variant="outlined"
                            sx={{fontSize: '11px', height: 24, borderRadius: 1}}
                        />
                    )}
                </Box>
            )}

            {filtered.map((entry, index) => {
                const expanded = expandedIndex === index;
                return (
                    <Box key={index}>
                        <RequestRow expanded={expanded} onClick={() => setExpandedIndex(expanded ? null : index)}>
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
                        <Collapse in={expanded}>
                            <RequestDetail entry={entry} />
                        </Collapse>
                    </Box>
                );
            })}
        </Box>
    );
};
