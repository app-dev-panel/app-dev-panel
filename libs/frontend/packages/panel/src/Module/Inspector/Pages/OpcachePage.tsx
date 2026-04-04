import {useGetOpcacheQuery} from '@app-dev-panel/panel/Module/Inspector/API/Inspector';
import {EmptyState} from '@app-dev-panel/sdk/Component/EmptyState';
import {FullScreenCircularProgress} from '@app-dev-panel/sdk/Component/FullScreenCircularProgress';
import {JsonRenderer} from '@app-dev-panel/sdk/Component/JsonRenderer';
import {formatBytes} from '@app-dev-panel/sdk/Helper/formatBytes';
import {Box, Chip, Tab, Tabs, Typography} from '@mui/material';
import {styled} from '@mui/material/styles';
import {useMemo, useState} from 'react';

// ---------------------------------------------------------------------------
// Types
// ---------------------------------------------------------------------------

type MemoryUsage = {used_memory: number; free_memory: number; wasted_memory: number; current_wasted_percentage: number};

type InternedStringsUsage = {buffer_size: number; used_memory: number; free_memory: number; number_of_strings: number};

type OpcacheStatistics = {
    num_cached_scripts: number;
    num_cached_keys: number;
    max_cached_keys: number;
    hits: number;
    misses: number;
    opcache_hit_rate: number;
    start_time: number;
    last_restart_time: number;
    oom_restarts: number;
    hash_restarts: number;
    manual_restarts: number;
    blacklist_misses: number;
    blacklist_miss_ratio: number;
    [key: string]: unknown;
};

type JitStatus = {
    enabled: boolean;
    on: boolean;
    kind: number;
    opt_level: number;
    opt_flags: number;
    buffer_size: number;
    buffer_free: number;
    [key: string]: unknown;
};

// ---------------------------------------------------------------------------
// Styled components
// ---------------------------------------------------------------------------

const StyledTabs = styled(Tabs)(({theme}) => ({
    minHeight: 36,
    '& .MuiTab-root': {
        minHeight: 36,
        fontSize: '12px',
        fontWeight: 600,
        textTransform: 'none',
        padding: theme.spacing(0.5, 2),
    },
}));

const Card = styled(Box)(({theme}) => ({
    border: `1px solid ${theme.palette.divider}`,
    borderRadius: theme.shape.borderRadius,
    overflow: 'hidden',
    marginBottom: theme.spacing(1.5),
}));

const CardTitle = styled(Box)(({theme}) => ({
    display: 'flex',
    alignItems: 'center',
    gap: theme.spacing(1),
    padding: theme.spacing(1.5, 2),
    backgroundColor: theme.palette.action.hover,
    borderBottom: `1px solid ${theme.palette.divider}`,
}));

const MetricRow = styled(Box)(({theme}) => ({
    display: 'flex',
    alignItems: 'center',
    gap: theme.spacing(2),
    padding: theme.spacing(0.75, 2),
    borderBottom: `1px solid ${theme.palette.divider}`,
    '&:last-child': {borderBottom: 'none'},
}));

const MetricKey = styled(Typography)(({theme}) => ({
    fontFamily: theme.adp.fontFamilyMono,
    fontSize: '12px',
    fontWeight: 600,
    width: 220,
    flexShrink: 0,
}));

const MetricValue = styled(Box)(({theme}) => ({fontFamily: theme.adp.fontFamilyMono, fontSize: '12px', flex: 1}));

const ProgressBar = styled(Box)(({theme}) => ({
    height: 6,
    borderRadius: 3,
    backgroundColor: theme.palette.action.selected,
    overflow: 'hidden',
    flex: 1,
    maxWidth: 200,
}));

const ProgressFill = styled(Box)({height: '100%', borderRadius: 3, transition: 'width 0.3s ease'});

const ScriptRow = styled(Box)(({theme}) => ({
    display: 'flex',
    alignItems: 'center',
    gap: theme.spacing(2),
    padding: theme.spacing(0.75, 2),
    borderBottom: `1px solid ${theme.palette.divider}`,
    '&:last-child': {borderBottom: 'none'},
    '&:hover': {backgroundColor: theme.palette.action.hover},
}));

