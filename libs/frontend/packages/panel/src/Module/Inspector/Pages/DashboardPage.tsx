import {useBreadcrumbs} from '@app-dev-panel/panel/Application/Context/BreadcrumbsContext';
import {useGetLogQuery} from '@app-dev-panel/panel/Module/Inspector/API/GitApi';
import {
    type CommandType,
    useGetComposerQuery,
    useGetOpcacheQuery,
    useGetRoutesQuery,
    useGetTableQuery,
    useLazyGetCommandsQuery,
    useRunCommandMutation,
} from '@app-dev-panel/panel/Module/Inspector/API/Inspector';
import {PageHeader} from '@app-dev-panel/sdk/Component/PageHeader';
import {Box, Button, CircularProgress, Link as MuiLink, Paper, Skeleton, type Theme, Typography} from '@mui/material';
import {styled, useTheme} from '@mui/material/styles';
import {useEffect, useState} from 'react';
import {Link} from 'react-router-dom';

// ---------------------------------------------------------------------------
// Types
// ---------------------------------------------------------------------------

type HealthCardColor = 'success' | 'info' | 'warning';

type HealthCardProps = {
    label: string;
    value: string | number;
    detail: string;
    badge?: string;
    color: HealthCardColor;
    loading?: boolean;
};

type ProgressBarProps = {label: string; detail: string; percent: number; color: 'success' | 'primary' | 'warning'};

// ---------------------------------------------------------------------------
// Helpers
// ---------------------------------------------------------------------------

const formatBytes = (bytes: number): string => {
    const mb = bytes / 1024 / 1024;
    return `${Math.round(mb)} MB`;
};

const methodBgColor = (method: string, theme: Theme): string => {
    switch (method.toUpperCase()) {
        case 'GET':
            return theme.palette.success.light;
        case 'POST':
            return theme.palette.primary.light;
        case 'PUT':
        case 'PATCH':
            return theme.palette.warning.light;
        case 'DELETE':
            return theme.palette.error.light;
        default:
            return theme.palette.action.hover;
    }
};

const methodFgColor = (method: string, theme: Theme): string => {
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
            return theme.palette.text.disabled;
    }
};

// ---------------------------------------------------------------------------
// Styled components
// ---------------------------------------------------------------------------

const HealthGrid = styled(Box)(({theme}) => ({
    display: 'grid',
    gridTemplateColumns: 'repeat(4, 1fr)',
    gap: theme.spacing(1.5),
    marginBottom: theme.spacing(3.5),
}));

const HealthCardRoot = styled(Paper, {shouldForwardProp: (p) => p !== 'accentColor'})<{accentColor: HealthCardColor}>(
    ({theme, accentColor}) => ({
        padding: theme.spacing(2.5),
        position: 'relative',
        overflow: 'hidden',
        '&::after': {
            content: '""',
            position: 'absolute',
            top: 0,
            left: 0,
            right: 0,
            height: 3,
            backgroundColor: theme.palette[accentColor].main,
        },
    }),
);

const Badge = styled('span', {shouldForwardProp: (p) => p !== 'badgeColor'})<{badgeColor: HealthCardColor}>(
    ({theme, badgeColor}) => ({
        display: 'inline-flex',
        alignItems: 'center',
        fontSize: theme.typography.caption.fontSize,
        fontWeight: 600,
        padding: theme.spacing(0.25, 1),
        borderRadius: 10,
        marginTop: theme.spacing(1),
        backgroundColor: theme.palette[badgeColor].light,
        color: theme.palette[badgeColor].main,
    }),
);

const Columns = styled(Box)(({theme}) => ({
    display: 'grid',
    gridTemplateColumns: '1fr 1fr',
    gap: theme.spacing(2.5),
    marginBottom: theme.spacing(3.5),
}));

const PanelRoot = styled(Paper)({overflow: 'hidden'});

