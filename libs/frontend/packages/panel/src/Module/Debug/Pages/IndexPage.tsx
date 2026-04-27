import {useSelector} from '@app-dev-panel/panel/store';
import {useDebugEntry} from '@app-dev-panel/sdk/API/Debug/Context';
import {DebugEntry, useLazyGetCollectorInfoQuery} from '@app-dev-panel/sdk/API/Debug/Debug';
import {
    compareCollectorWeight,
    getCollectorIcon,
    getCollectorLabel,
    hiddenOverviewCollectors,
} from '@app-dev-panel/sdk/Helper/collectorMeta';
import {CollectorsMap} from '@app-dev-panel/sdk/Helper/collectors';
import {getCollectedCountByCollector} from '@app-dev-panel/sdk/Helper/collectorsTotal';
import {isDebugEntryAboutConsole, isDebugEntryAboutWeb} from '@app-dev-panel/sdk/Helper/debugEntry';
import {formatBytes} from '@app-dev-panel/sdk/Helper/formatBytes';
import {formatMillisecondsAsDuration} from '@app-dev-panel/sdk/Helper/formatDate';
import {Box, Icon, LinearProgress, Typography} from '@mui/material';
import {styled, useTheme} from '@mui/material/styles';
import {useEffect, useState} from 'react';
import {useSearchParams} from 'react-router';

// ---------------------------------------------------------------------------
// Card icon background/foreground colors per collector (from theme tokens)
// ---------------------------------------------------------------------------
type CollectorColorKey =
    keyof typeof import('@app-dev-panel/sdk/Component/Theme/tokens').semanticTokens.collectorColors;

const collectorColorMap: Record<string, CollectorColorKey> = {
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
    [CollectorsMap.CacheCollector]: 'cache',
    [CollectorsMap.TemplateCollector]: 'template',
    [CollectorsMap.AuthorizationCollector]: 'authorization',
    [CollectorsMap.DeprecationCollector]: 'deprecation',
    [CollectorsMap.EnvironmentCollector]: 'environment',
    [CollectorsMap.TranslatorCollector]: 'translator',
};

// ---------------------------------------------------------------------------
// Summary bar (top request headline)
// ---------------------------------------------------------------------------

const SummaryBar = styled(Box)(({theme}) => ({
    display: 'flex',
    flexWrap: 'wrap',
    gap: theme.spacing(3),
    marginBottom: theme.spacing(2),
    padding: theme.spacing(1.5, 2),
    borderRadius: Number(theme.shape.borderRadius) * 1.5,
    border: `1px solid ${theme.palette.divider}`,
    backgroundColor: theme.palette.background.paper,
    cursor: 'pointer',
    transition: 'border-color 0.15s',
    '&:hover': {borderColor: theme.palette.primary.main},
}));

const SummaryItem = styled(Box)({display: 'flex', alignItems: 'center', gap: 8});

const SummaryValue = styled(Typography)(({theme}) => ({
    fontFamily: theme.adp.fontFamilyMono,
    fontWeight: 600,
    fontSize: '14px',
}));

const SummaryLabel = styled(Typography)(({theme}) => ({fontSize: '11px', color: theme.palette.text.secondary}));

// ---------------------------------------------------------------------------
// Environment strip
// ---------------------------------------------------------------------------

const EnvStrip = styled(Box)(({theme}) => ({
    display: 'flex',
    flexWrap: 'wrap',
    alignItems: 'center',
    gap: theme.spacing(0.75),
    marginBottom: theme.spacing(2),
    padding: theme.spacing(1, 1.5),
    borderRadius: theme.shape.borderRadius,
    border: `1px solid ${theme.palette.divider}`,
    backgroundColor: theme.palette.background.default,
    cursor: 'pointer',
    transition: 'border-color 0.15s',
    '&:hover': {borderColor: theme.palette.primary.main},
}));

const EnvChip = styled(Box)(({theme}) => ({
    display: 'inline-flex',
    alignItems: 'center',
    gap: 4,
    padding: theme.spacing(0.25, 1),
    borderRadius: Number(theme.shape.borderRadius) * 0.75,
    backgroundColor: theme.palette.background.paper,
    border: `1px solid ${theme.palette.divider}`,
    fontSize: '11px',
    fontWeight: 500,
    color: theme.palette.text.secondary,
    whiteSpace: 'nowrap',
}));

