import {changeEntryAction} from '@app-dev-panel/sdk/API/Debug/Context';
import {DebugEntry, useGetDebugQuery} from '@app-dev-panel/sdk/API/Debug/Debug';
import {SectionTitle} from '@app-dev-panel/sdk/Component/SectionTitle';
import {primitives} from '@app-dev-panel/sdk/Component/Theme/tokens';
import {isDebugEntryAboutConsole, isDebugEntryAboutWeb} from '@app-dev-panel/sdk/Helper/debugEntry';
import {formatBytes} from '@app-dev-panel/sdk/Helper/formatBytes';
import {formatDate} from '@app-dev-panel/sdk/Helper/formatDate';
import {
    Alert,
    AlertTitle,
    Box,
    Chip,
    CircularProgress,
    Icon,
    IconButton,
    TextField,
    Tooltip,
    Typography,
} from '@mui/material';
import {styled} from '@mui/material/styles';
import {useCallback, useMemo, useState} from 'react';
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

const methodColor = (method: string): string => {
    switch (method.toUpperCase()) {
        case 'GET':
            return primitives.green600;
        case 'POST':
            return primitives.blue500;
        case 'PUT':
        case 'PATCH':
            return primitives.amber600;
        case 'DELETE':
            return primitives.red600;
        default:
            return primitives.gray600;
    }
};

const statusColor = (status: number): string => {
    if (status >= 500) return primitives.red600;
    if (status >= 400) return primitives.amber600;
    return primitives.green600;
};

const statusBg = (status: number): string => {
    if (status >= 500) return primitives.red50;
    if (status >= 400) return primitives.blue50;
    return primitives.blue50;
};

// ---------------------------------------------------------------------------
// Component
// ---------------------------------------------------------------------------

export const ListPage = () => {
    const {data: entries, isLoading, isFetching, refetch} = useGetDebugQuery();
    const dispatch = useDispatch();
    const navigate = useNavigate();
    const [filter, setFilter] = useState('');

    const handleEntryClick = useCallback(
        (entry: DebugEntry) => {
            dispatch(changeEntryAction(entry));
            navigate(`/debug?debugEntry=${entry.id}`);
        },
        [dispatch, navigate],
    );

    const filtered = useMemo(() => {
        if (!entries) return [];
        if (!filter.trim()) return entries;
        const q = filter.toLowerCase();
        return entries.filter((entry) => {
            const path = entry.request?.path ?? entry.command?.input ?? '';
            const method = entry.request?.method ?? '';
            return path.toLowerCase().includes(q) || method.toLowerCase().includes(q) || entry.id.includes(q);
        });
    }, [entries, filter]);

    if (isLoading) {
        return (
            <Box sx={{display: 'flex', justifyContent: 'center', py: 6}}>
                <CircularProgress size={32} />
            </Box>
        );
    }

    if (!entries || entries.length === 0) {
        return (
            <Box m={2}>
                <Alert severity="info">
                    <AlertTitle>No debug entries found</AlertTitle>
                    Make sure the debugger is enabled and your application has processed requests.
                </Alert>
            </Box>
        );
    }

    return (
        <Box>
            <Box sx={{display: 'flex', alignItems: 'center', gap: 2, mb: 2}}>
                <SectionTitle>{`${filtered.length} debug entries`}</SectionTitle>
                <TextField
                    size="small"
                    placeholder="Filter by URL, method, or ID..."
                    value={filter}
                    onChange={(e) => setFilter(e.target.value)}
                    InputProps={{sx: {fontSize: '13px'}}}
                    sx={{ml: 'auto', width: 260}}
                />
                <Tooltip title={isFetching ? 'Refreshing...' : 'Refresh'}>
                    <IconButton size="small" onClick={() => refetch()} disabled={isFetching}>
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
                            <MethodLabel sx={{color: methodColor(entry.request.method)}}>
                                {entry.request.method}
                            </MethodLabel>
                            <PathLabel>{entry.request.path}</PathLabel>
                            {duration != null && (
                                <MetaLabel sx={{color: primitives.blue500}}>{(duration * 1000).toFixed(0)}ms</MetaLabel>
                            )}
                            {memory != null && (
                                <MetaLabel sx={{color: primitives.green600}}>{formatBytes(memory)}</MetaLabel>
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
                            {entry.exception && (
                                <Tooltip title={entry.exception.message}>
                                    <Icon sx={{fontSize: 15, color: 'error.main'}}>error</Icon>
                                </Tooltip>
                            )}
                            <StatusChip
                                label={entry.response.statusCode}
                                size="small"
                                sx={{
                                    color: statusColor(entry.response.statusCode),
                                    backgroundColor: statusBg(entry.response.statusCode),
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
                            <Icon sx={{fontSize: 16, color: 'text.disabled', flexShrink: 0}}>terminal</Icon>
                            <PathLabel>{entry.command?.input ?? 'Unknown command'}</PathLabel>
                            {duration != null && (
                                <MetaLabel sx={{color: primitives.blue500}}>{(duration * 1000).toFixed(0)}ms</MetaLabel>
                            )}
                            {memory != null && (
                                <MetaLabel sx={{color: primitives.green600}}>{formatBytes(memory)}</MetaLabel>
                            )}
                            {entry.logger?.total != null && entry.logger.total > 0 && (
                                <StatCell>
                                    <Icon sx={{fontSize: 13, color: 'text.disabled'}}>description</Icon>
                                    <StatLabel sx={{color: 'text.disabled'}}>{entry.logger.total}</StatLabel>
                                </StatCell>
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