const PanelHeader = styled(Box)(({theme}) => ({
    padding: theme.spacing(1.75, 2.5),
    borderBottom: `1px solid ${theme.palette.divider}`,
    display: 'flex',
    alignItems: 'center',
    justifyContent: 'space-between',
}));

const RowBox = styled(Box)(({theme}) => ({
    display: 'flex',
    alignItems: 'center',
    padding: theme.spacing(1.25, 2.5),
    gap: theme.spacing(1.25),
    '&:not(:last-child)': {borderBottom: `1px solid ${theme.palette.action.hover}`},
}));

const MethodChip = styled('span', {shouldForwardProp: (p) => p !== 'bg' && p !== 'fg'})<{bg: string; fg: string}>(
    ({bg, fg}) => ({
        fontSize: 10,
        fontWeight: 700,
        padding: '2px 8px',
        borderRadius: 4,
        minWidth: 42,
        textAlign: 'center' as const,
        backgroundColor: bg,
        color: fg,
    }),
);

const BarTrack = styled(Box)(({theme}) => ({
    height: 8,
    backgroundColor: theme.palette.action.hover,
    borderRadius: 4,
    overflow: 'hidden',
    marginBottom: theme.spacing(1.75),
}));

const BarFill = styled(Box, {shouldForwardProp: (p) => p !== 'barColor' && p !== 'percent'})<{
    barColor: 'success' | 'primary' | 'warning';
    percent: number;
}>(({theme, barColor, percent}) => ({
    height: '100%',
    borderRadius: 4,
    width: `${percent}%`,
    backgroundColor: theme.palette[barColor].main,
    transition: 'width 0.4s ease',
}));

// ---------------------------------------------------------------------------
// Sub-components
// ---------------------------------------------------------------------------

const HealthCard = ({label, value, detail, badge, color, loading}: HealthCardProps) => (
    <HealthCardRoot variant="outlined" accentColor={color}>
        <Typography variant="caption" sx={{color: 'text.disabled', textTransform: 'uppercase', letterSpacing: 0.5}}>
            {label}
        </Typography>
        {loading ? (
            <Skeleton width={80} height={36} sx={{mt: 0.5}} />
        ) : (
            <Typography sx={{fontSize: 28, fontWeight: 700, lineHeight: 1, mt: 1}}>{value}</Typography>
        )}
        <Typography variant="body2" sx={{color: 'text.secondary', mt: 0.75}}>
            {loading ? <Skeleton width={120} /> : detail}
        </Typography>
        {badge && !loading && <Badge badgeColor={color}>{badge}</Badge>}
    </HealthCardRoot>
);

const ProgressBar = ({label, detail, percent, color}: ProgressBarProps) => (
    <Box>
        <Box sx={{display: 'flex', justifyContent: 'space-between', mb: 0.75}}>
            <Typography variant="body2" sx={{color: 'text.secondary'}}>
                {label}
            </Typography>
            <Typography variant="body2" sx={{fontWeight: 600}}>
                {detail}
            </Typography>
        </Box>
        <BarTrack>
            <BarFill barColor={color} percent={percent} />
        </BarTrack>
    </Box>
);

// ---------------------------------------------------------------------------
// Main component
// ---------------------------------------------------------------------------