const EnvChipValue = styled('span')(({theme}) => ({fontFamily: theme.adp.fontFamilyMono, fontWeight: 600}));

// ---------------------------------------------------------------------------
// Performance metrics (compact)
// ---------------------------------------------------------------------------

const MetricsRow = styled(Box)(({theme}) => ({
    display: 'grid',
    gridTemplateColumns: 'repeat(auto-fill, minmax(140px, 1fr))',
    gap: theme.spacing(1),
    marginBottom: theme.spacing(2.5),
}));

const MetricCard = styled(Box)(({theme}) => ({
    padding: theme.spacing(1, 1.5),
    borderRadius: theme.shape.borderRadius,
    border: `1px solid ${theme.palette.divider}`,
    backgroundColor: theme.palette.background.paper,
}));

const MetricLabel = styled(Typography)(({theme}) => ({
    fontSize: '10px',
    fontWeight: 600,
    textTransform: 'uppercase' as const,
    letterSpacing: '0.5px',
    color: theme.palette.text.disabled,
    lineHeight: 1.2,
}));

const MetricValue = styled(Typography)(({theme}) => ({
    fontFamily: theme.adp.fontFamilyMono,
    fontWeight: 600,
    fontSize: '14px',
}));

const MetricBar = styled(Box)(({theme}) => ({
    height: 3,
    borderRadius: 1.5,
    backgroundColor: theme.palette.action.hover,
    marginTop: theme.spacing(0.5),
    overflow: 'hidden',
}));

type WebAppInfoData = {
    applicationProcessingTime?: number;
    requestProcessingTime?: number;
    applicationEmit?: number;
    preloadTime?: number;
    memoryPeakUsage?: number;
    memoryUsage?: number;
};

const formatTime = (seconds: number) => {
    if (seconds === 0) return '0 ms';
    if (seconds > 1000 || seconds < 0) return 'N/A';
    if (seconds < 0.001) return `${(seconds * 1000000).toFixed(0)} us`;
    if (seconds < 1) return `${(seconds * 1000).toFixed(2)} ms`;
    return `${seconds.toFixed(3)} s`;
};

const PerformanceSection = ({data}: {data: WebAppInfoData}) => {
    const theme = useTheme();
    const chart = theme.adp.chartColors;
    const totalTime = data.applicationProcessingTime || 0;
    const requestTime = data.requestProcessingTime || 0;
    const emitTime = data.applicationEmit || 0;
    const preloadTime = data.preloadTime || 0;
    const memPeak = data.memoryPeakUsage || 0;
    const memUsage = data.memoryUsage || 0;

    const validTimes = [totalTime, requestTime, preloadTime, emitTime].filter((t) => t > 0 && t <= 1000);
    const maxTime = validTimes.length > 0 ? Math.max(...validTimes) : 0.001;
    const safeRatio = (value: number, max: number) => (value > 0 && value <= 1000 ? value / max : 0);

    const items = [
        {
            label: 'Total',
            value: formatTime(totalTime),
            ratio: safeRatio(totalTime, maxTime),
            color: theme.palette.primary.main,
        },
        {label: 'Request', value: formatTime(requestTime), ratio: safeRatio(requestTime, maxTime), color: chart[0]},
        {label: 'Preload', value: formatTime(preloadTime), ratio: safeRatio(preloadTime, maxTime), color: chart[1]},
        {label: 'Emit', value: formatTime(emitTime), ratio: safeRatio(emitTime, maxTime), color: chart[2]},
        {label: 'Peak Mem', value: formatBytes(memPeak), ratio: memPeak > 0 ? 1 : 0, color: chart[3]},
        {
            label: 'Mem Usage',
            value: formatBytes(memUsage),
            ratio: memPeak > 0 ? memUsage / memPeak : 0,
            color: chart[4],
        },
    ];

    return (
        <MetricsRow>
            {items.map((item) => (
                <MetricCard key={item.label}>
                    <MetricLabel>{item.label}</MetricLabel>
                    <MetricValue sx={{color: item.color}}>{item.value}</MetricValue>
                    <MetricBar>
                        <Box
                            sx={{
                                height: '100%',
                                width: `${Math.max(2, item.ratio * 100)}%`,
                                backgroundColor: item.color,
                                borderRadius: 1.5,
                                transition: 'width 0.3s ease',
                            }}
                        />
                    </MetricBar>
                </MetricCard>
            ))}
        </MetricsRow>
    );
};

