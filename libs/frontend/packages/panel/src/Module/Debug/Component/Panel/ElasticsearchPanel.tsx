import {JsonRenderer} from '@app-dev-panel/panel/Module/Debug/Component/JsonRenderer';
import {EmptyState} from '@app-dev-panel/sdk/Component/EmptyState';
import {FilterInput} from '@app-dev-panel/sdk/Component/FilterInput';
import {SectionTitle} from '@app-dev-panel/sdk/Component/SectionTitle';
import {monoFontFamily} from '@app-dev-panel/sdk/Component/Theme/DefaultTheme';
import {Box, Chip, Collapse, Icon, IconButton, type Theme, Typography} from '@mui/material';
import {styled, useTheme} from '@mui/material/styles';
import {useCallback, useDeferredValue, useMemo, useState} from 'react';

type ElasticsearchRequest = {
    method: string;
    endpoint: string;
    index: string;
    body: string;
    line: string;
    status: string;
    startTime: number;
    endTime: number;
    duration: number;
    statusCode: number;
    responseBody: string;
    responseSize: number;
    hitsCount: number | null;
    exception: string | null;
};

type DuplicateGroup = {key: string; count: number; indices: number[]};

type ElasticsearchData = {
    requests: ElasticsearchRequest[];
    duplicates: {groups: DuplicateGroup[]; totalDuplicatedCount: number};
};

type ElasticsearchPanelProps = {data: ElasticsearchData};

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
    if (code === 0) return theme.palette.text.disabled;
    if (code >= 400) return theme.palette.error.main;
    if (code >= 300) return theme.palette.warning.main;
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

