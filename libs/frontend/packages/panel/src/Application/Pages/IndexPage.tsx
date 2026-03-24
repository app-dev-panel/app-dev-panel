import {useLazyGetGeneratorsQuery} from '@app-dev-panel/panel/Module/GenCode/API/GenCode';
import {useLazyGetParametersQuery} from '@app-dev-panel/panel/Module/Inspector/API/Inspector';
import {useSelector} from '@app-dev-panel/panel/store';
import {addFavoriteUrl, changeBaseUrl, removeFavoriteUrl} from '@app-dev-panel/sdk/API/Application/ApplicationContext';
import {changeEntryAction} from '@app-dev-panel/sdk/API/Debug/Context';
import {DebugEntry, useGetDebugQuery} from '@app-dev-panel/sdk/API/Debug/Debug';
import {FilterInput} from '@app-dev-panel/sdk/Component/FilterInput';
import {PageHeader} from '@app-dev-panel/sdk/Component/PageHeader';
import {StatusCard} from '@app-dev-panel/sdk/Component/StatusCard';
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
    InputBase,
    Tooltip,
    Typography,
    type Theme,
} from '@mui/material';
import {styled, useTheme} from '@mui/material/styles';
import {useCallback, useEffect, useMemo, useState} from 'react';
import {useDispatch} from 'react-redux';
import {useNavigate} from 'react-router-dom';

// ---------------------------------------------------------------------------
// Styled components
// ---------------------------------------------------------------------------

const StatusGrid = styled('div')(({theme}) => ({
    display: 'grid',
    gridTemplateColumns: 'repeat(3, 1fr)',
    gap: theme.spacing(2),
    marginBottom: theme.spacing(3),
}));

const UrlForm = styled('form')(({theme}) => ({
    display: 'flex',
    alignItems: 'center',
    gap: theme.spacing(1),
    padding: theme.spacing(1, 2),
    borderRadius: theme.shape.borderRadius * 1.5,
    border: `1px solid ${theme.palette.divider}`,
    backgroundColor: theme.palette.background.paper,
    marginBottom: theme.spacing(3),
    transition: 'border-color 0.2s',
    '&:focus-within': {borderColor: theme.palette.primary.main},
}));

const FavoritesRow = styled('div')(({theme}) => ({
    display: 'flex',
    flexWrap: 'wrap',
    gap: theme.spacing(0.75),
    marginBottom: theme.spacing(3),
}));

const CurrentUrl = styled('div')(({theme}) => ({
    display: 'flex',
    alignItems: 'center',
    gap: theme.spacing(1.5),
    padding: theme.spacing(1.5, 2),
    borderRadius: theme.shape.borderRadius * 1.5,
    backgroundColor: theme.palette.action.hover,
    marginBottom: theme.spacing(3),
    fontFamily: primitives.fontFamilyMono,
    fontSize: '13px',
}));

const SectionLabel = styled(Typography)(({theme}) => ({
    fontSize: '12px',
    fontWeight: 600,
    letterSpacing: '0.6px',
    textTransform: 'uppercase',
    color: theme.palette.text.disabled,
    marginBottom: theme.spacing(1.5),
    paddingBottom: theme.spacing(0.75),
    borderBottom: `1px solid ${theme.palette.divider}`,
}));

// --- Debug list styled components ---

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

// ---------------------------------------------------------------------------
// Component
// ---------------------------------------------------------------------------