// ---------------------------------------------------------------------------
// Section divider
// ---------------------------------------------------------------------------

const SectionDivider = styled(Box)(({theme}) => ({
    display: 'flex',
    alignItems: 'center',
    gap: theme.spacing(1.5),
    marginBottom: theme.spacing(1.5),
}));

const DividerLine = styled(Box)(({theme}) => ({flex: 1, height: 1, backgroundColor: theme.palette.divider}));

const DividerLabel = styled(Typography)(({theme}) => ({
    fontSize: '11px',
    fontWeight: 600,
    textTransform: 'uppercase' as const,
    letterSpacing: '0.6px',
    color: theme.palette.text.disabled,
    whiteSpace: 'nowrap',
}));

// ---------------------------------------------------------------------------
// Active collector cards
// ---------------------------------------------------------------------------

const ActiveGrid = styled(Box)(({theme}) => ({
    display: 'grid',
    gridTemplateColumns: 'repeat(auto-fill, minmax(200px, 1fr))',
    gap: theme.spacing(1.5),
    marginBottom: theme.spacing(2),
}));

type CollectorCardRootProps = {hasError?: boolean};

const ActiveCardRoot = styled(Box, {shouldForwardProp: (p) => p !== 'hasError'})<CollectorCardRootProps>(
    ({theme, hasError}) => ({
        background: theme.palette.background.paper,
        border: `1px solid ${theme.palette.divider}`,
        borderRadius: Number(theme.shape.borderRadius) * 1.5,
        padding: theme.spacing(2),
        cursor: 'pointer',
        transition: 'all 0.2s',
        boxShadow: '0 1px 3px rgba(0,0,0,0.06)',
        ...(hasError && {borderLeft: `3px solid ${theme.palette.error.main}`}),
        '&:hover': {
            boxShadow: '0 4px 12px rgba(0,0,0,0.08)',
            borderColor: theme.palette.primary.main,
            transform: 'translateY(-1px)',
        },
    }),
);

const CardHeader = styled(Box)({display: 'flex', alignItems: 'center', justifyContent: 'space-between'});

const CardTitle = styled(Box)(({theme}) => ({display: 'flex', alignItems: 'center', gap: theme.spacing(0.75)}));

const CardIconBox = styled(Box)({
    width: 24,
    height: 24,
    borderRadius: 6,
    display: 'flex',
    alignItems: 'center',
    justifyContent: 'center',
});

const CardName = styled(Typography)({fontWeight: 600, fontSize: '13px'});

type BadgeProps = {isError?: boolean};

const Badge = styled('span', {shouldForwardProp: (p) => p !== 'isError'})<BadgeProps>(({theme, isError}) => ({
    padding: '1px 7px',
    borderRadius: 10,
    fontSize: '11px',
    fontWeight: 600,
    backgroundColor: isError ? theme.palette.error.light : theme.palette.background.default,
    color: isError ? theme.palette.error.main : theme.palette.text.secondary,
}));

const SparklineContainer = styled(Box)({display: 'flex', alignItems: 'flex-end', gap: 2, height: 14, marginTop: 8});

type SparkBarProps = {isCurrent?: boolean; barColor?: string};

const SparkBar = styled(Box, {shouldForwardProp: (p) => p !== 'isCurrent' && p !== 'barColor'})<SparkBarProps>(
    ({theme, isCurrent, barColor}) => ({
        width: 5,
        borderRadius: '2px 2px 0 0',
        backgroundColor: barColor || theme.palette.primary.main,
        opacity: isCurrent ? 1 : 0.35,
        transition: 'opacity 0.15s',
    }),
);

// ---------------------------------------------------------------------------
// Compact (empty/info) collector cards
// ---------------------------------------------------------------------------