const EndpointCell = styled(Typography)(({theme}) => ({
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

const DetailBox = styled(Box)(({theme}) => ({
    padding: theme.spacing(2, 2, 2, 6),
    backgroundColor: theme.palette.action.hover,
    borderBottom: `1px solid ${theme.palette.divider}`,
    fontSize: '12px',
}));

const RequestDetail = ({entry}: {entry: ElasticsearchRequest}) => {
    const theme = useTheme();
    let parsedBody: object | null = null;
    let parsedResponse: object | null = null;
    try {
        if (entry.body) parsedBody = JSON.parse(entry.body);
    } catch {
        // Falls through to raw text rendering below
    }
    try {
        if (entry.responseBody) parsedResponse = JSON.parse(entry.responseBody);
    } catch {
        // Falls through to raw text rendering below
    }

    return (
        <DetailBox>
            {entry.line && (
                <Box sx={{mb: 2}}>
                    <Typography
                        variant="caption"
                        sx={{fontWeight: 600, color: 'text.disabled', textTransform: 'uppercase', letterSpacing: 0.5}}
                    >
                        Source
                    </Typography>
                    <Typography sx={{fontFamily: monoFontFamily, fontSize: '12px', color: 'primary.main', mt: 0.5}}>
                        {entry.line}
                    </Typography>
                </Box>
            )}

            <Box sx={{mb: 2}}>
                <Typography
                    variant="caption"
                    sx={{fontWeight: 600, color: 'text.disabled', textTransform: 'uppercase', letterSpacing: 0.5}}
                >
                    Timing
                </Typography>
                <Box sx={{mt: 0.5, display: 'flex', gap: 3}}>
                    <Typography
                        sx={{fontFamily: monoFontFamily, fontSize: '12px', color: durationColor(entry.duration, theme)}}
                    >
                        Duration: {formatDuration(entry.duration)}
                    </Typography>
                    {entry.responseSize > 0 && (
                        <Typography sx={{fontFamily: monoFontFamily, fontSize: '12px', color: 'text.secondary'}}>
                            Response size: {entry.responseSize} bytes
                        </Typography>
                    )}
                    {entry.hitsCount != null && (
                        <Typography sx={{fontFamily: monoFontFamily, fontSize: '12px', color: 'text.secondary'}}>
                            Hits: {entry.hitsCount}
                        </Typography>
                    )}
                </Box>
            </Box>

            {parsedBody && (
                <Box sx={{mb: 2}}>
                    <Typography
                        variant="caption"
                        sx={{fontWeight: 600, color: 'text.disabled', textTransform: 'uppercase', letterSpacing: 0.5}}
                    >
                        Request Body
                    </Typography>
                    <Box sx={{mt: 0.5}}>
                        <JsonRenderer value={parsedBody} />
                    </Box>
                </Box>
            )}
            {!parsedBody && entry.body && (
                <Box sx={{mb: 2}}>
                    <Typography
                        variant="caption"
                        sx={{fontWeight: 600, color: 'text.disabled', textTransform: 'uppercase', letterSpacing: 0.5}}
                    >
                        Request Body
                    </Typography>
                    <Box
                        component="pre"
                        sx={{
                            mt: 0.5,
                            fontFamily: monoFontFamily,
                            fontSize: '12px',
                            whiteSpace: 'pre-wrap',
                            wordBreak: 'break-all',
                        }}
                    >
                        {entry.body}
                    </Box>
                </Box>
            )}

            {parsedResponse && (
                <Box sx={{mb: 2}}>
                    <Typography
                        variant="caption"
                        sx={{fontWeight: 600, color: 'text.disabled', textTransform: 'uppercase', letterSpacing: 0.5}}
                    >
                        Response Body
                    </Typography>
                    <Box sx={{mt: 0.5}}>
                        <JsonRenderer value={parsedResponse} />
                    </Box>
                </Box>
            )}

            {entry.exception && (
                <Box>
                    <Typography
                        variant="caption"
                        sx={{fontWeight: 600, color: 'error.main', textTransform: 'uppercase', letterSpacing: 0.5}}
                    >
                        Exception
                    </Typography>
                    <Box
                        component="pre"
                        sx={{
                            mt: 0.5,
                            fontFamily: monoFontFamily,
                            fontSize: '12px',
                            color: 'error.main',
                            whiteSpace: 'pre-wrap',
                        }}
                    >
                        {entry.exception}
                    </Box>
                </Box>
            )}
        </DetailBox>
    );
};

export const ElasticsearchPanel = ({data}: ElasticsearchPanelProps) => {
    const theme = useTheme();
    const [filter, setFilter] = useState('');
    const deferredFilter = useDeferredValue(filter);
    const [activeFilters, setActiveFilters] = useState<Set<string>>(new Set());
    const [expandedIndex, setExpandedIndex] = useState<number | null>(null);

    const requests = data?.requests ?? [];

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

    const methodBadgeCounts = useMemo(() => {
        const counts = new Map<string, number>();
        for (const entry of requests) {
            const method = entry.method.toUpperCase();
            counts.set(method, (counts.get(method) ?? 0) + 1);
        }
        return [...counts.entries()].sort((a, b) => b[1] - a[1]);
    }, [requests]);

    const statusBadgeCounts = useMemo(() => {
        const counts = new Map<string, number>();
        for (const entry of requests) {
            const label = entry.status === 'error' ? 'error' : 'success';
            counts.set(label, (counts.get(label) ?? 0) + 1);
        }
        return [...counts.entries()];
    }, [requests]);

    const totalTime = useMemo(() => requests.reduce((sum, e) => sum + e.duration, 0), [requests]);

    const filtered = useMemo(() => {
        let result = requests;
        if (activeFilters.size > 0) {
            result = result.filter((e) => {
                const method = e.method.toUpperCase();
                return activeFilters.has(method) || activeFilters.has(e.status);
            });
        }
        if (deferredFilter) {
            const lowerFilter = deferredFilter.toLowerCase();
            result = result.filter(
                (e) =>
                    e.endpoint.toLowerCase().includes(lowerFilter) ||
                    e.index.toLowerCase().includes(lowerFilter) ||
                    e.method.toLowerCase().includes(lowerFilter) ||
                    e.body.toLowerCase().includes(lowerFilter),
            );
        }
        return result;
    }, [requests, deferredFilter, activeFilters]);

    if (requests.length === 0) {
        return (
            <EmptyState
                icon="search"
                title="No Elasticsearch requests found"
                description="No Elasticsearch operations were captured during this request"
            />
        );
    }

    return (
        <Box>
            <SectionTitle action={<FilterInput value={filter} onChange={setFilter} placeholder="Filter requests..." />}>
                {[`${filtered.length} elasticsearch requests`, `${formatDuration(totalTime)} total`]}
            </SectionTitle>

            {(methodBadgeCounts.length > 1 || statusBadgeCounts.length > 1) && (
                <Box sx={{display: 'flex', flexWrap: 'wrap', gap: 0.75, mb: 2}}>
                    {statusBadgeCounts.map(([label, count]) => {
                        const isActive = activeFilters.has(label);
                        const color = label === 'error' ? theme.palette.error.main : theme.palette.success.main;
                        return (
                            <Chip
                                key={label}
                                label={`${label} (${count})`}
                                size="small"
                                onClick={() => toggleFilter(label)}
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
                    {methodBadgeCounts.map(([method, count]) => {
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

            {data.duplicates && data.duplicates.groups.length > 0 && (
                <Box
                    sx={{
                        mx: 1.5,
                        mb: 2,
                        p: 1.5,
                        borderRadius: 1,
                        backgroundColor: 'warning.light',
                        border: '1px solid',
                        borderColor: 'warning.main',
                    }}
                >
                    <Typography sx={{fontSize: '12px', fontWeight: 600, color: 'warning.dark'}}>
                        {data.duplicates.groups.length} duplicate groups ({data.duplicates.totalDuplicatedCount} total
                        duplicated requests)
                    </Typography>
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
                            {entry.statusCode > 0 && (
                                <Chip
                                    label={entry.statusCode}
                                    size="small"
                                    sx={{
                                        fontWeight: 700,
                                        fontSize: '10px',
                                        height: 22,
                                        minWidth: 36,
                                        backgroundColor: statusColor(entry.statusCode, theme),
                                        color: 'common.white',
                                        borderRadius: 1,
                                    }}
                                />
                            )}
                            {entry.status === 'error' && entry.statusCode === 0 && (
                                <Chip
                                    label="ERR"
                                    size="small"
                                    sx={{
                                        fontWeight: 700,
                                        fontSize: '10px',
                                        height: 22,
                                        backgroundColor: theme.palette.error.main,
                                        color: 'common.white',
                                        borderRadius: 1,
                                    }}
                                />
                            )}
                            <EndpointCell>
                                {entry.index && (
                                    <Box component="span" sx={{color: 'text.disabled'}}>
                                        {entry.index}
                                    </Box>
                                )}
                                {entry.index ? entry.endpoint.slice(entry.index.length + 1) : entry.endpoint}
                            </EndpointCell>
                            {entry.hitsCount != null && (
                                <Typography
                                    sx={{
                                        fontFamily: monoFontFamily,
                                        fontSize: '11px',
                                        color: 'text.secondary',
                                        flexShrink: 0,
                                        width: 60,
                                        textAlign: 'right',
                                    }}
                                >
                                    {entry.hitsCount} hits
                                </Typography>
                            )}
                            <DurationCell sx={{color: durationColor(entry.duration, theme)}}>
                                {formatDuration(entry.duration)}
                            </DurationCell>
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