const ScriptPath = styled(Typography)(({theme}) => ({
    fontFamily: theme.adp.fontFamilyMono,
    fontSize: '11px',
    flex: 1,
    overflow: 'hidden',
    textOverflow: 'ellipsis',
    whiteSpace: 'nowrap',
}));

// ---------------------------------------------------------------------------
// Helpers
// ---------------------------------------------------------------------------

function formatPercent(value: number): string {
    return `${value.toFixed(1)}%`;
}

function formatNumber(value: number): string {
    return value.toLocaleString();
}

// ---------------------------------------------------------------------------
// Sub-components
// ---------------------------------------------------------------------------

const MetricCard = ({
    title,
    icon: _icon,
    metrics,
}: {
    title: string;
    icon: string;
    metrics: Array<{key: string; value: React.ReactNode}>;
}) => (
    <Card>
        <CardTitle>
            <Typography sx={{fontWeight: 600, fontSize: '13px'}}>{title}</Typography>
        </CardTitle>
        {metrics.map((m) => (
            <MetricRow key={m.key}>
                <MetricKey>{m.key}</MetricKey>
                <MetricValue>{m.value}</MetricValue>
            </MetricRow>
        ))}
    </Card>
);

const MemoryBar = ({used, total, color}: {used: number; total: number; color: string}) => {
    const percent = total > 0 ? (used / total) * 100 : 0;
    return (
        <Box sx={{display: 'flex', alignItems: 'center', gap: 1.5}}>
            <Typography sx={(theme) => ({fontFamily: theme.adp.fontFamilyMono, fontSize: '12px'})}>
                {formatBytes(used)} / {formatBytes(total)}
            </Typography>
            <ProgressBar>
                <ProgressFill sx={{width: `${percent}%`, backgroundColor: color}} />
            </ProgressBar>
            <Typography
                sx={(theme) => ({
                    fontFamily: theme.adp.fontFamilyMono,
                    fontSize: '11px',
                    color: 'text.disabled',
                    width: 50,
                    textAlign: 'right',
                })}
            >
                {formatPercent(percent)}
            </Typography>
        </Box>
    );
};