const CompactGrid = styled(Box)(({theme}) => ({
    display: 'grid',
    gridTemplateColumns: 'repeat(auto-fill, minmax(130px, 1fr))',
    gap: theme.spacing(1),
}));

const CompactCard = styled(Box)(({theme}) => ({
    display: 'flex',
    alignItems: 'center',
    gap: theme.spacing(0.75),
    padding: theme.spacing(0.75, 1.25),
    borderRadius: theme.shape.borderRadius,
    border: `1px solid ${theme.palette.divider}`,
    backgroundColor: theme.palette.background.paper,
    cursor: 'pointer',
    transition: 'all 0.15s',
    opacity: 0.7,
    '&:hover': {opacity: 1, borderColor: theme.palette.primary.main},
}));

const CompactIconBox = styled(Box)({
    width: 20,
    height: 20,
    borderRadius: 5,
    display: 'flex',
    alignItems: 'center',
    justifyContent: 'center',
    flexShrink: 0,
});

// ---------------------------------------------------------------------------
// Helpers
// ---------------------------------------------------------------------------

function generateSparkBars(count: number): number[] {
    if (count <= 0) return [5, 5, 5, 5, 5, 5, 5, 5];
    const bars: number[] = [];
    for (let i = 0; i < 8; i++) {
        bars.push(Math.max(10, Math.min(100, Math.round(15 + Math.sin(i * 0.8 + count) * 40 + count * 3))));
    }
    return bars;
}

type CollectorCardData = {
    key: string;
    icon: string;
    label: string;
    badge: number | undefined;
    summary: string;
    isException: boolean;
    iconBg: string;
    iconFg: string;
};

function buildCollectorCards(
    entry: DebugEntry,
    collectorColors: typeof import('@app-dev-panel/sdk/Component/Theme/tokens').semanticTokens.collectorColors,
): CollectorCardData[] {
    return [...entry.collectors]
        .map((c) => (typeof c === 'string' ? c : c.id))
        .filter((c) => !hiddenOverviewCollectors.has(c))
        .sort(compareCollectorWeight)
        .map((collector) => {
            const count = getCollectedCountByCollector(collector as CollectorsMap, entry);
            const isException = collector === CollectorsMap.ExceptionCollector && !!count && count > 0;
            const colorKey = collectorColorMap[collector] ?? 'default';
            const colors = collectorColors[colorKey];
            const label = getCollectorLabel(collector);
            let summary = count != null ? `${count} ${label.toLowerCase()}` : label;

            if (collector === CollectorsMap.DatabaseCollector && entry.db) {
                summary = `${Number(entry.db.queries?.total ?? 0)} queries`;
            }
            if (collector === CollectorsMap.ExceptionCollector && isException) {
                summary = `${count} exception`;
            }

            return {
                key: collector,
                icon: getCollectorIcon(collector),
                label,
                badge: count,
                summary,
                isException,
                iconBg: colors.bg,
                iconFg: colors.fg,
            };
        });
}

// ---------------------------------------------------------------------------
// IndexPage
// ---------------------------------------------------------------------------

