import {useDebugEntry} from '@app-dev-panel/sdk/API/Debug/Context';
import {DebugEntry, useLazyGetCollectorInfoQuery} from '@app-dev-panel/sdk/API/Debug/Debug';
import {primitives} from '@app-dev-panel/sdk/Component/Theme/tokens';
import {compareCollectorWeight, getCollectorIcon, getCollectorLabel} from '@app-dev-panel/sdk/Helper/collectorMeta';
import {CollectorsMap} from '@app-dev-panel/sdk/Helper/collectors';
import {getCollectedCountByCollector} from '@app-dev-panel/sdk/Helper/collectorsTotal';
import {isDebugEntryAboutConsole, isDebugEntryAboutWeb} from '@app-dev-panel/sdk/Helper/debugEntry';
import {formatBytes} from '@app-dev-panel/sdk/Helper/formatBytes';
import {formatMillisecondsAsDuration} from '@app-dev-panel/sdk/Helper/formatDate';
import {Box, Icon, LinearProgress, Typography} from '@mui/material';
import {styled, useTheme} from '@mui/material/styles';
import {useEffect, useState} from 'react';
import {useSearchParams} from 'react-router-dom';

// Collectors hidden from the card grid (data shown elsewhere in overview)
const hiddenCollectors = new Set<string>([CollectorsMap.WebAppInfoCollector, CollectorsMap.ConsoleAppInfoCollector]);

// ---------------------------------------------------------------------------
// Card icon background/foreground colors per collector
// ---------------------------------------------------------------------------
const iconColors: Record<string, {bg: string; fg: string}> = {
    [CollectorsMap.RequestCollector]: {bg: '#EFF6FF', fg: '#2563EB'},
    [CollectorsMap.LogCollector]: {bg: '#FEF3C7', fg: '#D97706'},
    [CollectorsMap.EventCollector]: {bg: '#F3E8FF', fg: '#9333EA'},
    [CollectorsMap.DatabaseCollector]: {bg: '#ECFDF5', fg: '#16A34A'},
    [CollectorsMap.MiddlewareCollector]: {bg: '#FFF7ED', fg: '#EA580C'},
    [CollectorsMap.ExceptionCollector]: {bg: '#FEF2F2', fg: '#DC2626'},
    [CollectorsMap.ServiceCollector]: {bg: '#F0F9FF', fg: '#0284C7'},
    [CollectorsMap.TimelineCollector]: {bg: '#F5F3FF', fg: '#7C3AED'},
    [CollectorsMap.VarDumperCollector]: {bg: '#F5F5F5', fg: '#666666'},
    [CollectorsMap.MailerCollector]: {bg: '#FDF4FF', fg: '#A855F7'},
    [CollectorsMap.FilesystemStreamCollector]: {bg: '#FFF7ED', fg: '#EA580C'},
    [CollectorsMap.CacheCollector]: {bg: '#ECFDF5', fg: '#059669'},
    [CollectorsMap.DoctrineCollector]: {bg: '#EFF6FF', fg: '#2563EB'},
    [CollectorsMap.TwigCollector]: {bg: '#FEF3C7', fg: '#B45309'},
    [CollectorsMap.SecurityCollector]: {bg: '#FEF2F2', fg: '#DC2626'},
    [CollectorsMap.MessengerCollector]: {bg: '#F0F9FF', fg: '#0284C7'},
};
const defaultIconColor = {bg: '#F5F5F5', fg: '#666666'};

// ---------------------------------------------------------------------------
// Styled components
// ---------------------------------------------------------------------------

const CardsGrid = styled(Box)(({theme}) => ({
    display: 'grid',
    gridTemplateColumns: 'repeat(auto-fill, minmax(200px, 1fr))',
    gap: theme.spacing(2),
    marginBottom: theme.spacing(3),
}));

type CollectorCardRootProps = {hasError?: boolean};

