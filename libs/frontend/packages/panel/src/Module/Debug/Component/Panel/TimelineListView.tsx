import {TimelineDetailCard} from '@app-dev-panel/panel/Module/Debug/Component/Panel/TimelineDetailCard';
import {getCollectorLabel} from '@app-dev-panel/sdk/Helper/collectorMeta';
import {CollectorsMap} from '@app-dev-panel/sdk/Helper/collectors';
import {Box, Collapse, Icon, IconButton, Typography} from '@mui/material';
import {alpha, styled, type Theme, useTheme} from '@mui/material/styles';
import {useCallback, useState} from 'react';

type Item = [number, number, string] | [number, number, string, string];

import {type EnrichedDetail} from '@app-dev-panel/panel/Module/Debug/Component/Panel/useTimelineEnrichment';

type TimelineListViewProps = {data: Item[]; filtered: Item[]; enrichedDetails: (EnrichedDetail | null)[]};

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

const Row = styled(Box, {shouldForwardProp: (p) => p !== 'selected' && p !== 'accentColor'})<{
    selected?: boolean;
    accentColor?: string;
}>(({theme, selected, accentColor}) => ({
    display: 'flex',
    alignItems: 'center',
    minHeight: 32,
    padding: theme.spacing(0.75, 1.5),
    borderBottom: `1px solid ${theme.palette.divider}`,
    borderLeft: `3px solid ${accentColor ?? 'transparent'}`,
    cursor: 'pointer',
    transition: 'background-color 0.1s ease',
    backgroundColor: selected ? theme.palette.action.selected : 'transparent',
    '&:hover': {backgroundColor: theme.palette.action.hover},
    gap: theme.spacing(1),
}));

const OffsetLabel = styled(Typography)(({theme}) => ({
    width: 80,
    flexShrink: 0,
    fontFamily: theme.adp.fontFamilyMono,
    fontSize: '11px',
    color: theme.palette.text.disabled,
    textAlign: 'right',
}));

const CollectorLabel = styled(Typography)({
    flexShrink: 0,
    fontSize: '12px',
    fontWeight: 600,
    whiteSpace: 'nowrap',
    minWidth: 80,
});

const RefPill = styled(Typography)(({theme}) => ({
    fontFamily: theme.adp.fontFamilyMono,
    fontSize: '10px',
    color: theme.palette.text.disabled,
    backgroundColor: theme.palette.action.selected,
    padding: '1px 6px',
    borderRadius: 4,
    flexShrink: 0,
    lineHeight: '16px',
    whiteSpace: 'nowrap',
}));

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
                const enriched = enrichedDetails[index];
                const expanded = expandedIndex === index;
                const isException = collectorClass === CollectorsMap.ExceptionCollector;

                // For LogCollector: extract level from "[level] message" format
                const logMatch = enriched?.preview.match(/^\[(\w+)] (.*)$/);
                const logLevel = logMatch?.[1] ?? null;
                const detail = isException ? ref : logMatch ? logMatch[2] : enriched?.preview ?? null;
                const fullDetail = isException ? ref : logMatch ? enriched!.full.replace(/^\[\w+] /, '') : enriched?.full ?? null;

                const levelColor = logLevel ? logLevelColor(logLevel, theme) : null;

                return (
                    <Box key={index}>
                        <Row selected={expanded} accentColor={color.fg} onClick={() => handleRowClick(index)}>
                            <OffsetLabel>{offsetLabel}</OffsetLabel>
                            <CollectorLabel sx={{color: color.fg}}>
                                {label !== 'Unknown' ? label : shortName}
                                {ref && !isException && <RefPill component="span" sx={{ml: 0.5}}>{ref}</RefPill>}
                                {logLevel && (
                                    <RefPill
                                        component="span"
                                        sx={{
                                            color: levelColor,
                                            backgroundColor: alpha(levelColor!, 0.12),
                                            fontWeight: 600,
                                            ml: 0.5,
                                        }}
                                    >
                                        {logLevel.toUpperCase()}
                                    </RefPill>
                                )}
                            </CollectorLabel>
                            {detail && <DetailText title={detail}>{detail}</DetailText>}
                            <IconButton size="small" sx={{flexShrink: 0, p: 0.25}}>
                                <Icon sx={{fontSize: 16, color: 'text.disabled'}}>
                                    {expanded ? 'expand_less' : 'expand_more'}
                                </Icon>
                            </IconButton>
                        </Row>
                        <Collapse in={expanded}>
                            <TimelineDetailCard
                                row={row}
                                fullDetail={fullDetail}
                                logLevel={logLevel}
                                accentColor={color.fg}
                                offsetLabel={offsetLabel}
                            />
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