const StatusTab = ({status}: {status: Record<string, unknown>}) => {
    const memory = status.memory_usage as MemoryUsage | undefined;
    const strings = status.interned_strings_usage as InternedStringsUsage | undefined;
    const stats = status.opcache_statistics as OpcacheStatistics | undefined;

    return (
        <Box sx={{p: 2}}>
            <MetricCard
                title="Status"
                icon="info"
                metrics={[
                    {
                        key: 'opcache_enabled',
                        value: (
                            <Chip
                                label={status.opcache_enabled ? 'Enabled' : 'Disabled'}
                                size="small"
                                color={status.opcache_enabled ? 'success' : 'default'}
                                sx={{fontSize: '10px', height: 20, borderRadius: 1}}
                            />
                        ),
                    },
                    {
                        key: 'cache_full',
                        value: (
                            <Chip
                                label={status.cache_full ? 'Yes' : 'No'}
                                size="small"
                                color={status.cache_full ? 'error' : 'default'}
                                sx={{fontSize: '10px', height: 20, borderRadius: 1}}
                            />
                        ),
                    },
                    {key: 'restart_pending', value: String(status.restart_pending ?? false)},
                    {key: 'restart_in_progress', value: String(status.restart_in_progress ?? false)},
                ]}
            />

            {memory && (
                <MetricCard
                    title="Memory Usage"
                    icon="memory"
                    metrics={[
                        {
                            key: 'used_memory',
                            value: (
                                <MemoryBar
                                    used={memory.used_memory}
                                    total={memory.used_memory + memory.free_memory}
                                    color="primary.main"
                                />
                            ),
                        },
                        {key: 'free_memory', value: formatBytes(memory.free_memory)},
                        {key: 'wasted_memory', value: formatBytes(memory.wasted_memory)},
                        {key: 'wasted_percentage', value: formatPercent(memory.current_wasted_percentage)},
                    ]}
                />
            )}

            {strings && (
                <MetricCard
                    title="Interned Strings"
                    icon="text_fields"
                    metrics={[
                        {key: 'buffer_size', value: formatBytes(strings.buffer_size)},
                        {
                            key: 'used_memory',
                            value: (
                                <MemoryBar
                                    used={strings.used_memory}
                                    total={strings.buffer_size}
                                    color="success.main"
                                />
                            ),
                        },
                        {key: 'number_of_strings', value: formatNumber(strings.number_of_strings)},
                    ]}
                />
            )}

            {stats && (
                <MetricCard
                    title="Statistics"
                    icon="bar_chart"
                    metrics={[
                        {key: 'cached_scripts', value: formatNumber(stats.num_cached_scripts)},
                        {
                            key: 'cached_keys',
                            value: `${formatNumber(stats.num_cached_keys)} / ${formatNumber(stats.max_cached_keys)}`,
                        },
                        {key: 'hits', value: formatNumber(stats.hits)},
                        {key: 'misses', value: formatNumber(stats.misses)},
                        {
                            key: 'hit_rate',
                            value: (
                                <Box sx={{display: 'flex', alignItems: 'center', gap: 1}}>
                                    <Typography
                                        sx={(theme) => ({fontFamily: theme.adp.fontFamilyMono, fontSize: '12px'})}
                                    >
                                        {formatPercent(stats.opcache_hit_rate)}
                                    </Typography>
                                    <Chip
                                        label={
                                            stats.opcache_hit_rate > 95
                                                ? 'Excellent'
                                                : stats.opcache_hit_rate > 80
                                                  ? 'Good'
                                                  : 'Low'
                                        }
                                        size="small"
                                        color={
                                            stats.opcache_hit_rate > 95
                                                ? 'success'
                                                : stats.opcache_hit_rate > 80
                                                  ? 'primary'
                                                  : 'warning'
                                        }
                                        sx={{fontSize: '10px', height: 20, borderRadius: 1}}
                                    />
                                </Box>
                            ),
                        },
                        {key: 'oom_restarts', value: formatNumber(stats.oom_restarts)},
                        {key: 'hash_restarts', value: formatNumber(stats.hash_restarts)},
                        {key: 'manual_restarts', value: formatNumber(stats.manual_restarts)},
                    ]}
                />
            )}
        </Box>
    );
};

const JitTab = ({jit}: {jit: JitStatus | null}) => {
    if (!jit) return <EmptyState icon="speed" title="No JIT data available" />;

    return (
        <Box sx={{p: 2}}>
            <MetricCard
                title="JIT Configuration"
                icon="speed"
                metrics={[
                    {
                        key: 'enabled',
                        value: (
                            <Chip
                                label={jit.enabled ? 'Enabled' : 'Disabled'}
                                size="small"
                                color={jit.enabled ? 'success' : 'default'}
                                sx={{fontSize: '10px', height: 20, borderRadius: 1}}
                            />
                        ),
                    },
                    {
                        key: 'on',
                        value: (
                            <Chip
                                label={jit.on ? 'Active' : 'Inactive'}
                                size="small"
                                color={jit.on ? 'success' : 'default'}
                                sx={{fontSize: '10px', height: 20, borderRadius: 1}}
                            />
                        ),
                    },
                    {key: 'kind', value: String(jit.kind)},
                    {key: 'opt_level', value: String(jit.opt_level)},
                    {key: 'opt_flags', value: String(jit.opt_flags)},
                    {key: 'buffer_size', value: formatBytes(jit.buffer_size)},
                    {
                        key: 'buffer_free',
                        value: (
                            <MemoryBar
                                used={jit.buffer_size - jit.buffer_free}
                                total={jit.buffer_size}
                                color="primary.main"
                            />
                        ),
                    },
                ]}
            />
        </Box>
    );
};

