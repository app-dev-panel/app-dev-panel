import {DebugEntry} from '@app-dev-panel/sdk/API/Debug/Debug';
import {isDebugEntryAboutConsole, isDebugEntryAboutWeb} from '@app-dev-panel/sdk/Helper/debugEntry';
import {Box, Chip} from '@mui/material';

const chipSx = {
    height: 27,
    borderRadius: 1.5,
    fontSize: 11,
    fontFamily: "'JetBrains Mono', monospace",
    fontWeight: 500,
    cursor: 'pointer',
    '& .MuiChip-label': {px: 1},
};

const formatTime = (seconds: number): string => {
    const ms = seconds * 1000;
    if (ms < 1000) return `${Math.round(ms)}ms`;
    return `${seconds.toFixed(2)}s`;
};

const formatMemory = (bytes: number): string => {
    if (bytes < 1024 * 1024) return `${(bytes / 1024).toFixed(0)} KB`;
    return `${(bytes / (1024 * 1024)).toFixed(1)} MB`;
};

type FloatMetricsProps = {entry: DebugEntry; iframeUrlHandler: (url: string) => void};

export const FloatMetrics = ({entry}: FloatMetricsProps) => {
    const timing = entry.web || entry.console;

    return (
        <Box sx={{display: 'flex', flexWrap: 'wrap', gap: 0.5, alignContent: 'flex-start'}}>
            {timing && (
                <Chip
                    label={`\u23F1 ${formatTime(timing.request.processingTime)}`}
                    size="small"
                    variant="outlined"
                    sx={chipSx}
                />
            )}
            {timing && (
                <Chip
                    label={`\uD83D\uDCBE ${formatMemory(timing.memory.peakUsage)}`}
                    size="small"
                    variant="outlined"
                    sx={chipSx}
                />
            )}
            {entry.db && (
                <Chip label={`\uD83D\uDDC4 DB ${entry.db.queries.total}`} size="small" variant="outlined" sx={chipSx} />
            )}
            {entry.http && entry.http.count > 0 && (
                <Chip label={`\uD83C\uDF10 HTTP ${entry.http.count}`} size="small" variant="outlined" sx={chipSx} />
            )}
            {entry.logger && entry.logger.total > 0 && (
                <Chip label={`\uD83D\uDCCB Logs ${entry.logger.total}`} size="small" variant="outlined" sx={chipSx} />
            )}
            {entry.event && entry.event.total > 0 && (
                <Chip label={`\u26A1 Ev ${entry.event.total}`} size="small" variant="outlined" sx={chipSx} />
            )}
            {entry.deprecation && entry.deprecation.total > 0 && (
                <Chip
                    label={`\u26A0 Depr ${entry.deprecation.total}`}
                    size="small"
                    variant="outlined"
                    color="warning"
                    sx={chipSx}
                />
            )}
            {entry.exception && (
                <Chip
                    label={`\uD83D\uDCA5 ${entry.exception.class}`}
                    size="small"
                    variant="outlined"
                    color="error"
                    sx={chipSx}
                />
            )}
        </Box>
    );
};

/** Request hero bar for float/side modes */
export const RequestHeroBar = ({entry}: {entry: DebugEntry}) => {
    const timing = entry.web || entry.console;
    const isWeb = isDebugEntryAboutWeb(entry);
    const isConsole = isDebugEntryAboutConsole(entry);

    return (
        <Box
            sx={{
                display: 'flex',
                alignItems: 'center',
                gap: 0.75,
                px: 1.25,
                py: 0.5,
                bgcolor: 'primary.light',
                flexShrink: 0,
                fontFamily: "'JetBrains Mono', monospace",
                fontSize: 11,
                overflow: 'hidden',
            }}
        >
            {isWeb && (
                <>
                    <Box component="span" sx={{fontWeight: 700}}>
                        {entry.request?.method}
                    </Box>
                    <Box component="span" sx={{fontWeight: 700, color: 'success.main'}}>
                        {entry.response?.statusCode}
                    </Box>
                    <Box
                        component="span"
                        sx={{
                            color: 'text.secondary',
                            flex: 1,
                            overflow: 'hidden',
                            textOverflow: 'ellipsis',
                            whiteSpace: 'nowrap',
                        }}
                    >
                        {entry.request?.path}
                    </Box>
                </>
            )}
            {isConsole && (
                <>
                    <Box component="span" sx={{fontWeight: 700}}>
                        CLI
                    </Box>
                    <Box
                        component="span"
                        sx={{fontWeight: 700, color: entry.command?.exitCode === 0 ? 'success.main' : 'error.main'}}
                    >
                        exit {entry.command?.exitCode}
                    </Box>
                    <Box
                        component="span"
                        sx={{
                            color: 'text.secondary',
                            flex: 1,
                            overflow: 'hidden',
                            textOverflow: 'ellipsis',
                            whiteSpace: 'nowrap',
                        }}
                    >
                        {entry.command?.input}
                    </Box>
                </>
            )}
            {timing && (
                <Box component="span" sx={{color: 'text.disabled', flexShrink: 0}}>
                    {formatTime(timing.request.processingTime)}
                </Box>
            )}
        </Box>
    );
};

