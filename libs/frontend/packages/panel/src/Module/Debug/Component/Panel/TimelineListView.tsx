import {JsonRenderer} from '@app-dev-panel/panel/Module/Debug/Component/JsonRenderer';
import {FileLink} from '@app-dev-panel/sdk/Component/FileLink';
import {isClassString} from '@app-dev-panel/sdk/Helper/classMatcher';
import {getCollectorLabel} from '@app-dev-panel/sdk/Helper/collectorMeta';
import {CollectorsMap} from '@app-dev-panel/sdk/Helper/collectors';
import {formatMicrotime} from '@app-dev-panel/sdk/Helper/formatDate';
import {toObjectString} from '@app-dev-panel/sdk/Helper/objectString';
import {Box, Collapse, Typography} from '@mui/material';
import {styled, type Theme, useTheme} from '@mui/material/styles';
import {useCallback, useState} from 'react';

type Item = [number, number, string] | [number, number, string, string];

type TimelineListViewProps = {data: Item[]; filtered: Item[]; enrichedDetails: (string | null)[]};

// ---------------------------------------------------------------------------
// Collector FQCN → color key mapping
// ---------------------------------------------------------------------------

const collectorColorKeyMap: Partial<Record<string, string>> = {
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
    [CollectorsMap.HttpClientCollector]: 'filesystem',
    [CollectorsMap.CacheCollector]: 'cache',
    [CollectorsMap.TemplateCollector]: 'template',
    [CollectorsMap.AuthorizationCollector]: 'authorization',
    [CollectorsMap.DeprecationCollector]: 'deprecation',
    [CollectorsMap.EnvironmentCollector]: 'environment',
    [CollectorsMap.TranslatorCollector]: 'translator',
    [CollectorsMap.WebAppInfoCollector]: 'environment',
    [CollectorsMap.ConsoleAppInfoCollector]: 'environment',
    [CollectorsMap.CommandCollector]: 'request',
    [CollectorsMap.QueueCollector]: 'service',
    [CollectorsMap.RouterCollector]: 'middleware',
    [CollectorsMap.ValidatorCollector]: 'service',
    [CollectorsMap.OpenTelemetryCollector]: 'timeline',
    [CollectorsMap.ElasticsearchCollector]: 'database',
    [CollectorsMap.RedisCollector]: 'cache',
};

// ---------------------------------------------------------------------------
// Styled components
// ---------------------------------------------------------------------------

const Row = styled(Box, {shouldForwardProp: (p) => p !== 'selected'})<{selected?: boolean}>(({theme, selected}) => ({
    display: 'flex',
    alignItems: 'center',
    minHeight: 32,
    padding: theme.spacing(0.5, 1.5),
    borderBottom: `1px solid ${theme.palette.divider}`,
    cursor: 'pointer',
    transition: 'background 0.1s',
    backgroundColor: selected ? theme.palette.action.selected : 'transparent',
    '&:hover': {backgroundColor: theme.palette.action.hover},
    gap: theme.spacing(1.5),
}));

const OffsetLabel = styled(Typography)(({theme}) => ({
    width: 72,
    flexShrink: 0,
    fontFamily: theme.adp.fontFamilyMono,
    fontSize: '11px',
    color: theme.palette.text.disabled,
    textAlign: 'right',
}));

const CollectorLabel = styled(Typography)({
    width: 140,
    flexShrink: 0,
    fontSize: '12px',
    fontWeight: 600,
    whiteSpace: 'nowrap',
    overflow: 'hidden',
    textOverflow: 'ellipsis',
});

const DetailText = styled(Typography)(({theme}) => ({
    flex: 1,
    fontSize: '12px',
    fontFamily: theme.adp.fontFamilyMono,
    color: theme.palette.text.secondary,
    whiteSpace: 'nowrap',
    overflow: 'hidden',
    textOverflow: 'ellipsis',
    minWidth: 0,
}));

const DetailBox = styled(Box)(({theme}) => ({
    padding: theme.spacing(1.5, 2, 1.5, 5),
    backgroundColor: theme.palette.action.hover,
    borderBottom: `1px solid ${theme.palette.divider}`,
    fontSize: '12px',
}));

const ScaleBar = styled(Box)(({theme}) => ({
    display: 'flex',
    justifyContent: 'space-between',
    padding: theme.spacing(1, 1.5),
    fontSize: '10px',
    fontFamily: theme.adp.fontFamilyMono,
    color: theme.palette.text.disabled,
    borderTop: `1px solid ${theme.palette.divider}`,
    position: 'relative',
    '&::before': {
        content: '""',
        position: 'absolute',
        top: 0,
        left: theme.spacing(1.5),
        right: theme.spacing(1.5),
        height: 4,
        background: `linear-gradient(to right, ${theme.palette.divider}, ${theme.palette.divider})`,
        borderRadius: 1,
    },
}));

// ---------------------------------------------------------------------------
// Helpers
// ---------------------------------------------------------------------------

function logLevelColor(level: string, theme: Theme): string {
    switch (level) {
        case 'emergency':
        case 'alert':
        case 'critical':
        case 'error':
            return theme.palette.error.main;
        case 'warning':
            return theme.palette.warning.main;
        case 'notice':
            return theme.palette.primary.main;
        case 'info':
            return theme.palette.success.main;
        case 'debug':
        default:
            return theme.palette.text.disabled;
    }
}