const ScriptsTab = ({scripts}: {scripts: Record<string, unknown> | null}) => {
    const entries = useMemo(() => {
        if (!scripts || typeof scripts !== 'object') return [];
        return Object.entries(scripts);
    }, [scripts]);

    if (entries.length === 0) return <EmptyState icon="description" title="No cached scripts" />;

    return (
        <Box sx={{p: 2}}>
            <Box sx={{display: 'flex', alignItems: 'center', gap: 1, mb: 1.5}}>
                <Typography sx={{fontSize: '12px', color: 'text.disabled'}}>{entries.length} cached scripts</Typography>
            </Box>
            <Card>
                {entries.map(([path, info]) => (
                    <ScriptRow key={path}>
                        <ScriptPath title={path}>{path}</ScriptPath>
                        {info && typeof info === 'object' && 'hits' in info && (
                            <Typography
                                sx={(theme) => ({
                                    fontFamily: theme.adp.fontFamilyMono,
                                    fontSize: '10px',
                                    color: 'text.disabled',
                                    flexShrink: 0,
                                })}
                            >
                                {(info as {hits: number}).hits} hits
                            </Typography>
                        )}
                        {info && typeof info === 'object' && 'memory_consumption' in info && (
                            <Typography
                                sx={(theme) => ({
                                    fontFamily: theme.adp.fontFamilyMono,
                                    fontSize: '10px',
                                    color: 'success.main',
                                    flexShrink: 0,
                                })}
                            >
                                {formatBytes((info as {memory_consumption: number}).memory_consumption)}
                            </Typography>
                        )}
                    </ScriptRow>
                ))}
            </Card>
        </Box>
    );
};

const ConfigurationTab = ({configuration}: {configuration: Record<string, unknown> | null}) => {
    if (!configuration) return <EmptyState icon="settings" title="No configuration data available" />;

    const directives = (configuration.directives ?? configuration) as Record<string, unknown>;
    const entries = Object.entries(directives);

    if (entries.length === 0) return <EmptyState icon="settings" title="No configuration directives found" />;

    return (
        <Box sx={{p: 2}}>
            <Card>
                <CardTitle>
                    <Typography sx={{fontWeight: 600, fontSize: '13px'}}>Directives</Typography>
                    <Chip
                        label={`${entries.length}`}
                        size="small"
                        sx={{fontSize: '10px', height: 20, borderRadius: 1, backgroundColor: 'action.selected'}}
                    />
                </CardTitle>
                {entries.map(([key, value]) => (
                    <MetricRow key={key}>
                        <MetricKey>{key}</MetricKey>
                        <MetricValue>
                            {typeof value === 'boolean' ? (
                                <Chip
                                    label={value ? 'true' : 'false'}
                                    size="small"
                                    color={value ? 'success' : 'default'}
                                    sx={{fontSize: '10px', height: 20, borderRadius: 1}}
                                />
                            ) : typeof value === 'object' ? (
                                <JsonRenderer value={value} depth={2} />
                            ) : (
                                String(value)
                            )}
                        </MetricValue>
                    </MetricRow>
                ))}
            </Card>
        </Box>
    );
};

// ---------------------------------------------------------------------------
// Main component
// ---------------------------------------------------------------------------

export const OpcachePage = () => {
    const {data, isLoading} = useGetOpcacheQuery();
    const [tab, setTab] = useState(0);

    const {status, jit, scripts, configuration} = useMemo(() => {
        if (!data) return {status: null, jit: null, scripts: null, configuration: null};
        const {jit, scripts, ...rest} = data.status ?? {};
        return {
            status: rest,
            jit: jit as JitStatus | null,
            scripts: scripts as Record<string, unknown> | null,
            configuration: data.configuration as Record<string, unknown> | null,
        };
    }, [data]);

    if (isLoading) return <FullScreenCircularProgress />;

    if (!data)
        return (
            <EmptyState
                icon="speed"
                title="No Opcache data available"
                description="Opcache may not be enabled on this server"
            />
        );

    return (
        <Box>
            <Box sx={{borderBottom: 1, borderColor: 'divider'}}>
                <StyledTabs value={tab} onChange={(_, v) => setTab(v)}>
                    <Tab label="Status" />
                    <Tab label="JIT" />
                    <Tab label="Scripts" />
                    <Tab label="Configuration" />
                </StyledTabs>
            </Box>
            {tab === 0 && status && <StatusTab status={status} />}
            {tab === 1 && <JitTab jit={jit} />}
            {tab === 2 && <ScriptsTab scripts={scripts} />}
            {tab === 3 && <ConfigurationTab configuration={configuration} />}
        </Box>
    );
};