const CollectorCardRoot = styled(Box, {shouldForwardProp: (p) => p !== 'hasError'})<CollectorCardRootProps>(
    ({theme, hasError}) => ({
        background: theme.palette.background.paper,
        border: `1px solid ${theme.palette.divider}`,
        borderRadius: theme.shape.borderRadius * 1.5,
        padding: theme.spacing(2.5),
        cursor: 'pointer',
        transition: 'all 0.2s',
        position: 'relative',
        boxShadow: '0 1px 3px rgba(0,0,0,0.06), 0 1px 2px rgba(0,0,0,0.04)',
        ...(hasError && {borderLeft: `3px solid ${theme.palette.error.main}`}),
        '&:hover': {
            boxShadow: '0 4px 12px rgba(0,0,0,0.08), 0 2px 4px rgba(0,0,0,0.04)',
            borderColor: theme.palette.primary.main,
            transform: 'translateY(-1px)',
        },
    }),
);

const CardHeader = styled(Box)({
    display: 'flex',
    alignItems: 'center',
    justifyContent: 'space-between',
    marginBottom: 12,
});

const CardTitle = styled(Box)(({theme}) => ({display: 'flex', alignItems: 'center', gap: theme.spacing(1)}));

const CardIconBox = styled(Box)({
    width: 28,
    height: 28,
    borderRadius: 8,
    display: 'flex',
    alignItems: 'center',
    justifyContent: 'center',
});

const CardName = styled(Typography)({fontWeight: 600, fontSize: '14px'});

type BadgeProps = {isError?: boolean};

const Badge = styled('span', {shouldForwardProp: (p) => p !== 'isError'})<BadgeProps>(({theme, isError}) => ({
    padding: '2px 8px',
    borderRadius: 10,
    fontSize: '11px',
    fontWeight: 600,
    backgroundColor: isError ? theme.palette.error.light : theme.palette.background.default,
    color: isError ? theme.palette.error.main : theme.palette.text.secondary,
}));

const CardSummary = styled(Typography)(({theme}) => ({
    fontSize: '13px',
    color: theme.palette.text.secondary,
    marginBottom: theme.spacing(1.25),
}));

const SparklineContainer = styled(Box)({display: 'flex', alignItems: 'flex-end', gap: 2, height: 16});

type SparkBarProps = {isCurrent?: boolean; barColor?: string};

const SparkBar = styled(Box, {shouldForwardProp: (p) => p !== 'isCurrent' && p !== 'barColor'})<SparkBarProps>(
    ({theme, isCurrent, barColor}) => ({
        width: 6,
        borderRadius: '2px 2px 0 0',
        backgroundColor: barColor || theme.palette.primary.main,
        opacity: isCurrent ? 1 : 0.4,
        transition: 'opacity 0.15s',
    }),
);

// ---------------------------------------------------------------------------
// Summary section (top metrics bar)
// ---------------------------------------------------------------------------

const SummaryBar = styled(Box)(({theme}) => ({
    display: 'flex',
    flexWrap: 'wrap',
    gap: theme.spacing(3),
    marginBottom: theme.spacing(3),
    padding: theme.spacing(2, 2.5),
    borderRadius: theme.shape.borderRadius * 1.5,
    border: `1px solid ${theme.palette.divider}`,
    backgroundColor: theme.palette.background.paper,
}));

const SummaryItem = styled(Box)({display: 'flex', alignItems: 'center', gap: 8});

const SummaryValue = styled(Typography)({fontFamily: primitives.fontFamilyMono, fontWeight: 600, fontSize: '15px'});

const SummaryLabel = styled(Typography)(({theme}) => ({fontSize: '12px', color: theme.palette.text.secondary}));

// ---------------------------------------------------------------------------
// Performance breakdown section (WebAppInfo data)
// ---------------------------------------------------------------------------

const PerfGrid = styled(Box)(({theme}) => ({
    display: 'grid',
    gridTemplateColumns: 'repeat(auto-fill, minmax(180px, 1fr))',
    gap: theme.spacing(2),
    marginBottom: theme.spacing(3),
}));

const PerfCard = styled(Box)(({theme}) => ({
    padding: theme.spacing(2),
    borderRadius: theme.shape.borderRadius * 1.5,
    border: `1px solid ${theme.palette.divider}`,
    backgroundColor: theme.palette.background.paper,
}));

const PerfLabel = styled(Typography)(({theme}) => ({
    fontSize: '11px',
    fontWeight: 600,
    textTransform: 'uppercase' as const,
    letterSpacing: '0.5px',
    color: theme.palette.text.disabled,
    marginBottom: theme.spacing(0.5),
}));

const PerfValue = styled(Typography)({fontFamily: primitives.fontFamilyMono, fontWeight: 600, fontSize: '18px'});

