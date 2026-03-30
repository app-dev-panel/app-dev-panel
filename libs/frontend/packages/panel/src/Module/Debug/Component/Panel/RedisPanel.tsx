import {EmptyState} from '@app-dev-panel/sdk/Component/EmptyState';
import {FilterInput} from '@app-dev-panel/sdk/Component/FilterInput';
import {JsonRenderer} from '@app-dev-panel/sdk/Component/JsonRenderer';
import {SectionTitle} from '@app-dev-panel/sdk/Component/SectionTitle';
import {primitives} from '@app-dev-panel/sdk/Component/Theme/tokens';
import {Box, Chip, Collapse, Icon, Tooltip, Typography} from '@mui/material';
import {styled, type Theme, useTheme} from '@mui/material/styles';
import {useDeferredValue, useMemo, useState} from 'react';

type RedisCommand = {
    connection: string;
    command: string;
    arguments: unknown[];
    result: unknown;
    duration: number;
    error: string | null;
    line: string;
};

type RedisData = {
    commands: RedisCommand[];
    totalTime: number;
    errorCount: number;
    totalCommands: number;
    connections: string[];
};

type RedisPanelProps = {data: RedisData};

// ---------------------------------------------------------------------------
// Styled components
// ---------------------------------------------------------------------------

const SummaryGrid = styled(Box)(({theme}) => ({
    display: 'grid',
    gridTemplateColumns: 'repeat(auto-fill, minmax(160px, 1fr))',
    gap: theme.spacing(2),
    marginBottom: theme.spacing(3),
}));

const SummaryCard = styled(Box)(({theme}) => ({
    padding: theme.spacing(2),
    borderRadius: theme.shape.borderRadius * 1.5,
    border: `1px solid ${theme.palette.divider}`,
    backgroundColor: theme.palette.background.paper,
}));

const SummaryLabel = styled(Typography)(({theme}) => ({
    fontSize: '11px',
    fontWeight: 600,
    textTransform: 'uppercase' as const,
    letterSpacing: '0.5px',
    color: theme.palette.text.disabled,
    marginBottom: theme.spacing(0.5),
}));

const SummaryValue = styled(Typography)({fontFamily: primitives.fontFamilyMono, fontWeight: 700, fontSize: '22px'});

const CommandRow = styled(Box, {shouldForwardProp: (p) => p !== 'expanded'})<{expanded?: boolean}>(
    ({theme, expanded}) => ({
        display: 'flex',
        alignItems: 'center',
        gap: theme.spacing(1.5),
        padding: theme.spacing(1, 1.5),
        borderBottom: `1px solid ${theme.palette.divider}`,
        cursor: 'pointer',
        transition: 'background-color 0.1s ease',
        backgroundColor: expanded ? theme.palette.action.hover : 'transparent',
        '&:hover': {backgroundColor: theme.palette.action.hover},
    }),
);

const KeyCell = styled(Typography)({
    fontFamily: primitives.fontFamilyMono,
    fontSize: '12px',
    flex: 1,
    wordBreak: 'break-all',
    minWidth: 0,
});

const DurationCell = styled(Typography)({
    fontFamily: primitives.fontFamilyMono,
    fontSize: '11px',
    flexShrink: 0,
    width: 80,
    textAlign: 'right',
});

const ConnectionChip = styled(Chip)(({theme}) => ({
    fontWeight: 600,
    fontSize: '10px',
    height: 20,
    borderRadius: theme.shape.borderRadius * 0.5,
}));

const DetailBox = styled(Box)(({theme}) => ({
    padding: theme.spacing(1.5, 2, 1.5, 5.5),
    borderBottom: `1px solid ${theme.palette.divider}`,
    backgroundColor: theme.palette.action.hover,
}));

const DetailLabel = styled(Typography)({
    fontSize: '11px',
    fontWeight: 600,
    textTransform: 'uppercase' as const,
    letterSpacing: '0.5px',
});

// ---------------------------------------------------------------------------
// Helpers
// ---------------------------------------------------------------------------

const formatDuration = (seconds: number): string => {
    if (seconds === 0) return '0 ms';
    if (seconds < 0.001) return `${(seconds * 1000000).toFixed(0)} us`;
    if (seconds < 1) return `${(seconds * 1000).toFixed(2)} ms`;
    return `${seconds.toFixed(3)} s`;
};

const READ_COMMANDS = ['GET', 'MGET', 'HGET', 'HGETALL', 'LRANGE', 'SMEMBERS', 'ZRANGE'];
const WRITE_COMMANDS = ['SET', 'MSET', 'HSET', 'LPUSH', 'RPUSH', 'SADD', 'ZADD', 'SETEX', 'SETNX'];
const DELETE_COMMANDS = ['DEL', 'HDEL', 'LREM', 'SREM', 'ZREM', 'UNLINK'];