/** Side rail metric row */
export const SideMetricRow = ({
    icon,
    label,
    value,
    color,
    onClick,
}: {
    icon: string;
    label: string;
    value: string | number;
    color?: string;
    onClick?: () => void;
}) => (
    <Box
        onClick={onClick}
        sx={{
            display: 'flex',
            alignItems: 'center',
            px: 1.75,
            py: 0.75,
            gap: 1.25,
            cursor: onClick ? 'pointer' : 'default',
            borderLeft: '3px solid transparent',
            borderBottom: 1,
            borderColor: 'divider',
            transition: 'background 100ms ease',
            '&:hover': onClick ? {bgcolor: 'primary.light', borderLeftColor: 'primary.main'} : {},
        }}
    >
        <Box sx={{fontSize: 13, width: 20, textAlign: 'center', flexShrink: 0}}>{icon}</Box>
        <Box sx={{fontSize: 13, color: 'text.secondary', flex: 1}}>{label}</Box>
        <Box
            sx={{
                fontFamily: "'JetBrains Mono', monospace",
                fontSize: 13,
                fontWeight: 500,
                color: color ?? 'text.primary',
            }}
        >
            {value}
        </Box>
    </Box>
);

/** All side metrics for an entry */
export const SideMetrics = ({entry}: {entry: DebugEntry}) => {
    const timing = entry.web || entry.console;

    return (
        <Box sx={{flex: 1, overflowY: 'auto'}}>
            {timing && (
                <SideMetricRow icon="\u23F1" label="Response time" value={formatTime(timing.request.processingTime)} />
            )}
            {timing && (
                <SideMetricRow icon="\uD83D\uDCBE" label="Peak memory" value={formatMemory(timing.memory.peakUsage)} />
            )}
            {entry.db && <SideMetricRow icon="\uD83D\uDDC4" label="DB queries" value={entry.db.queries.total} />}
            {entry.http && entry.http.count > 0 && (
                <SideMetricRow icon="\uD83C\uDF10" label="HTTP requests" value={entry.http.count} />
            )}
            {entry.logger && entry.logger.total > 0 && (
                <SideMetricRow icon="\uD83D\uDCCB" label="Log entries" value={entry.logger.total} />
            )}
            {entry.event && entry.event.total > 0 && (
                <SideMetricRow icon="\u26A1" label="Events fired" value={entry.event.total} />
            )}
            {entry.deprecation && entry.deprecation.total > 0 && (
                <SideMetricRow
                    icon="\u26A0\uFE0F"
                    label="Deprecations"
                    value={entry.deprecation.total}
                    color="#D97706"
                />
            )}
            {entry.exception && (
                <SideMetricRow icon="\uD83D\uDCA5" label="Exception" value={entry.exception.class} color="#DC2626" />
            )}
            {entry.validator && (
                <SideMetricRow
                    icon="\u2705"
                    label="Validation"
                    value={entry.validator.invalid > 0 ? `${entry.validator.invalid} invalid` : 'OK'}
                    color={entry.validator.invalid > 0 ? '#D97706' : '#16A34A'}
                />
            )}
            {entry.router && <SideMetricRow icon="\uD83D\uDD00" label="Route" value={entry.router.name} />}
        </Box>
    );
};