export const IndexPage = () => {
    const theme = useTheme();
    const entry = useDebugEntry();
    const [, setSearchParams] = useSearchParams();
    const [getCollectorInfo] = useLazyGetCollectorInfoQuery();
    const [webAppInfo, setWebAppInfo] = useState<WebAppInfoData | null>(null);
    const [loadingPerf, setLoadingPerf] = useState(false);
    const showInactiveCollectors = useSelector((state) => state.application.showInactiveCollectors);

    useEffect(() => {
        if (!entry) return;
        if (!entry.collectors.some((c) => (typeof c === 'string' ? c : c.id) === CollectorsMap.WebAppInfoCollector))
            return;

        setLoadingPerf(true);
        getCollectorInfo({id: entry.id, collector: CollectorsMap.WebAppInfoCollector})
            .then(({data}) => {
                if (data) setWebAppInfo(data as WebAppInfoData);
            })
            .catch(() => {})
            .finally(() => setLoadingPerf(false));
    }, [entry?.id]);

    if (!entry) {
        return (
            <Box sx={{textAlign: 'center', py: 6, color: 'text.disabled'}}>
                <Typography variant="body1">No debug entry selected</Typography>
            </Box>
        );
    }

    const cards = buildCollectorCards(entry, theme.adp.collectorColors);
    const activeCards = cards.filter((c) => c.badge != null && c.badge > 0);
    // Mirrors sidebar: always show badge-less collectors (Router, Authorization);
    // include zero-count ones only when the user opted in via the inactive-collectors toggle.
    const emptyCards = cards.filter((c) => c.badge == null || (showInactiveCollectors && c.badge === 0));

    const handleCardClick = (collectorKey: string) => {
        setSearchParams((params) => {
            params.set('collector', collectorKey);
            params.set('debugEntry', entry.id);
            return params;
        });
    };

    // Summary metrics
    const duration =
        isDebugEntryAboutWeb(entry) && entry.web?.request?.processingTime
            ? formatMillisecondsAsDuration(entry.web.request.processingTime)
            : isDebugEntryAboutConsole(entry) && entry.console?.request?.processingTime
              ? formatMillisecondsAsDuration(entry.console.request.processingTime)
              : null;

    const memory =
        isDebugEntryAboutWeb(entry) && entry.web?.memory?.peakUsage
            ? formatBytes(entry.web.memory.peakUsage)
            : isDebugEntryAboutConsole(entry) && entry.console?.memory?.peakUsage
              ? formatBytes(entry.console.memory.peakUsage)
              : null;

    const phpVersion = entry.environment?.php?.version;
    const phpSapi = entry.environment?.php?.sapi;
    const osName = entry.environment?.os;
    const adapter = isDebugEntryAboutWeb(entry) ? entry.web?.adapter : entry.console?.adapter;
    const status = isDebugEntryAboutWeb(entry) ? entry.response?.statusCode : null;
    const method = isDebugEntryAboutWeb(entry) ? entry.request?.method : null;
    const path = isDebugEntryAboutWeb(entry) ? entry.request?.path : null;

    const hasEnvironment = entry.collectors.some(
        (c) => (typeof c === 'string' ? c : c.id) === CollectorsMap.EnvironmentCollector,
    );

    return (
        <Box>
            {/* Request summary — click opens Request or Command panel */}
            <SummaryBar
                onClick={() => {
                    handleCardClick(CollectorsMap.EntryCollector);
                }}
            >
                {method && path && (
                    <SummaryItem>
                        <Icon sx={{fontSize: 16, color: 'primary.main'}}>http</Icon>
                        <Box>
                            <SummaryValue>
                                {method} {path}
                            </SummaryValue>
                            <SummaryLabel>Request</SummaryLabel>
                        </Box>
                    </SummaryItem>
                )}
                {status != null && (
                    <SummaryItem>
                        <Icon sx={{fontSize: 16, color: status >= 400 ? 'error.main' : 'success.main'}}>
                            {status >= 400 ? 'error' : 'check_circle'}
                        </Icon>
                        <Box>
                            <SummaryValue>{status}</SummaryValue>
                            <SummaryLabel>Status</SummaryLabel>
                        </Box>
                    </SummaryItem>
                )}
                {duration && (
                    <SummaryItem>
                        <Icon sx={{fontSize: 16, color: 'primary.main'}}>timer</Icon>
                        <Box>
                            <SummaryValue>{duration}</SummaryValue>
                            <SummaryLabel>Duration</SummaryLabel>
                        </Box>
                    </SummaryItem>
                )}
                {memory && (
                    <SummaryItem>
                        <Icon sx={{fontSize: 16, color: 'success.main'}}>memory</Icon>
                        <Box>
                            <SummaryValue>{memory}</SummaryValue>
                            <SummaryLabel>Peak Memory</SummaryLabel>
                        </Box>
                    </SummaryItem>
                )}
                {isDebugEntryAboutConsole(entry) && entry.command && (
                    <SummaryItem>
                        <Icon sx={{fontSize: 16, color: 'primary.main'}}>terminal</Icon>
                        <Box>
                            <SummaryValue>{entry.command.input || entry.command.name}</SummaryValue>
                            <SummaryLabel>Command (exit: {entry.command.exitCode})</SummaryLabel>
                        </Box>
                    </SummaryItem>
                )}
            </SummaryBar>

            <Box sx={{px: {xs: 1.5, sm: 2.5}}}>
                {/* Environment strip */}
                {(phpVersion || adapter || osName) && (
                <EnvStrip
                    onClick={hasEnvironment ? () => handleCardClick(CollectorsMap.EnvironmentCollector) : undefined}
                >
                    <Icon sx={{fontSize: 14, color: 'text.disabled', mr: 0.25}}>dns</Icon>
                    {phpVersion && (
                        <EnvChip>
                            PHP <EnvChipValue>{phpVersion}</EnvChipValue>
                        </EnvChip>
                    )}
                    {phpSapi && (
                        <EnvChip>
                            SAPI <EnvChipValue>{phpSapi}</EnvChipValue>
                        </EnvChip>
                    )}
                    {adapter && (
                        <EnvChip>
                            Adapter <EnvChipValue>{adapter}</EnvChipValue>
                        </EnvChip>
                    )}
                    {osName && (
                        <EnvChip>
                            OS <EnvChipValue>{osName}</EnvChipValue>
                        </EnvChip>
                    )}
                    {hasEnvironment && <Icon sx={{fontSize: 14, color: 'text.disabled', ml: 'auto'}}>open_in_new</Icon>}
                </EnvStrip>
            )}

            {/* Performance breakdown */}
            {loadingPerf && <LinearProgress sx={{mb: 1.5, borderRadius: 1}} />}
            {webAppInfo && <PerformanceSection data={webAppInfo} />}

            {/* Active collectors */}
            {activeCards.length > 0 && (
                <>
                    <SectionDivider>
                        <DividerLabel>Collectors</DividerLabel>
                        <DividerLine />
                    </SectionDivider>
                    <ActiveGrid>
                        {activeCards.map((card) => {
                            const sparkBars = generateSparkBars(card.badge ?? 0);
                            return (
                                <ActiveCardRoot
                                    key={card.key}
                                    hasError={card.isException}
                                    onClick={() => handleCardClick(card.key)}
                                >
                                    <CardHeader>
                                        <CardTitle>
                                            <CardIconBox sx={{backgroundColor: card.iconBg}}>
                                                <Icon sx={{fontSize: 14, color: card.iconFg}}>{card.icon}</Icon>
                                            </CardIconBox>
                                            <CardName>{card.label}</CardName>
                                        </CardTitle>
                                        <Badge isError={card.isException}>{card.badge}</Badge>
                                    </CardHeader>
                                    <SparklineContainer>
                                        {sparkBars.map((height, i) => (
                                            <SparkBar
                                                key={i}
                                                sx={{height: `${height}%`}}
                                                isCurrent={i === sparkBars.length - 1}
                                                barColor={card.isException ? theme.palette.error.main : undefined}
                                            />
                                        ))}
                                    </SparklineContainer>
                                </ActiveCardRoot>
                            );
                        })}
                    </ActiveGrid>
                </>
            )}

            {/* Empty / info collectors */}
            {emptyCards.length > 0 && (
                <>
                    {activeCards.length === 0 && (
                        <SectionDivider>
                            <DividerLabel>Collectors</DividerLabel>
                            <DividerLine />
                        </SectionDivider>
                    )}
                    <CompactGrid>
                        {emptyCards.map((card) => (
                            <CompactCard key={card.key} onClick={() => handleCardClick(card.key)}>
                                <CompactIconBox sx={{backgroundColor: card.iconBg}}>
                                    <Icon sx={{fontSize: 12, color: card.iconFg}}>{card.icon}</Icon>
                                </CompactIconBox>
                                <Typography sx={{fontSize: '12px', fontWeight: 500, color: 'text.secondary'}}>
                                    {card.label}
                                </Typography>
                                {card.badge != null && (
                                    <Typography
                                        sx={{fontSize: '10px', fontWeight: 600, color: 'text.disabled', ml: 'auto'}}
                                    >
                                        {card.badge}
                                    </Typography>
                                )}
                            </CompactCard>
                        ))}
                    </CompactGrid>
                </>
            )}
            </Box>
        </Box>
    );
};
