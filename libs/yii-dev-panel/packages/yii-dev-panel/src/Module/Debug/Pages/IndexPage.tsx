import {Box, Icon, Typography} from '@mui/material';
import {styled} from '@mui/material/styles';
import {useDebugEntry} from '@yiisoft/yii-dev-panel-sdk/API/Debug/Context';
import {DebugEntry} from '@yiisoft/yii-dev-panel-sdk/API/Debug/Debug';
import {SectionTitle} from '@yiisoft/yii-dev-panel-sdk/Component/SectionTitle';
import {primitives} from '@yiisoft/yii-dev-panel-sdk/Component/Theme/tokens';
import {isDebugEntryAboutConsole, isDebugEntryAboutWeb} from '@yiisoft/yii-dev-panel-sdk/Helper/debugEntry';
import {formatBytes} from '@yiisoft/yii-dev-panel-sdk/Helper/formatBytes';
import {formatDate, formatMillisecondsAsDuration} from '@yiisoft/yii-dev-panel-sdk/Helper/formatDate';
import {useSearchParams} from 'react-router-dom';

const MetricGrid = styled(Box)(({theme}) => ({
    display: 'grid',
    gridTemplateColumns: 'repeat(auto-fill, minmax(200px, 1fr))',
    gap: theme.spacing(2),
}));

type MetricCardProps = {color?: string};

const MetricCard = styled(Box, {shouldForwardProp: (p) => p !== 'color'})<MetricCardProps>(({theme, color}) => ({
    display: 'flex',
    alignItems: 'center',
    gap: theme.spacing(1.5),
    padding: theme.spacing(2),
    borderRadius: theme.shape.borderRadius,
    border: `1px solid ${theme.palette.divider}`,
    cursor: 'pointer',
    transition: 'border-color 0.15s ease',
    '&:hover': {borderColor: color || theme.palette.primary.main},
}));

const MetricValue = styled(Typography)({fontFamily: primitives.fontFamilyMono, fontWeight: 600, fontSize: '20px'});

const MetricLabel = styled(Typography)(({theme}) => ({
    fontSize: '12px',
    color: theme.palette.text.secondary,
    fontWeight: 500,
}));

const KVRow = styled(Box)(({theme}) => ({
    display: 'flex',
    justifyContent: 'space-between',
    padding: theme.spacing(1, 0),
    borderBottom: `1px solid ${theme.palette.divider}`,
    fontSize: '13px',
    '&:last-child': {borderBottom: 'none'},
}));

const KVLabel = styled('span')(({theme}) => ({color: theme.palette.text.disabled, fontWeight: 500}));

const KVValue = styled('span')({fontFamily: primitives.fontFamilyMono, fontSize: '12px'});

type MetricItem = {icon: string; label: string; value: string | number; color: string; collectorKey?: string};

function getMetrics(entry: DebugEntry): MetricItem[] {
    const metrics: MetricItem[] = [];

    if (isDebugEntryAboutWeb(entry) && entry.web) {
        metrics.push({
            icon: 'timer',
            label: 'Response Time',
            value: formatMillisecondsAsDuration(entry.web.request.processingTime),
            color: primitives.blue500,
        });
        metrics.push({
            icon: 'memory',
            label: 'Peak Memory',
            value: formatBytes(entry.web.memory.peakUsage),
            color: primitives.blue500,
        });
    }
    if (isDebugEntryAboutConsole(entry) && entry.console) {
        metrics.push({
            icon: 'timer',
            label: 'Execution Time',
            value: formatMillisecondsAsDuration(entry.console.request.processingTime),
            color: primitives.blue500,
        });
        metrics.push({
            icon: 'memory',
            label: 'Peak Memory',
            value: formatBytes(entry.console.memory.peakUsage),
            color: primitives.blue500,
        });
    }
    if (entry.logger) {
        metrics.push({
            icon: 'description',
            label: 'Log Entries',
            value: entry.logger.total,
            color: primitives.green600,
            collectorKey: 'Yiisoft\\Yii\\Debug\\Collector\\LogCollector',
        });
    }
    if (entry.event) {
        metrics.push({
            icon: 'bolt',
            label: 'Events',
            value: entry.event.total,
            color: primitives.amber600,
            collectorKey: 'Yiisoft\\Yii\\Debug\\Collector\\EventCollector',
        });
    }
    if (entry.service) {
        metrics.push({
            icon: 'inventory_2',
            label: 'Services',
            value: entry.service.total,
            color: primitives.blue500,
            collectorKey: 'Yiisoft\\Yii\\Debug\\Collector\\ServiceCollector',
        });
    }
    return metrics;
}