export const DashboardPage = () => {
    const theme = useTheme();
    const routesQuery = useGetRoutesQuery();
    const tableQuery = useGetTableQuery();
    const opcacheQuery = useGetOpcacheQuery();
    const composerQuery = useGetComposerQuery();
    const gitLogQuery = useGetLogQuery();
    const [getCommandsQuery] = useLazyGetCommandsQuery();
    const [runCommandMutation] = useRunCommandMutation();
    const [commands, setCommands] = useState<CommandType[]>([]);
    const [testsStatus, setTestsStatus] = useState<'idle' | 'loading' | 'ok' | 'error'>('idle');
    const [testsResult, setTestsResult] = useState<{passed: number; failed: number} | null>(null);
    const [analyseStatus, setAnalyseStatus] = useState<'idle' | 'loading' | 'ok' | 'error'>('idle');
    const [analyseResult, setAnalyseResult] = useState<{errors: number; info: number} | null>(null);
    const [runningCommands, setRunningCommands] = useState<Record<string, 'loading' | 'ok' | 'error'>>({});

    useEffect(() => {
        void (async () => {
            const response = await getCommandsQuery();
            if (response.data) {
                setCommands(response.data);
            }
        })();
    }, []);

    const runTests = async () => {
        setTestsStatus('loading');
        setTestsResult(null);
        const data = await runCommandMutation('test/codeception');
        if ('data' in data && data.data) {
            const results = data.data.result ?? [];
            const passed = Array.isArray(results) ? results.filter((r: any) => r.status === 'ok').length : 0;
            const failed = Array.isArray(results) ? results.filter((r: any) => r.status !== 'ok').length : 0;
            setTestsResult({passed, failed});
            setTestsStatus(data.data.status === 'ok' ? 'ok' : 'error');
        } else {
            setTestsStatus('error');
        }
    };

    const runAnalyse = async () => {
        setAnalyseStatus('loading');
        setAnalyseResult(null);
        const data = await runCommandMutation('analyse/psalm');
        if ('data' in data && data.data) {
            const results = data.data.result ?? [];
            const errors = Array.isArray(results) ? results.filter((r: any) => r.type === 'error').length : 0;
            const info = Array.isArray(results) ? results.filter((r: any) => r.type !== 'error').length : 0;
            setAnalyseResult({errors, info});
            setAnalyseStatus(data.data.status === 'ok' ? 'ok' : 'error');
        } else {
            setAnalyseStatus('error');
        }
    };

    const runCommand = async (commandName: string) => {
        setRunningCommands((prev) => ({...prev, [commandName]: 'loading'}));
        const data = await runCommandMutation(commandName);
        if ('data' in data && data.data) {
            setRunningCommands((prev) => ({...prev, [commandName]: data.data!.status === 'ok' ? 'ok' : 'error'}));
        } else {
            setRunningCommands((prev) => ({...prev, [commandName]: 'error'}));
        }
    };

    useBreadcrumbs(() => ['Inspector', 'Dashboard']);

    // --- Derived data ---

    const routes: {method: string; pattern: string; name: string}[] = Array.isArray(routesQuery.data)
        ? (routesQuery.data as any[])
              .slice(0, 6)
              .map((r: any) => ({method: r.method ?? 'GET', pattern: r.pattern ?? r.path ?? '', name: r.name ?? ''}))
        : [];
    const totalRoutes = Array.isArray(routesQuery.data) ? routesQuery.data.length : 0;

    const tables: {name: string; columns: number; records: number}[] = Array.isArray(tableQuery.data)
        ? (tableQuery.data as any[])
              .slice(0, 6)
              .map((t: any) => ({
                  name: t.table ?? t.name ?? '',
                  columns: Array.isArray(t.columns) ? t.columns.length : (t.columns ?? 0),
                  records: t.records ?? t.totalCount ?? 0,
              }))
        : [];
    const totalTables = Array.isArray(tableQuery.data) ? tableQuery.data.length : 0;
    const totalRecords = tables.reduce((sum, t) => sum + t.records, 0);

    const opcache = opcacheQuery.data;
    const opcacheEnabled = opcache?.status?.opcache_enabled ?? false;
    const memUsed = opcache?.status?.memory_usage?.used_memory ?? 0;
    const memFree = opcache?.status?.memory_usage?.free_memory ?? 0;
    const memTotal = memUsed + memFree;
    const memPercent = memTotal > 0 ? Math.round((memUsed / memTotal) * 100) : 0;
    const internedUsed = opcache?.status?.interned_strings_usage?.used_memory ?? 0;
    const internedTotal = opcache?.status?.interned_strings_usage?.buffer_size ?? 0;
    const internedPercent = internedTotal > 0 ? Math.round((internedUsed / internedTotal) * 100) : 0;
    const cachedKeys = opcache?.status?.opcache_statistics?.num_cached_keys ?? 0;
    const maxKeys = opcache?.status?.opcache_statistics?.max_cached_keys ?? 0;
    const keysPercent = maxKeys > 0 ? Math.round((cachedKeys / maxKeys) * 100) : 0;
    const hitRate = opcache?.status?.opcache_statistics?.opcache_hit_rate ?? 0;
    const jitEnabled = opcache?.status?.jit?.enabled ?? false;

    const phpVersion = opcache?.configuration?.version?.version ?? '';

    const composerData = composerQuery.data;
    const allPackages = [...(composerData?.lock?.packages ?? []), ...(composerData?.lock?.['packages-dev'] ?? [])];
    const keyPackages = allPackages.slice(0, 8);
    const halfIndex = Math.ceil(keyPackages.length / 2);
    const leftPackages = keyPackages.slice(0, halfIndex);
    const rightPackages = keyPackages.slice(halfIndex);

    const commits = (gitLogQuery.data?.commits ?? []).slice(0, 5);

    // --- Route method breakdown ---
    const allRoutes: any[] = Array.isArray(routesQuery.data) ? (routesQuery.data as any[]) : [];
    const getCount = allRoutes.filter((r) => (r.method ?? '').toUpperCase() === 'GET').length;
    const postCount = allRoutes.filter((r) => (r.method ?? '').toUpperCase() === 'POST').length;
    const otherCount = totalRoutes - getCount - postCount;

    return (
        <>
            <PageHeader title="Inspector" icon="search" description="Application health and runtime overview" />

            {/* Health cards */}
            <HealthGrid>
                <HealthCard
                    label="PHP"
                    value={phpVersion || '...'}
                    detail={
                        opcacheEnabled ? `OPcache ${opcacheEnabled ? 'enabled' : 'disabled'}` : 'Loading environment...'
                    }
                    badge={opcacheEnabled ? 'Healthy' : undefined}
                    color="success"
                    loading={opcacheQuery.isLoading}
                />
                <HealthCard
                    label="OPcache Memory"
                    value={`${memPercent}%`}
                    detail={`${formatBytes(memUsed)} / ${formatBytes(memTotal)} used`}
                    badge={jitEnabled ? 'JIT enabled' : undefined}
                    color={memPercent > 90 ? 'warning' : 'success'}
                    loading={opcacheQuery.isLoading}
                />
                <HealthCard
                    label="Routes"
                    value={totalRoutes}
                    detail={`GET: ${getCount} \u00b7 POST: ${postCount} \u00b7 Other: ${otherCount}`}
                    color="info"
                    loading={routesQuery.isLoading}
                />
                <HealthCard
                    label="Database"
                    value={totalTables}
                    detail={`tables \u00b7 ${totalRecords.toLocaleString()} total rows`}
                    color="info"
                    loading={tableQuery.isLoading}
                />
            </HealthGrid>

            {/* Routes + Database */}
            <Columns>
                <PanelRoot variant="outlined">
                    <PanelHeader>
                        <Typography variant="body2" sx={{fontWeight: 600}}>
                            Routes {totalRoutes > 0 && `(top ${Math.min(6, totalRoutes)})`}
                        </Typography>
                        <MuiLink component={Link} to="/inspector/routes" underline="hover" variant="body2">
                            View all {totalRoutes > 0 && totalRoutes} &rarr;
                        </MuiLink>
                    </PanelHeader>
                    {routesQuery.isLoading ? (
                        <Box sx={{p: 2.5}}>
                            {[...Array(4)].map((_, i) => (
                                <Skeleton key={i} height={28} sx={{mb: 0.5}} />
                            ))}
                        </Box>
                    ) : (
                        routes.map((route, i) => (
                            <RowBox key={i}>
                                <MethodChip
                                    bg={methodBgColor(route.method, theme)}
                                    fg={methodFgColor(route.method, theme)}
                                >
                                    {route.method.toUpperCase()}
                                </MethodChip>
                                <Typography
                                    variant="body2"
                                    sx={{fontFamily: 'monospace', color: 'text.secondary', flex: 1}}
                                >
                                    {route.pattern}
                                </Typography>
                                <Typography variant="caption" sx={{color: 'text.disabled'}}>
                                    {route.name}
                                </Typography>
                            </RowBox>
                        ))
                    )}
                </PanelRoot>

                <PanelRoot variant="outlined">
                    <PanelHeader>
                        <Typography variant="body2" sx={{fontWeight: 600}}>
                            Database Tables
                        </Typography>
                        <MuiLink component={Link} to="/inspector/database" underline="hover" variant="body2">
                            View all &rarr;
                        </MuiLink>
                    </PanelHeader>
                    {tableQuery.isLoading ? (
                        <Box sx={{p: 2.5}}>
                            {[...Array(4)].map((_, i) => (
                                <Skeleton key={i} height={28} sx={{mb: 0.5}} />
                            ))}
                        </Box>
                    ) : (
                        tables.map((table, i) => (
                            <RowBox key={i}>
                                <Typography variant="body2" sx={{fontFamily: 'monospace', fontWeight: 500, flex: 1}}>
                                    {table.name}
                                </Typography>
                                <Typography variant="caption" sx={{color: 'text.disabled'}}>
                                    {table.columns} cols
                                </Typography>
                                <Typography variant="body2" sx={{fontWeight: 600, color: 'text.secondary'}}>
                                    {table.records.toLocaleString()} records
                                </Typography>
                            </RowBox>
                        ))
                    )}
                </PanelRoot>
            </Columns>

            {/* Git + OPcache */}
            <Columns>
                <PanelRoot variant="outlined">
                    <PanelHeader>
                        <Typography variant="body2" sx={{fontWeight: 600}}>
                            Recent Commits
                        </Typography>
                        <MuiLink component={Link} to="/inspector/git/log" underline="hover" variant="body2">
                            View log &rarr;
                        </MuiLink>
                    </PanelHeader>
                    {gitLogQuery.isLoading ? (
                        <Box sx={{p: 2.5}}>
                            {[...Array(4)].map((_, i) => (
                                <Skeleton key={i} height={28} sx={{mb: 0.5}} />
                            ))}
                        </Box>
                    ) : (
                        commits.map((commit, i) => (
                            <RowBox key={i}>
                                <Typography
                                    variant="body2"
                                    sx={{
                                        fontFamily: 'monospace',
                                        color: 'primary.main',
                                        fontWeight: 500,
                                        flexShrink: 0,
                                    }}
                                >
                                    {commit.sha.substring(0, 7)}
                                </Typography>
                                <Typography
                                    variant="body2"
                                    sx={{
                                        color: 'text.secondary',
                                        flex: 1,
                                        whiteSpace: 'nowrap',
                                        overflow: 'hidden',
                                        textOverflow: 'ellipsis',
                                    }}
                                >
                                    {commit.message}
                                </Typography>
                            </RowBox>
                        ))
                    )}
                </PanelRoot>

                <PanelRoot variant="outlined">
                    <PanelHeader>
                        <Typography variant="body2" sx={{fontWeight: 600}}>
                            OPcache Status
                        </Typography>
                    </PanelHeader>
                    {opcacheQuery.isLoading ? (
                        <Box sx={{p: 2.5}}>
                            {[...Array(4)].map((_, i) => (
                                <Skeleton key={i} height={28} sx={{mb: 1}} />
                            ))}
                        </Box>
                    ) : (
                        <Box sx={{p: 2.5}}>
                            <ProgressBar
                                label="Memory"
                                detail={`${memPercent}% (${formatBytes(memUsed)} / ${formatBytes(memTotal)})`}
                                percent={memPercent}
                                color={memPercent > 90 ? 'warning' : 'success'}
                            />
                            <ProgressBar
                                label="Interned Strings"
                                detail={`${internedPercent}% (${formatBytes(internedUsed)} / ${formatBytes(internedTotal)})`}
                                percent={internedPercent}
                                color="primary"
                            />
                            <ProgressBar
                                label="Keys"
                                detail={`${keysPercent}% (${cachedKeys.toLocaleString()} / ${maxKeys.toLocaleString()})`}
                                percent={keysPercent}
                                color="success"
                            />
                            <ProgressBar
                                label="Hit Rate"
                                detail={`${hitRate.toFixed(1)}%`}
                                percent={hitRate}
                                color="success"
                            />
                        </Box>
                    )}
                </PanelRoot>
            </Columns>

            {/* Commands + Tests & Analyse */}
            <Columns>
                <PanelRoot variant="outlined">
                    <PanelHeader>
                        <Typography variant="body2" sx={{fontWeight: 600}}>
                            Commands
                        </Typography>
                        <MuiLink component={Link} to="/inspector/commands" underline="hover" variant="body2">
                            View all {commands.length > 0 && commands.length} &rarr;
                        </MuiLink>
                    </PanelHeader>
                    {commands.length === 0 ? (
                        <Box sx={{p: 2.5}}>
                            <Typography variant="body2" sx={{color: 'text.disabled'}}>
                                No commands available
                            </Typography>
                        </Box>
                    ) : (
                        commands.slice(0, 8).map((cmd) => (
                            <RowBox key={cmd.name}>
                                <Typography variant="caption" sx={{color: 'text.disabled', flexShrink: 0}}>
                                    {cmd.group}
                                </Typography>
                                <Typography variant="body2" sx={{flex: 1, fontWeight: 500}}>
                                    {cmd.title}
                                </Typography>
                                <Button
                                    variant="outlined"
                                    size="small"
                                    onClick={() => runCommand(cmd.name)}
                                    disabled={runningCommands[cmd.name] === 'loading'}
                                    color={
                                        runningCommands[cmd.name] === 'ok'
                                            ? 'success'
                                            : runningCommands[cmd.name] === 'error'
                                              ? 'error'
                                              : 'primary'
                                    }
                                    sx={{minWidth: 'auto', px: 1.5, py: 0.25, fontSize: 11}}
                                    endIcon={
                                        runningCommands[cmd.name] === 'loading' ? (
                                            <CircularProgress size={12} color="inherit" />
                                        ) : null
                                    }
                                >
                                    Run
                                </Button>
                            </RowBox>
                        ))
                    )}
                </PanelRoot>

                <Box sx={{display: 'flex', flexDirection: 'column', gap: 2.5}}>
                    <PanelRoot variant="outlined">
                        <PanelHeader>
                            <Typography variant="body2" sx={{fontWeight: 600}}>
                                Tests
                            </Typography>
                            <MuiLink component={Link} to="/inspector/tests" underline="hover" variant="body2">
                                Details &rarr;
                            </MuiLink>
                        </PanelHeader>
                        <Box sx={{p: 2.5, display: 'flex', alignItems: 'center', gap: 2}}>
                            <Button
                                variant="outlined"
                                size="small"
                                onClick={runTests}
                                disabled={testsStatus === 'loading'}
                                color={testsStatus === 'ok' ? 'success' : testsStatus === 'error' ? 'error' : 'primary'}
                                endIcon={
                                    testsStatus === 'loading' ? <CircularProgress size={16} color="inherit" /> : null
                                }
                            >
                                Run Codeception
                            </Button>
                            {testsResult && (
                                <Typography variant="body2" sx={{color: 'text.secondary'}}>
                                    <Typography
                                        component="span"
                                        variant="body2"
                                        sx={{color: 'success.main', fontWeight: 600}}
                                    >
                                        {testsResult.passed} passed
                                    </Typography>
                                    {testsResult.failed > 0 && (
                                        <>
                                            {' \u00b7 '}
                                            <Typography
                                                component="span"
                                                variant="body2"
                                                sx={{color: 'error.main', fontWeight: 600}}
                                            >
                                                {testsResult.failed} failed
                                            </Typography>
                                        </>
                                    )}
                                </Typography>
                            )}
                        </Box>
                    </PanelRoot>

                    <PanelRoot variant="outlined">
                        <PanelHeader>
                            <Typography variant="body2" sx={{fontWeight: 600}}>
                                Analyse
                            </Typography>
                            <MuiLink component={Link} to="/inspector/analyse" underline="hover" variant="body2">
                                Details &rarr;
                            </MuiLink>
                        </PanelHeader>
                        <Box sx={{p: 2.5, display: 'flex', alignItems: 'center', gap: 2}}>
                            <Button
                                variant="outlined"
                                size="small"
                                onClick={runAnalyse}
                                disabled={analyseStatus === 'loading'}
                                color={
                                    analyseStatus === 'ok' ? 'success' : analyseStatus === 'error' ? 'error' : 'primary'
                                }
                                endIcon={
                                    analyseStatus === 'loading' ? <CircularProgress size={16} color="inherit" /> : null
                                }
                            >
                                Run Psalm
                            </Button>
                            {analyseResult && (
                                <Typography variant="body2" sx={{color: 'text.secondary'}}>
                                    {analyseResult.errors > 0 ? (
                                        <Typography
                                            component="span"
                                            variant="body2"
                                            sx={{color: 'error.main', fontWeight: 600}}
                                        >
                                            {analyseResult.errors} errors
                                        </Typography>
                                    ) : (
                                        <Typography
                                            component="span"
                                            variant="body2"
                                            sx={{color: 'success.main', fontWeight: 600}}
                                        >
                                            No errors
                                        </Typography>
                                    )}
                                    {analyseResult.info > 0 && (
                                        <>
                                            {' \u00b7 '}
                                            <Typography component="span" variant="body2" sx={{color: 'text.disabled'}}>
                                                {analyseResult.info} info
                                            </Typography>
                                        </>
                                    )}
                                </Typography>
                            )}
                        </Box>
                    </PanelRoot>
                </Box>
            </Columns>

            {/* Composer packages */}
            <PanelRoot variant="outlined" sx={{mb: 3.5}}>
                <PanelHeader>
                    <Typography variant="body2" sx={{fontWeight: 600}}>
                        Key Packages
                    </Typography>
                    <MuiLink component={Link} to="/inspector/composer" underline="hover" variant="body2">
                        View all {allPackages.length > 0 && allPackages.length} &rarr;
                    </MuiLink>
                </PanelHeader>
                {composerQuery.isLoading ? (
                    <Box sx={{p: 2.5}}>
                        {[...Array(4)].map((_, i) => (
                            <Skeleton key={i} height={24} sx={{mb: 0.5}} />
                        ))}
                    </Box>
                ) : (
                    <Box sx={{display: 'grid', gridTemplateColumns: '1fr 1fr'}}>
                        <Box>
                            {leftPackages.map((pkg) => (
                                <RowBox key={pkg.name} sx={{py: 1}}>
                                    <Typography variant="body2" sx={{fontFamily: 'monospace', flex: 1}}>
                                        {pkg.name}
                                    </Typography>
                                    <Typography variant="body2" sx={{color: 'text.disabled'}}>
                                        {pkg.version}
                                    </Typography>
                                </RowBox>
                            ))}
                        </Box>
                        <Box>
                            {rightPackages.map((pkg) => (
                                <RowBox key={pkg.name} sx={{py: 1}}>
                                    <Typography variant="body2" sx={{fontFamily: 'monospace', flex: 1}}>
                                        {pkg.name}
                                    </Typography>
                                    <Typography variant="body2" sx={{color: 'text.disabled'}}>
                                        {pkg.version}
                                    </Typography>
                                </RowBox>
                            ))}
                        </Box>
                    </Box>
                )}
            </PanelRoot>
        </>
    );
};
