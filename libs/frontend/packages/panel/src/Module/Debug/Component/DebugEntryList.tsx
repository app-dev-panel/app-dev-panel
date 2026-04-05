import {changeEntryAction} from '@app-dev-panel/sdk/API/Debug/Context';
import {DebugEntry, useGetDebugQuery} from '@app-dev-panel/sdk/API/Debug/Debug';
import {setPrefillMessage} from '@app-dev-panel/sdk/API/Llm/AiChatSlice';
import {getEntrySearchText, isDebugEntryAboutConsole, isDebugEntryAboutWeb} from '@app-dev-panel/sdk/Helper/debugEntry';
import {formatBytes} from '@app-dev-panel/sdk/Helper/formatBytes';
import {formatDate, formatTime} from '@app-dev-panel/sdk/Helper/formatDate';
import {searchVariants} from '@app-dev-panel/sdk/Helper/layoutTranslit';
import AutoAwesomeIcon from '@mui/icons-material/AutoAwesome';
import {
    Alert,
    AlertTitle,
    Box,
    Chip,
    CircularProgress,
    Icon,
    IconButton,
    InputAdornment,
    TextField,
    Tooltip,
    Typography,
    type Theme,
} from '@mui/material';
import {styled, useTheme} from '@mui/material/styles';
import useMediaQuery from '@mui/material/useMediaQuery';
import React, {useCallback, useDeferredValue, useMemo, useRef, useState} from 'react';
import {useDispatch} from 'react-redux';
import {useNavigate} from 'react-router';
import {Virtuoso, type VirtuosoHandle} from 'react-virtuoso';

// ---------------------------------------------------------------------------
// Styled components
// ---------------------------------------------------------------------------

const EntryRow = styled(Box)(({theme}) => ({
    display: 'flex',
    alignItems: 'center',
    gap: theme.spacing(1.5),
    padding: theme.spacing(1, 1.5),
    borderBottom: `1px solid ${theme.palette.divider}`,
    cursor: 'pointer',
    transition: 'background-color 0.1s ease',
    '&:hover': {backgroundColor: theme.palette.action.hover},
    [theme.breakpoints.down('sm')]: {gap: theme.spacing(0.75), padding: theme.spacing(0.75, 1)},
}));

const MethodLabel = styled(Typography)(({theme}) => ({
    fontFamily: theme.adp.fontFamilyMono,
    fontSize: '11px',
    fontWeight: 600,
    minWidth: 44,
    flexShrink: 0,
}));

const PathLabel = styled(Typography)(({theme}) => ({
    fontFamily: theme.adp.fontFamilyMono,
    fontSize: '13px',
    flex: 1,
    overflow: 'hidden',
    textOverflow: 'ellipsis',
    whiteSpace: 'nowrap',
}));

const MetaLabel = styled(Typography)(({theme}) => ({
    fontFamily: theme.adp.fontFamilyMono,
    fontSize: '10px',
    flexShrink: 0,
    [theme.breakpoints.down('sm')]: {display: 'none'},
}));

const StatusChip = styled(Chip)({fontSize: '10px', height: 20, minWidth: 36, fontWeight: 600, borderRadius: 4});

const StatCell = styled(Box)(({theme}) => ({
    display: 'flex',
    alignItems: 'center',
    gap: theme.spacing(0.5),
    flexShrink: 0,
    [theme.breakpoints.down('sm')]: {display: 'none'},
}));

const StatLabel = styled(Typography)(({theme}) => ({fontFamily: theme.adp.fontFamilyMono, fontSize: '10px'}));

// ---------------------------------------------------------------------------
// Helpers
// ---------------------------------------------------------------------------

const hasN1Indicator = (entry: DebugEntry): boolean => {
    return (
        (entry.db?.duplicateGroups ?? 0) > 0 ||
        (entry.view?.duplicateGroups ?? 0) > 0 ||
        (entry.queue?.duplicateGroups ?? 0) > 0
    );
};

const methodColor = (method: string, theme: Theme): string => {
    switch (method.toUpperCase()) {
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
            return theme.palette.text.secondary;
    }
};