export const IndexPage = () => {
    const entry = useDebugEntry();
    const [, setSearchParams] = useSearchParams();

    if (!entry) {
        return (
            <Box sx={{textAlign: 'center', py: 6, color: 'text.disabled'}}>
                <Typography variant="body1">No debug entry selected</Typography>
            </Box>
        );
    }

    const metrics = getMetrics(entry);

    const handleMetricClick = (collectorKey?: string) => {
        if (!collectorKey) return;
        setSearchParams((params) => {
            params.set('collector', collectorKey);
            params.set('debugEntry', entry.id);
            return params;
        });
    };

    return (
        <Box>
            <SectionTitle>Summary</SectionTitle>
            <MetricGrid>
                {metrics.map((m) => (
                    <MetricCard key={m.label} color={m.color} onClick={() => handleMetricClick(m.collectorKey)}>
                        <Icon sx={{fontSize: 24, color: m.color}}>{m.icon}</Icon>
                        <Box>
                            <MetricValue>{m.value}</MetricValue>
                            <MetricLabel>{m.label}</MetricLabel>
                        </Box>
                    </MetricCard>
                ))}
            </MetricGrid>

            {isDebugEntryAboutWeb(entry) && entry.request && (
                <>
                    <SectionTitle>Request</SectionTitle>
                    <KVRow>
                        <KVLabel>Method</KVLabel>
                        <KVValue>{entry.request.method}</KVValue>
                    </KVRow>
                    <KVRow>
                        <KVLabel>URL</KVLabel>
                        <KVValue>{entry.request.url || entry.request.path}</KVValue>
                    </KVRow>
                    <KVRow>
                        <KVLabel>Status</KVLabel>
                        <KVValue>{entry.response?.statusCode}</KVValue>
                    </KVRow>
                    <KVRow>
                        <KVLabel>User IP</KVLabel>
                        <KVValue>{entry.request.userIp}</KVValue>
                    </KVRow>
                    {entry.request.query && (
                        <KVRow>
                            <KVLabel>Query</KVLabel>
                            <KVValue>{entry.request.query}</KVValue>
                        </KVRow>
                    )}
                </>
            )}

            {isDebugEntryAboutConsole(entry) && entry.command && (
                <>
                    <SectionTitle>Command</SectionTitle>
                    <KVRow>
                        <KVLabel>Name</KVLabel>
                        <KVValue>{entry.command.name || entry.command.input}</KVValue>
                    </KVRow>
                    <KVRow>
                        <KVLabel>Exit Code</KVLabel>
                        <KVValue>{entry.command.exitCode}</KVValue>
                    </KVRow>
                    <KVRow>
                        <KVLabel>Class</KVLabel>
                        <KVValue>{entry.command.class}</KVValue>
                    </KVRow>
                </>
            )}

            {entry.router && (
                <>
                    <SectionTitle>Route</SectionTitle>
                    <KVRow>
                        <KVLabel>Pattern</KVLabel>
                        <KVValue>{entry.router.pattern}</KVValue>
                    </KVRow>
                    <KVRow>
                        <KVLabel>Name</KVLabel>
                        <KVValue>{entry.router.name}</KVValue>
                    </KVRow>
                    <KVRow>
                        <KVLabel>Action</KVLabel>
                        <KVValue>
                            {Array.isArray(entry.router.action) ? entry.router.action.join(', ') : entry.router.action}
                        </KVValue>
                    </KVRow>
                    <KVRow>
                        <KVLabel>Match Time</KVLabel>
                        <KVValue>{formatMillisecondsAsDuration(entry.router.matchTime)}</KVValue>
                    </KVRow>
                </>
            )}

            <SectionTitle>Environment</SectionTitle>
            <KVRow>
                <KVLabel>PHP Version</KVLabel>
                <KVValue>{entry.web?.php?.version || entry.console?.php?.version || 'N/A'}</KVValue>
            </KVRow>
            {isDebugEntryAboutWeb(entry) && entry.web?.request?.startTime && (
                <KVRow>
                    <KVLabel>Time</KVLabel>
                    <KVValue>{formatDate(entry.web.request.startTime)}</KVValue>
                </KVRow>
            )}
            <KVRow>
                <KVLabel>Entry ID</KVLabel>
                <KVValue>{entry.id}</KVValue>
            </KVRow>
            <KVRow>
                <KVLabel>Collectors</KVLabel>
                <KVValue>{entry.collectors.filter((c) => typeof c === 'string').length}</KVValue>
            </KVRow>
        </Box>
    );
};