const commandIcon = (cmd: string): string => {
    const upper = cmd.toUpperCase();
    if (READ_COMMANDS.includes(upper)) return 'search';
    if (WRITE_COMMANDS.includes(upper)) return 'edit';
    if (DELETE_COMMANDS.includes(upper)) return 'delete';
    if (['EXISTS', 'HEXISTS', 'SISMEMBER', 'TYPE', 'TTL', 'PTTL'].includes(upper)) return 'help_outline';
    if (['SUBSCRIBE', 'PUBLISH', 'PSUBSCRIBE'].includes(upper)) return 'campaign';
    if (['EXPIRE', 'EXPIREAT', 'PERSIST', 'PEXPIRE'].includes(upper)) return 'timer';
    if (['INCR', 'INCRBY', 'DECR', 'DECRBY', 'INCRBYFLOAT'].includes(upper)) return 'calculate';
    if (['PING', 'ECHO', 'INFO', 'DBSIZE', 'FLUSHDB'].includes(upper)) return 'settings';
    if (['MULTI', 'EXEC', 'DISCARD', 'WATCH'].includes(upper)) return 'lock';
    if (['EVAL', 'EVALSHA'].includes(upper)) return 'code';
    return 'memory';
};

const commandColor = (cmd: RedisCommand, theme: Theme): {bg: string; fg: string} => {
    if (cmd.error) return {bg: theme.palette.error.light, fg: theme.palette.error.main};
    const upper = cmd.command.toUpperCase();
    if (READ_COMMANDS.includes(upper)) return {bg: theme.palette.info.light, fg: theme.palette.info.main};
    if (WRITE_COMMANDS.includes(upper)) return {bg: theme.palette.success.light, fg: theme.palette.success.main};
    if (DELETE_COMMANDS.includes(upper)) return {bg: theme.palette.warning.light, fg: theme.palette.warning.main};
    return {bg: theme.palette.action.hover, fg: theme.palette.text.secondary};
};

const formatArgsSummary = (args: unknown[]): string => {
    if (args.length === 0) return '';
    const parts = args.map((a) => (typeof a === 'string' ? a : JSON.stringify(a)));
    const joined = parts.join(' ');
    return joined.length > 80 ? joined.slice(0, 80) + '...' : joined;
};

// ---------------------------------------------------------------------------
// Connection breakdown sub-component
// ---------------------------------------------------------------------------

type ConnectionStat = {connection: string; commands: number; errors: number; totalTime: number};

const ConnectionBreakdown = ({commands}: {commands: RedisCommand[]}) => {
    const theme = useTheme();
    const stats = useMemo(() => {
        const map = new Map<string, ConnectionStat>();
        for (const cmd of commands) {
            let stat = map.get(cmd.connection);
            if (!stat) {
                stat = {connection: cmd.connection, commands: 0, errors: 0, totalTime: 0};
                map.set(cmd.connection, stat);
            }
            stat.commands++;
            stat.totalTime += cmd.duration;
            if (cmd.error) stat.errors++;
        }
        return [...map.values()];
    }, [commands]);

    if (stats.length <= 1) return null;

    return (
        <Box sx={{mb: 3}}>
            <SectionTitle>Connections</SectionTitle>
            <Box sx={{display: 'flex', gap: 2, flexWrap: 'wrap', mt: 1}}>
                {stats.map((s) => (
                    <Box
                        key={s.connection}
                        sx={{
                            flex: '1 1 200px',
                            p: 1.5,
                            borderRadius: 1.5,
                            border: `1px solid ${theme.palette.divider}`,
                            backgroundColor: theme.palette.background.paper,
                        }}
                    >
                        <Typography sx={{fontWeight: 600, fontSize: '13px', mb: 0.5}}>{s.connection}</Typography>
                        <Box sx={{display: 'flex', gap: 2, fontSize: '12px', color: 'text.secondary'}}>
                            <span>{s.commands} cmds</span>
                            <span>{formatDuration(s.totalTime)}</span>
                            {s.errors > 0 && <span style={{color: theme.palette.error.main}}>{s.errors} errors</span>}
                        </Box>
                    </Box>
                ))}
            </Box>
        </Box>
    );
};

// ---------------------------------------------------------------------------
// RedisPanel
// ---------------------------------------------------------------------------