const statusColor = (status: number, theme: Theme): string => {
    if (status >= 500) return theme.palette.error.main;
    if (status >= 400) return theme.palette.warning.main;
    return theme.palette.success.main;
};

const statusBg = (status: number, theme: Theme): string => {
    if (status >= 500) return theme.palette.error.light;
    if (status >= 400) return theme.palette.primary.light;
    return theme.palette.primary.light;
};

function matchesFilter(entry: DebugEntry, query: string): boolean {
    const variants = searchVariants(query);
    const text = getEntrySearchText(entry).toLowerCase();
    const id = entry.id.toLowerCase();
    return variants.some((q) => text.includes(q) || id.includes(q));
}

// ---------------------------------------------------------------------------
// Component
// ---------------------------------------------------------------------------

export const DebugEntryList = () => {
    const theme = useTheme();
    const compact = useMediaQuery(theme.breakpoints.down('md'));
    const {data: entries, isLoading, isFetching, refetch} = useGetDebugQuery();
    const dispatch = useDispatch();
    const navigate = useNavigate();
    const [filter, setFilter] = useState('');
    const deferredFilter = useDeferredValue(filter);
    const handleFilterChange = useCallback((e: React.ChangeEvent<HTMLInputElement>) => setFilter(e.target.value), []);
    const handleFilterClear = useCallback(() => setFilter(''), []);

    const handleExplainWithAi = useCallback(() => {
        if (!entries || entries.length === 0) return;
        const visible = filtered.length > 0 ? filtered.slice(0, 20) : entries.slice(0, 20);
        const summary = visible
            .map((e) => {
                if (isDebugEntryAboutWeb(e)) {
                    const dur = e.web?.request?.processingTime
                        ? `${(e.web.request.processingTime * 1000).toFixed(0)}ms`
                        : '?';
                    const mem = e.web?.memory?.peakUsage ? formatBytes(e.web.memory.peakUsage) : '?';
                    const db = e.db?.queries?.total ?? 0;
                    const n1 = hasN1Indicator(e) ? ' [N+1]' : '';
                    const err = e.exception ? ` ERROR: ${e.exception.message}` : '';
                    return `${e.request.method} ${e.request.path} → ${e.response.statusCode} (${dur}, ${mem}, ${db} queries${n1})${err}`;
                }
                if (isDebugEntryAboutConsole(e)) {
                    return `CLI: ${e.command?.input ?? 'unknown'} → exit ${e.command?.exitCode}`;
                }
                return e.id;
            })
            .join('\n');

        const prompt = `Analyze these debug entries from my application and explain what you see. Look for patterns, potential issues (slow requests, errors, N+1 queries, high memory usage), and suggest improvements.\n\nUse MCP tools to fetch detailed debug data for entries that look problematic.\n\n${summary}`;
        dispatch(setPrefillMessage(prompt));
        navigate('/llm');
    }, [entries, filtered, dispatch, navigate]);

    const handleEntryClick = useCallback(
        (entry: DebugEntry) => {
            dispatch(changeEntryAction(entry));
            navigate(`/debug?debugEntry=${entry.id}`);
        },
        [dispatch, navigate],
    );

    const virtuosoRef = useRef<VirtuosoHandle>(null);

    const filtered = useMemo(() => {
        if (!entries) return [];
        if (!deferredFilter.trim()) return entries;
        return entries.filter((entry) => matchesFilter(entry, deferredFilter));
    }, [entries, deferredFilter]);

    const renderEntry = useCallback(
        (_index: number, entry: DebugEntry) => {
            if (isDebugEntryAboutWeb(entry)) {
                const duration = entry.web?.request?.processingTime;
                const memory = entry.web?.memory?.peakUsage;
                return (
                    <EntryRow onClick={() => handleEntryClick(entry)}>
                        <MethodLabel sx={{color: methodColor(entry.request.method, theme)}}>
                            {entry.request.method}
                        </MethodLabel>
                        <PathLabel>{entry.request.path}</PathLabel>
                        {duration != null && (
                            <MetaLabel sx={{color: 'primary.main'}}>{(duration * 1000).toFixed(0)}ms</MetaLabel>
                        )}
                        {memory != null && <MetaLabel sx={{color: 'success.main'}}>{formatBytes(memory)}</MetaLabel>}
                        {entry.logger?.total != null && entry.logger.total > 0 && (
                            <StatCell>
                                <Icon sx={{fontSize: 13, color: 'text.disabled'}}>description</Icon>
                                <StatLabel sx={{color: 'text.disabled'}}>{entry.logger.total}</StatLabel>
                            </StatCell>
                        )}
                        {entry.db?.queries?.total != null && entry.db.queries.total > 0 && (
                            <StatCell>
                                <Icon
                                    sx={{fontSize: 13, color: entry.db.queries.error ? 'error.main' : 'text.disabled'}}
                                >
                                    storage
                                </Icon>
                                <StatLabel sx={{color: entry.db.queries.error ? 'error.main' : 'text.disabled'}}>
                                    {entry.db.queries.total}
                                </StatLabel>
                            </StatCell>
                        )}
                        {hasN1Indicator(entry) && (
                            <Tooltip title="Duplicate operations detected (N+1)">
                                <Chip
                                    label="N+1"
                                    size="small"
                                    color="warning"
                                    sx={{
                                        fontWeight: 700,
                                        fontSize: '9px',
                                        height: 18,
                                        minWidth: 32,
                                        borderRadius: 1,
                                        flexShrink: 0,
                                    }}
                                />
                            </Tooltip>
                        )}
                        {entry.exception && (
                            <Tooltip title={entry.exception.message}>
                                <Icon sx={{fontSize: 15, color: 'error.main'}}>error</Icon>
                            </Tooltip>
                        )}
                        <StatusChip
                            label={entry.response.statusCode}
                            size="small"
                            sx={{
                                color: statusColor(entry.response.statusCode, theme),
                                backgroundColor: statusBg(entry.response.statusCode, theme),
                            }}
                        />
                        <Tooltip title={compact ? formatDate(entry.web.request.startTime) : ''} arrow>
                            <MetaLabel sx={{color: 'text.disabled'}}>
                                {compact
                                    ? formatTime(entry.web.request.startTime)
                                    : formatDate(entry.web.request.startTime)}
                            </MetaLabel>
                        </Tooltip>
                    </EntryRow>
                );
            }

            if (isDebugEntryAboutConsole(entry)) {
                const duration = entry.console?.request?.processingTime;
                const memory = entry.console?.memory?.peakUsage;
                const exitOk = entry.command?.exitCode === 0;
                return (
                    <EntryRow onClick={() => handleEntryClick(entry)}>
                        <MethodLabel
                            sx={{
                                color: theme.palette.info.main,
                                display: 'flex',
                                alignItems: 'center',
                                justifyContent: 'center',
                            }}
                        >
                            <Icon sx={{fontSize: 14}}>terminal</Icon>
                        </MethodLabel>
                        <PathLabel>{entry.command?.input ?? 'Unknown command'}</PathLabel>
                        {duration != null && (
                            <MetaLabel sx={{color: 'primary.main'}}>{(duration * 1000).toFixed(0)}ms</MetaLabel>
                        )}
                        {memory != null && <MetaLabel sx={{color: 'success.main'}}>{formatBytes(memory)}</MetaLabel>}
                        {entry.logger?.total != null && entry.logger.total > 0 && (
                            <StatCell>
                                <Icon sx={{fontSize: 13, color: 'text.disabled'}}>description</Icon>
                                <StatLabel sx={{color: 'text.disabled'}}>{entry.logger.total}</StatLabel>
                            </StatCell>
                        )}
                        {hasN1Indicator(entry) && (
                            <Tooltip title="Duplicate operations detected (N+1)">
                                <Chip
                                    label="N+1"
                                    size="small"
                                    color="warning"
                                    sx={{
                                        fontWeight: 700,
                                        fontSize: '9px',
                                        height: 18,
                                        minWidth: 32,
                                        borderRadius: 1,
                                        flexShrink: 0,
                                    }}
                                />
                            </Tooltip>
                        )}
                        <StatusChip
                            label={exitOk ? 'OK' : `EXIT ${entry.command?.exitCode}`}
                            size="small"
                            color={exitOk ? 'success' : 'error'}
                        />
                        <Tooltip title={compact ? formatDate(entry.console.request.startTime) : ''} arrow>
                            <MetaLabel sx={{color: 'text.disabled'}}>
                                {compact
                                    ? formatTime(entry.console.request.startTime)
                                    : formatDate(entry.console.request.startTime)}
                            </MetaLabel>
                        </Tooltip>
                    </EntryRow>
                );
            }

            return (
                <EntryRow onClick={() => handleEntryClick(entry)}>
                    <PathLabel>{entry.id}</PathLabel>
                </EntryRow>
            );
        },
        [handleEntryClick, theme, compact],
    );

    const computeItemKey = useCallback((_index: number, entry: DebugEntry) => entry.id, []);

    if (isLoading) {
        return (
            <Box sx={{display: 'flex', justifyContent: 'center', py: 6}}>
                <CircularProgress size={32} />
            </Box>
        );
    }

    if (!entries || entries.length === 0) {
        return (
            <Alert severity="info">
                <AlertTitle>No debug entries found</AlertTitle>
                Make sure the debugger is enabled and your application has processed requests.
            </Alert>
        );
    }

    return (
        <Box sx={{display: 'flex', flexDirection: 'column', height: '100%'}}>
            <Box sx={{display: 'flex', alignItems: 'center', gap: 1.5, mb: 2, flexShrink: 0}}>
                <TextField
                    fullWidth
                    size="small"
                    placeholder="Search by URL, method, status, command, or ID..."
                    value={filter}
                    onChange={handleFilterChange}
                    InputProps={{
                        startAdornment: (
                            <InputAdornment position="start">
                                <Icon sx={{fontSize: 18, color: 'text.disabled'}}>search</Icon>
                            </InputAdornment>
                        ),
                        endAdornment: filter ? (
                            <InputAdornment position="end">
                                <IconButton
                                    size="small"
                                    onClick={handleFilterClear}
                                    aria-label="Clear search"
                                    sx={{p: 0.25}}
                                >
                                    <Icon sx={{fontSize: 16}}>close</Icon>
                                </IconButton>
                            </InputAdornment>
                        ) : null,
                    }}
                    sx={{'& .MuiOutlinedInput-root': {fontSize: '13px', borderRadius: 1}}}
                />
                <Typography
                    variant="body2"
                    sx={(theme) => ({
                        color: 'text.disabled',
                        flexShrink: 0,
                        fontFamily: theme.adp.fontFamilyMono,
                        fontSize: '12px',
                    })}
                >
                    {filtered.length}/{entries.length}
                </Typography>
                <Tooltip title={isFetching ? 'Refreshing...' : 'Refresh'}>
                    <IconButton size="small" onClick={refetch} disabled={isFetching} aria-label="Refresh entries">
                        <Icon sx={{fontSize: 18}}>{isFetching ? 'hourglass_empty' : 'refresh'}</Icon>
                    </IconButton>
                </Tooltip>
                <Tooltip title="Explain with AI">
                    <IconButton
                        size="small"
                        onClick={handleExplainWithAi}
                        aria-label="Explain with AI"
                        sx={{color: 'primary.main'}}
                    >
                        <AutoAwesomeIcon sx={{fontSize: 18}} />
                    </IconButton>
                </Tooltip>
            </Box>

            <Box sx={{flex: 1, minHeight: 400}}>
                <Virtuoso
                    ref={virtuosoRef}
                    data={filtered}
                    computeItemKey={computeItemKey}
                    itemContent={renderEntry}
                    overscan={200}
                    style={{height: '100%'}}
                />
            </Box>
        </Box>
    );
};
