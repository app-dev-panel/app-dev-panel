import {changeEntryAction} from '@app-dev-panel/sdk/API/Debug/Context';
import {DebugEntry, useGetDebugQuery} from '@app-dev-panel/sdk/API/Debug/Debug';
import {primitives} from '@app-dev-panel/sdk/Component/Theme/tokens';
import {getEntrySearchText, isDebugEntryAboutConsole, isDebugEntryAboutWeb} from '@app-dev-panel/sdk/Helper/debugEntry';
import {formatBytes} from '@app-dev-panel/sdk/Helper/formatBytes';
import {formatDate} from '@app-dev-panel/sdk/Helper/formatDate';
import {searchVariants} from '@app-dev-panel/sdk/Helper/layoutTranslit';
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
import React, {useCallback, useDeferredValue, useMemo, useState} from 'react';
import {useDispatch} from 'react-redux';
import {useNavigate} from 'react-router-dom';

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
}));

const MethodLabel = styled(Typography)({
    fontFamily: primitives.fontFamilyMono,
    fontSize: '11px',
    fontWeight: 600,
    minWidth: 44,
    flexShrink: 0,
});

const PathLabel = styled(Typography)({
    fontFamily: primitives.fontFamilyMono,
    fontSize: '13px',
    flex: 1,
    overflow: 'hidden',
    textOverflow: 'ellipsis',
    whiteSpace: 'nowrap',
});

const MetaLabel = styled(Typography)({fontFamily: primitives.fontFamilyMono, fontSize: '10px', flexShrink: 0});

const StatusChip = styled(Chip)({fontSize: '10px', height: 20, minWidth: 36, fontWeight: 600, borderRadius: 4});

const StatCell = styled(Box)(({theme}) => ({
    display: 'flex',
    alignItems: 'center',
    gap: theme.spacing(0.5),
    flexShrink: 0,
}));

const StatLabel = styled(Typography)({fontFamily: primitives.fontFamilyMono, fontSize: '10px'});

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
    const {data: entries, isLoading, isFetching, refetch} = useGetDebugQuery();
    const dispatch = useDispatch();
    const navigate = useNavigate();
    const [filter, setFilter] = useState('');
    const deferredFilter = useDeferredValue(filter);
    const handleFilterChange = useCallback((e: React.ChangeEvent<HTMLInputElement>) => setFilter(e.target.value), []);
    const handleFilterClear = useCallback(() => setFilter(''), []);

    const handleEntryClick = useCallback(
        (entry: DebugEntry) => {
            dispatch(changeEntryAction(entry));
            navigate(`/debug?debugEntry=${entry.id}`);
        },
        [dispatch, navigate],
    );

    const filtered = useMemo(() => {
        if (!entries) return [];
        if (!deferredFilter.trim()) return entries;
        return entries.filter((entry) => matchesFilter(entry, deferredFilter));
    }, [entries, deferredFilter]);

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
        <Box>
            <Box sx={{display: 'flex', alignItems: 'center', gap: 1.5, mb: 2}}>
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
                    sx={{
                        color: 'text.disabled',
                        flexShrink: 0,
                        fontFamily: primitives.fontFamilyMono,
                        fontSize: '12px',
                    }}
                >
                    {filtered.length}/{entries.length}
                </Typography>
                <Tooltip title={isFetching ? 'Refreshing...' : 'Refresh'}>
                    <IconButton size="small" onClick={refetch} disabled={isFetching} aria-label="Refresh entries">
                        <Icon sx={{fontSize: 18}}>{isFetching ? 'hourglass_empty' : 'refresh'}</Icon>
                    </IconButton>
                </Tooltip>
            </Box>

            {filtered.map((entry) => {
                if (isDebugEntryAboutWeb(entry)) {
                    const duration = entry.web?.request?.processingTime;
                    const memory = entry.web?.memory?.peakUsage;
                    return (
                        <EntryRow key={entry.id} onClick={() => handleEntryClick(entry)}>
                            <MethodLabel sx={{color: methodColor(entry.request.method, theme)}}>
                                {entry.request.method}
                            </MethodLabel>
                            <PathLabel>{entry.request.path}</PathLabel>
                            {duration != null && (
                                <MetaLabel sx={{color: 'primary.main'}}>{(duration * 1000).toFixed(0)}ms</MetaLabel>
                            )}
                            {memory != null && (
                                <MetaLabel sx={{color: 'success.main'}}>{formatBytes(memory)}</MetaLabel>
                            )}
                            {entry.logger?.total != null && entry.logger.total > 0 && (
                                <StatCell>
                                    <Icon sx={{fontSize: 13, color: 'text.disabled'}}>description</Icon>
                                    <StatLabel sx={{color: 'text.disabled'}}>{entry.logger.total}</StatLabel>
                                </StatCell>
                            )}
                            {entry.db?.queries?.total != null && entry.db.queries.total > 0 && (
                                <StatCell>
                                    <Icon
                                        sx={{
                                            fontSize: 13,
                                            color: entry.db.queries.error ? 'error.main' : 'text.disabled',
                                        }}
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
                            <MetaLabel sx={{color: 'text.disabled', minWidth: 55}}>
                                {formatDate(entry.web.request.startTime)}
                            </MetaLabel>
                        </EntryRow>
                    );
                }

                if (isDebugEntryAboutConsole(entry)) {
                    const duration = entry.console?.request?.processingTime;
                    const memory = entry.console?.memory?.peakUsage;
                    const exitOk = entry.command?.exitCode === 0;
                    return (
                        <EntryRow key={entry.id} onClick={() => handleEntryClick(entry)}>
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
                            {memory != null && (
                                <MetaLabel sx={{color: 'success.main'}}>{formatBytes(memory)}</MetaLabel>
                            )}
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
                            <MetaLabel sx={{color: 'text.disabled', minWidth: 55}}>
                                {formatDate(entry.console.request.startTime)}
                            </MetaLabel>
                        </EntryRow>
                    );
                }

                return (
                    <EntryRow key={entry.id} onClick={() => handleEntryClick(entry)}>
                        <PathLabel>{entry.id}</PathLabel>
                    </EntryRow>
                );
            })}
        </Box>
    );
};