export const RedisPanel = ({data}: RedisPanelProps) => {
    const theme = useTheme();
    const [filter, setFilter] = useState('');
    const deferredFilter = useDeferredValue(filter);
    const [expandedIndex, setExpandedIndex] = useState<number | null>(null);

    if (!data || data.totalCommands === 0) {
        return (
            <EmptyState
                icon="memory"
                title="No Redis commands"
                description="No Redis commands were recorded during this request."
            />
        );
    }

    const {commands, totalTime, errorCount, totalCommands} = data;

    const filtered = useMemo(
        () =>
            deferredFilter
                ? commands.filter((cmd) => {
                      const lower = deferredFilter.toLowerCase();
                      return (
                          cmd.command.toLowerCase().includes(lower) ||
                          cmd.connection.toLowerCase().includes(lower) ||
                          formatArgsSummary(cmd.arguments).toLowerCase().includes(lower)
                      );
                  })
                : commands,
        [commands, deferredFilter],
    );

    return (
        <Box>
            {/* Summary cards */}
            <SummaryGrid>
                <SummaryCard>
                    <SummaryLabel>Commands</SummaryLabel>
                    <SummaryValue sx={{color: 'primary.main'}}>{totalCommands}</SummaryValue>
                </SummaryCard>
                <SummaryCard>
                    <SummaryLabel>Total Time</SummaryLabel>
                    <SummaryValue sx={{color: 'text.primary', fontSize: '18px'}}>
                        {formatDuration(totalTime)}
                    </SummaryValue>
                </SummaryCard>
                <SummaryCard>
                    <SummaryLabel>Errors</SummaryLabel>
                    <SummaryValue sx={{color: errorCount > 0 ? 'error.main' : 'text.disabled'}}>
                        {errorCount}
                    </SummaryValue>
                </SummaryCard>
                <SummaryCard>
                    <SummaryLabel>Connections</SummaryLabel>
                    <SummaryValue sx={{color: 'text.primary'}}>{data.connections.length}</SummaryValue>
                </SummaryCard>
            </SummaryGrid>

            {/* Connection breakdown */}
            <ConnectionBreakdown commands={commands} />

            {/* Commands table */}
            <SectionTitle
                action={<FilterInput value={filter} onChange={setFilter} placeholder="Filter commands..." />}
            >{`${filtered.length} commands`}</SectionTitle>

            {filtered.map((cmd, index) => {
                const expanded = expandedIndex === index;
                const colors = commandColor(cmd, theme);
                return (
                    <Box key={index}>
                        <CommandRow expanded={expanded} onClick={() => setExpandedIndex(expanded ? null : index)}>
                            <Tooltip title={cmd.command} placement="top">
                                <Icon sx={{fontSize: 16, color: 'text.disabled', flexShrink: 0}}>
                                    {commandIcon(cmd.command)}
                                </Icon>
                            </Tooltip>
                            <Chip
                                label={cmd.command.toUpperCase()}
                                size="small"
                                sx={{
                                    fontWeight: 600,
                                    fontSize: '10px',
                                    height: 20,
                                    minWidth: 50,
                                    borderRadius: 0.5,
                                    backgroundColor: colors.bg,
                                    color: colors.fg,
                                }}
                            />
                            {cmd.error && (
                                <Chip
                                    label="ERR"
                                    size="small"
                                    sx={{
                                        fontWeight: 700,
                                        fontSize: '9px',
                                        height: 18,
                                        minWidth: 36,
                                        borderRadius: 0.5,
                                        backgroundColor: theme.palette.error.main,
                                        color: 'common.white',
                                    }}
                                />
                            )}
                            {data.connections.length > 1 && (
                                <ConnectionChip label={cmd.connection} size="small" variant="outlined" />
                            )}
                            <KeyCell sx={{color: 'text.primary'}}>{formatArgsSummary(cmd.arguments)}</KeyCell>
                            <DurationCell sx={{color: 'text.disabled'}}>{formatDuration(cmd.duration)}</DurationCell>
                            <Icon sx={{fontSize: 16, color: 'text.disabled', flexShrink: 0}}>
                                {expanded ? 'expand_less' : 'expand_more'}
                            </Icon>
                        </CommandRow>
                        <Collapse in={expanded}>
                            <DetailBox>
                                {cmd.arguments.length > 0 && (
                                    <Box sx={{mb: 1}}>
                                        <DetailLabel sx={{color: 'text.disabled', mb: 0.5}}>Arguments</DetailLabel>
                                        <JsonRenderer value={cmd.arguments} depth={3} />
                                    </Box>
                                )}
                                {cmd.result !== null && cmd.result !== undefined && (
                                    <Box sx={{mb: 1}}>
                                        <DetailLabel sx={{color: 'text.disabled', mb: 0.5}}>Result</DetailLabel>
                                        <JsonRenderer value={cmd.result} depth={3} />
                                    </Box>
                                )}
                                {cmd.error && (
                                    <Box sx={{mb: 1}}>
                                        <DetailLabel sx={{color: 'error.main', mb: 0.5}}>Error</DetailLabel>
                                        <Typography
                                            sx={{
                                                fontFamily: primitives.fontFamilyMono,
                                                fontSize: '12px',
                                                color: 'error.main',
                                            }}
                                        >
                                            {cmd.error}
                                        </Typography>
                                    </Box>
                                )}
                                {cmd.line && (
                                    <Box>
                                        <DetailLabel sx={{color: 'text.disabled', mb: 0.5}}>Source</DetailLabel>
                                        <Typography
                                            sx={{
                                                fontFamily: primitives.fontFamilyMono,
                                                fontSize: '11px',
                                                color: 'text.secondary',
                                            }}
                                        >
                                            {cmd.line}
                                        </Typography>
                                    </Box>
                                )}
                            </DetailBox>
                        </Collapse>
                    </Box>
                );
            })}
        </Box>
    );
};