export function IndexPage() {
    const defaultBackendUrl = useSelector((state) => state.application.baseUrl) as string;
    const dispatch = useDispatch();
    const theme = useTheme();
    const navigate = useNavigate();
    const [inspectorQuery] = useLazyGetParametersQuery();
    const [genCodeQuery] = useLazyGetGeneratorsQuery();
    const baseUrl = useSelector((state) => state.application.baseUrl);
    const [url, setUrl] = useState<string>(String(baseUrl));
    const [status, setStatus] = useState<Record<string, 'connected' | 'disconnected' | 'loading'>>({
        debug: 'loading',
        inspector: 'loading',
        genCode: 'loading',
    });
    const favoriteUrls = useSelector((state) => state.application.favoriteUrls) as string[];

    // Debug list state — also drives debug status card (avoids race with Layout's resetApiState)
    const {
        data: entries,
        isLoading: debugLoading,
        isFetching: debugFetching,
        isSuccess: debugSuccess,
        isError: debugError,
        refetch: debugRefetch,
    } = useGetDebugQuery();
    const [filter, setFilter] = useState('');

    // Derive debug status from the shared useGetDebugQuery hook (managed by Layout)
    const debugStatus: 'connected' | 'disconnected' | 'loading' = debugFetching
        ? 'loading'
        : debugSuccess
          ? 'connected'
          : debugError
            ? 'disconnected'
            : 'loading';

    async function checkStatus() {
        setStatus((s) => ({...s, inspector: 'loading', genCode: 'loading'}));
        inspectorQuery()
            .then((response) =>
                setStatus((s) => ({...s, inspector: response.isSuccess ? 'connected' : 'disconnected'})),
            )
            .catch(() => setStatus((s) => ({...s, inspector: 'disconnected'})));
        genCodeQuery()
            .then((response) => setStatus((s) => ({...s, genCode: response.isSuccess ? 'connected' : 'disconnected'})))
            .catch(() => setStatus((s) => ({...s, genCode: 'disconnected'})));
    }

    const handleChangeUrl = async (newUrl: string) => {
        setUrl(newUrl);
        dispatch(changeBaseUrl(newUrl));
    };

    const onSubmitHandler = async (event: {preventDefault: () => void}) => {
        event.preventDefault();
        await handleChangeUrl(url);
    };

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

    useEffect(() => {
        checkStatus();
    }, [baseUrl]);

    return (
        <>
            <PageHeader
                title="Application Development Panel"
                icon="dashboard"
                description="Monitor and manage your application backend services"
            />

            <CurrentUrl>
                <Icon sx={{fontSize: 18, color: 'text.disabled'}}>link</Icon>
                <span style={{flex: 1}}>{String(defaultBackendUrl)}</span>
                <IconButton
                    size="small"
                    onClick={() => {
                        checkStatus();
                        debugRefetch();
                    }}
                >
                    <Icon sx={{fontSize: 16}}>refresh</Icon>
                </IconButton>
            </CurrentUrl>

            <SectionLabel>API Status</SectionLabel>
            <StatusGrid>
                <StatusCard title="Debug" icon="bug_report" status={debugStatus} onClick={() => handleChangeUrl(url)} />
                <StatusCard
                    title="Inspector"
                    icon="search"
                    status={status.inspector}
                    onClick={() => handleChangeUrl(url)}
                />
                <StatusCard title="GenCode" icon="build_circle" status={status.genCode} onClick={() => handleChangeUrl(url)} />
            </StatusGrid>

            <SectionLabel>Backend URL</SectionLabel>
            <UrlForm onSubmit={onSubmitHandler}>
                <Icon sx={{fontSize: 18, color: 'text.disabled'}}>language</Icon>
                <InputBase
                    sx={{flex: 1, fontSize: '13px', fontFamily: primitives.fontFamilyMono}}
                    placeholder="http://localhost:8080"
                    value={url}
                    onChange={(event) => setUrl(event.target.value)}
                />
                <IconButton size="small" onClick={() => dispatch(addFavoriteUrl(url))}>
                    <Icon sx={{fontSize: 18}}>star_outline</Icon>
                </IconButton>
                <IconButton size="small" type="submit">
                    <Icon sx={{fontSize: 18, color: 'primary.main'}}>check</Icon>
                </IconButton>
            </UrlForm>

            {favoriteUrls.length > 0 && (
                <>
                    <SectionLabel>Favorites</SectionLabel>
                    <FavoritesRow>
                        {favoriteUrls.map((favUrl, index) => (
                            <Chip
                                key={index}
                                icon={<Icon sx={{fontSize: '14px !important', color: 'warning.main'}}>star</Icon>}
                                label={favUrl}
                                size="small"
                                variant={favUrl === String(baseUrl) ? 'filled' : 'outlined'}
                                onClick={() => handleChangeUrl(favUrl)}
                                onDelete={() => dispatch(removeFavoriteUrl(favUrl))}
                                deleteIcon={<Icon sx={{fontSize: '14px !important'}}>close</Icon>}
                                sx={{
                                    fontFamily: primitives.fontFamilyMono,
                                    fontSize: '12px',
                                    height: 28,
                                    borderRadius: 1,
                                    ...(favUrl === String(baseUrl) && {
                                        backgroundColor: 'primary.light',
                                        borderColor: 'primary.main',
                                        color: 'primary.main',
                                        fontWeight: 600,
                                    }),
                                }}
                            />
                        ))}
                    </FavoritesRow>
                </>
            )}

            <SectionLabel>Debug Entries</SectionLabel>
            {debugLoading ? (
                <Box sx={{display: 'flex', justifyContent: 'center', py: 4}}>
                    <CircularProgress size={24} />
                </Box>
            ) : !entries || entries.length === 0 ? (
                <Alert severity="info" sx={{mb: 2}}>
                    <AlertTitle>No debug entries</AlertTitle>
                    Make sure the debugger is enabled and your application has processed requests.
                </Alert>
            ) : (
                <Box>
                    <Box sx={{display: 'flex', alignItems: 'center', gap: 1, mb: 1}}>
                        <FilterInput value={filter} onChange={setFilter} placeholder="Filter entries..." />
                        <Typography sx={{fontSize: '11px', color: 'text.disabled', ml: 0.5}}>
                            {filtered.length} / {entries.length}
                        </Typography>
                        <Box sx={{flex: 1}} />
                        <Tooltip title={debugFetching ? 'Refreshing...' : 'Refresh'}>
                            <IconButton size="small" onClick={() => debugRefetch()} disabled={debugFetching}>
                                <Icon sx={{fontSize: 18}}>{debugFetching ? 'hourglass_empty' : 'refresh'}</Icon>
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
                                        <MetaLabel sx={{color: 'primary.main'}}>
                                            {(duration * 1000).toFixed(0)}ms
                                        </MetaLabel>
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
                                            <StatLabel
                                                sx={{color: entry.db.queries.error ? 'error.main' : 'text.disabled'}}
                                            >
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
                                    <Icon sx={{fontSize: 16, color: 'text.disabled', flexShrink: 0}}>terminal</Icon>
                                    <PathLabel>{entry.command?.input ?? 'Unknown command'}</PathLabel>
                                    {duration != null && (
                                        <MetaLabel sx={{color: 'primary.main'}}>
                                            {(duration * 1000).toFixed(0)}ms
                                        </MetaLabel>
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
            )}
        </>
    );
}