function formatOffset(relativeTime: number): string {
    if (relativeTime < 0.001) {
        return `+${(relativeTime * 1000000).toFixed(0)}µs`;
    }
    if (relativeTime < 1) {
        return `+${(relativeTime * 1000).toFixed(1)}ms`;
    }
    return `+${relativeTime.toFixed(3)}s`;
}

// ---------------------------------------------------------------------------
// Component
// ---------------------------------------------------------------------------

export const TimelineListView = ({data, filtered, enrichedDetails}: TimelineListViewProps) => {
    const theme = useTheme();
    const [expandedIndex, setExpandedIndex] = useState<number | null>(null);

    // Time calculations (use full data for consistent scale)
    const timestamps = data.map((r) => r[0]);
    const minTime = Math.min(...timestamps);
    const maxTime = Math.max(...timestamps);
    const totalSpan = maxTime - minTime || 0.001;

    // Build scale ticks
    const tickCount = 5;
    const ticks: string[] = [];
    for (let i = 0; i <= tickCount; i++) {
        const t = (totalSpan / tickCount) * i;
        if (t < 0.001) {
            ticks.push(`${(t * 1000000).toFixed(0)}µs`);
        } else if (t < 1) {
            ticks.push(`${(t * 1000).toFixed(1)}ms`);
        } else {
            ticks.push(`${t.toFixed(2)}s`);
        }
    }

    const getColor = useCallback(
        (collectorClass: string) => {
            const key = collectorColorKeyMap[collectorClass] ?? 'default';
            const colors = theme.adp.collectorColors as any;
            return colors[key] ?? colors.default;
        },
        [theme],
    );

    const handleRowClick = useCallback(
        (index: number) => {
            setExpandedIndex(expandedIndex === index ? null : index);
        },
        [expandedIndex],
    );

    return (
        <Box>
            {filtered.map((row, index) => {
                const collectorClass = row[2];
                const shortName = collectorClass.split('\\').pop() ?? collectorClass;
                const relativeTime = row[0] - minTime;
                const offsetLabel = formatOffset(relativeTime);
                const color = getColor(collectorClass);
                const label = getCollectorLabel(collectorClass);
                const ref = row[1] != null && row[1] !== '' ? String(row[1]) : null;
                const rawDetail = enrichedDetails[index];
                const expanded = expandedIndex === index;
                const isException = collectorClass === CollectorsMap.ExceptionCollector;

                // For LogCollector: extract level from "[level] message" format
                const logMatch = rawDetail?.match(/^\[(\w+)] (.*)$/);
                const logLevel = logMatch?.[1] ?? null;
                const detail = isException ? ref : logMatch ? logMatch[2] : rawDetail;

                return (
                    <Box key={index}>
                        <Row selected={expanded} onClick={() => handleRowClick(index)}>
                            <OffsetLabel>{offsetLabel}</OffsetLabel>
                            <CollectorLabel sx={{color: color.fg}}>
                                {label !== 'Unknown' ? label : shortName}
                                {ref && !isException && (
                                    <Typography
                                        component="span"
                                        sx={{color: 'text.disabled', fontWeight: 400, fontSize: '11px', ml: 0.5}}
                                    >
                                        ({ref})
                                    </Typography>
                                )}
                                {logLevel && (
                                    <Typography
                                        component="span"
                                        sx={{
                                            color: logLevelColor(logLevel, theme),
                                            fontWeight: 400,
                                            fontSize: '11px',
                                            ml: 0.5,
                                        }}
                                    >
                                        [{logLevel}]
                                    </Typography>
                                )}
                            </CollectorLabel>
                            {detail && <DetailText title={detail}>{detail}</DetailText>}
                        </Row>
                        <Collapse in={expanded}>
                            <DetailBox>
                                <Box sx={{display: 'flex', gap: 3, mb: 1, flexWrap: 'wrap'}}>
                                    <Typography variant="caption" sx={{color: 'text.disabled'}}>
                                        Time: {formatMicrotime(row[0])}
                                    </Typography>
                                    <Typography variant="caption" sx={{color: 'text.disabled'}}>
                                        Offset: {offsetLabel}
                                    </Typography>
                                    {row[1] != null && (
                                        <Typography variant="caption" sx={{color: 'text.disabled'}}>
                                            Ref: {String(row[1])}
                                        </Typography>
                                    )}
                                </Box>
                                <FileLink className={collectorClass}>
                                    <Typography
                                        variant="caption"
                                        component="span"
                                        sx={(t) => ({
                                            fontFamily: t.adp.fontFamilyMono,
                                            color: 'primary.main',
                                            '&:hover': {textDecoration: 'underline'},
                                        })}
                                    >
                                        {collectorClass}
                                    </Typography>
                                </FileLink>
                                {!!row[3] && (
                                    <JsonRenderer
                                        value={isClassString(row[3]) ? toObjectString(row[3], row[1]) : row[3]}
                                    />
                                )}
                            </DetailBox>
                        </Collapse>
                    </Box>
                );
            })}

            {/* Mini time scale */}
            <ScaleBar>
                {ticks.map((tick, i) => (
                    <span key={i}>{tick}</span>
                ))}
            </ScaleBar>
        </Box>
    );
};