const PerfBarTrack = styled(Box)(({theme}) => ({
    height: 4,
    borderRadius: 2,
    backgroundColor: theme.palette.action.hover,
    marginTop: theme.spacing(1),
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

const PerformanceSection = ({data}: {data: WebAppInfoData}) => {
    const totalTime = data.applicationProcessingTime || 0;
    const requestTime = data.requestProcessingTime || 0;
    const emitTime = data.applicationEmit || 0;
    const preloadTime = data.preloadTime || 0;
    const memPeak = data.memoryPeakUsage || 0;
    const memUsage = data.memoryUsage || 0;

    const formatTime = (seconds: number) => {
        if (seconds === 0) return '0 ms';
        if (seconds > 1000) return 'N/A';
        if (seconds < 0) return 'N/A';
        if (seconds < 0.001) return `${(seconds * 1000000).toFixed(0)} µs`;
        if (seconds < 1) return `${(seconds * 1000).toFixed(2)} ms`;
        return `${seconds.toFixed(3)} s`;
    };

    const validTimes = [totalTime, requestTime, preloadTime, emitTime].filter((t) => t > 0 && t <= 1000);
    const maxTime = validTimes.length > 0 ? Math.max(...validTimes) : 0.001;

    const safeRatio = (value: number, max: number) => (value > 0 && value <= 1000 ? value / max : 0);

    const items = [
        {
            label: 'Total Time',
            value: formatTime(totalTime),
            ratio: safeRatio(totalTime, maxTime),
            color: 'primary.main',
        },
        {
            label: 'Request Processing',
            value: formatTime(requestTime),
            ratio: safeRatio(requestTime, maxTime),
            color: '#42A5F5',
        },
        {
            label: 'Preload Time',
            value: formatTime(preloadTime),
            ratio: safeRatio(preloadTime, maxTime),
            color: '#AB47BC',
        },
        {label: 'Emit Time', value: formatTime(emitTime), ratio: safeRatio(emitTime, maxTime), color: '#66BB6A'},
        {label: 'Peak Memory', value: formatBytes(memPeak), ratio: memPeak > 0 ? 1 : 0, color: '#FFA726'},
        {
            label: 'Memory Usage',
            value: formatBytes(memUsage),
            ratio: memPeak > 0 ? memUsage / memPeak : 0,
            color: '#26C6DA',
        },
    ];

    return (
        <PerfGrid>
            {items.map((item) => (
                <PerfCard key={item.label}>
                    <PerfLabel>{item.label}</PerfLabel>
                    <PerfValue sx={{color: item.color}}>{item.value}</PerfValue>
                    <PerfBarTrack>
                        <Box
                            sx={{
                                height: '100%',
                                width: `${Math.max(2, item.ratio * 100)}%`,
                                backgroundColor: item.color,
                                borderRadius: 2,
                                transition: 'width 0.3s ease',
                            }}
                        />
                    </PerfBarTrack>
                </PerfCard>
            ))}
        </PerfGrid>
    );
};

// ---------------------------------------------------------------------------
// Helper: generate sparkline bars (decorative, based on badge count)
// ---------------------------------------------------------------------------

function generateSparkBars(count: number): number[] {
    if (count <= 0) return [5, 5, 5, 5, 5, 5, 5, 5];
    const bars: number[] = [];
    for (let i = 0; i < 8; i++) {
        bars.push(Math.max(10, Math.min(100, Math.round(15 + Math.sin(i * 0.8 + count) * 40 + count * 3))));
    }
    return bars;
}

// ---------------------------------------------------------------------------
// Build collector card data from debug entry
// ---------------------------------------------------------------------------

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

function buildCollectorCards(entry: DebugEntry): CollectorCardData[] {
    const isWeb = isDebugEntryAboutWeb(entry);
    const isConsole = isDebugEntryAboutConsole(entry);

    return [...entry.collectors]
        .map((c) => (typeof c === 'string' ? c : c.id))
        .filter((c) => !hiddenCollectors.has(c))
        .filter((c) => {
            // Show only one of Request/Command based on entry type
            if (c === CollectorsMap.CommandCollector && isWeb) return false;
            if (c === CollectorsMap.RequestCollector && isConsole) return false;
            return true;
        })
        .sort(compareCollectorWeight)
        .map((collector) => {
            const count = getCollectedCountByCollector(collector as CollectorsMap, entry);
            const isException = collector === CollectorsMap.ExceptionCollector && !!count && count > 0;
            const colors = iconColors[collector] || defaultIconColor;
            const label = getCollectorLabel(collector);
            let summary = count != null ? `${count} ${label.toLowerCase()}` : label;

            // Customize summaries
            if (collector === CollectorsMap.DatabaseCollector && entry.db) {
                const queries = Number(entry.db.queries?.total ?? 0);
                summary = `${queries} queries`;
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
// IndexPage — card-grid dashboard (variant-d-minimal-zen)
// ---------------------------------------------------------------------------

export const IndexPage = () => {
    const theme = useTheme();
    const entry = useDebugEntry();
    const [, setSearchParams] = useSearchParams();
    const [getCollectorInfo] = useLazyGetCollectorInfoQuery();
    const [webAppInfo, setWebAppInfo] = useState<WebAppInfoData | null>(null);
    const [loadingPerf, setLoadingPerf] = useState(false);

    // Fetch WebAppInfo collector data for performance breakdown
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

    const cards = buildCollectorCards(entry);

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

    const phpVersion = isDebugEntryAboutWeb(entry) ? entry.web?.php?.version : entry.console?.php?.version;
    const status = isDebugEntryAboutWeb(entry) ? entry.response?.statusCode : null;
    const method = isDebugEntryAboutWeb(entry) ? entry.request?.method : null;
    const path = isDebugEntryAboutWeb(entry) ? entry.request?.path : null;

    return (
        <Box>
            <SummaryBar>
                {method && path && (
                    <SummaryItem>
                        <Icon sx={{fontSize: 18, color: 'primary.main'}}>http</Icon>
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
                        <Icon sx={{fontSize: 18, color: status >= 400 ? 'error.main' : 'success.main'}}>
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
                        <Icon sx={{fontSize: 18, color: 'primary.main'}}>timer</Icon>
                        <Box>
                            <SummaryValue>{duration}</SummaryValue>
                            <SummaryLabel>Duration</SummaryLabel>
                        </Box>
                    </SummaryItem>
                )}
                {memory && (
                    <SummaryItem>
                        <Icon sx={{fontSize: 18, color: 'success.main'}}>memory</Icon>
                        <Box>
                            <SummaryValue>{memory}</SummaryValue>
                            <SummaryLabel>Peak Memory</SummaryLabel>
                        </Box>
                    </SummaryItem>
                )}
                {phpVersion && (
                    <SummaryItem>
                        <Icon sx={{fontSize: 18, color: '#777EB8'}}>code</Icon>
                        <Box>
                            <SummaryValue>PHP {phpVersion}</SummaryValue>
                            <SummaryLabel>Runtime</SummaryLabel>
                        </Box>
                    </SummaryItem>
                )}
                {isDebugEntryAboutConsole(entry) && entry.command && (
                    <SummaryItem>
                        <Icon sx={{fontSize: 18, color: 'primary.main'}}>terminal</Icon>
                        <Box>
                            <SummaryValue>{entry.command.input || entry.command.name}</SummaryValue>
                            <SummaryLabel>Command (exit: {entry.command.exitCode})</SummaryLabel>
                        </Box>
                    </SummaryItem>
                )}
            </SummaryBar>

            {loadingPerf && <LinearProgress sx={{mb: 2}} />}
            {webAppInfo && <PerformanceSection data={webAppInfo} />}

            <CardsGrid>
                {cards.map((card) => {
                    const sparkBars = generateSparkBars(card.badge ?? 0);
                    return (
                        <CollectorCardRoot
                            key={card.key}
                            hasError={card.isException}
                            onClick={() => handleCardClick(card.key)}
                        >
                            <CardHeader>
                                <CardTitle>
                                    <CardIconBox sx={{backgroundColor: card.iconBg}}>
                                        <Icon sx={{fontSize: 16, color: card.iconFg}}>{card.icon}</Icon>
                                    </CardIconBox>
                                    <CardName>{card.label}</CardName>
                                </CardTitle>
                                {card.badge != null && <Badge isError={card.isException}>{card.badge}</Badge>}
                            </CardHeader>
                            <CardSummary>{card.summary}</CardSummary>
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
                        </CollectorCardRoot>
                    );
                })}
            </CardsGrid>
        </Box>
    );
};
