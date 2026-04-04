import {VarDumpValue} from '@app-dev-panel/panel/Module/Debug/Component/VarDumpValue';
import {
    clearLiveEntries,
    type LiveEntry,
    toggleLivePaused,
    useLiveEntries,
    useLivePaused,
} from '@app-dev-panel/sdk/API/Debug/LiveContext';
import {EmptyState} from '@app-dev-panel/sdk/Component/EmptyState';
import {FilterInput} from '@app-dev-panel/sdk/Component/FilterInput';
import {SectionTitle} from '@app-dev-panel/sdk/Component/SectionTitle';
import {searchVariants} from '@app-dev-panel/sdk/Helper/layoutTranslit';
import {Box, Chip, Collapse, Icon, IconButton, type Theme, Tooltip, Typography} from '@mui/material';
import {styled, useTheme} from '@mui/material/styles';
import {useDeferredValue, useMemo, useState} from 'react';
import {useDispatch} from 'react-redux';

// ---------------------------------------------------------------------------
// Types
// ---------------------------------------------------------------------------

type EntryKind = 'log' | 'dump';

// ---------------------------------------------------------------------------
// Color helpers
// ---------------------------------------------------------------------------

const levelColor = (level: string, theme: Theme): string => {
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
            return theme.palette.text.disabled;
        default:
            return theme.palette.text.disabled;
    }
};

const kindColor = (kind: EntryKind, theme: Theme): string =>
    kind === 'log' ? theme.palette.primary.main : (theme.palette.info?.main ?? theme.palette.primary.main);

// ---------------------------------------------------------------------------
// Styled components
// ---------------------------------------------------------------------------

const EntryRow = styled(Box, {shouldForwardProp: (p) => p !== 'expanded' && p !== 'borderColor'})<{
    expanded?: boolean;
    borderColor?: string;
}>(({theme, expanded, borderColor}) => ({
    display: 'flex',
    alignItems: 'flex-start',
    gap: theme.spacing(1.5),
    padding: theme.spacing(1, 1.5),
    borderBottom: `1px solid ${theme.palette.divider}`,
    borderLeft: `3px solid ${borderColor || 'transparent'}`,
    cursor: 'pointer',
    transition: 'background-color 0.1s ease',
    backgroundColor: expanded ? theme.palette.action.hover : 'transparent',
    '&:hover': {backgroundColor: theme.palette.action.hover},
}));

const TimeCell = styled(Typography)(({theme}) => ({
    fontFamily: theme.adp.fontFamilyMono,
    fontSize: '11px',
    flexShrink: 0,
    width: 80,
    paddingTop: 2,
}));

const MessageCell = styled(Typography)({fontSize: '13px', flex: 1, wordBreak: 'break-word', minWidth: 0});

const DetailBox = styled(Box)(({theme}) => ({
    padding: theme.spacing(1.5, 1.5, 1.5, 15),
    backgroundColor: theme.palette.action.hover,
    borderBottom: `1px solid ${theme.palette.divider}`,
    fontSize: '12px',
}));

const DumpBody = styled(Box)(({theme}) => ({
    padding: theme.spacing(1.5, 2),
    backgroundColor: theme.palette.mode === 'dark' ? theme.palette.background.default : theme.palette.grey[50],
    borderRadius: theme.shape.borderRadius,
    overflow: 'auto',
}));

const PulsingDot = styled(Box)(({theme}) => ({
    width: 8,
    height: 8,
    borderRadius: '50%',
    backgroundColor: theme.palette.success.main,
    animation: 'pulse 2s ease-in-out infinite',
    '@keyframes pulse': {'0%, 100%': {opacity: 1}, '50%': {opacity: 0.3}},
}));

// ---------------------------------------------------------------------------
// Helpers
// ---------------------------------------------------------------------------

const formatTime = (timestamp: number): string => {
    const d = new Date(timestamp);
    return d.toLocaleTimeString('en-US', {hour12: false, hour: '2-digit', minute: '2-digit', second: '2-digit'});
};

const formatMessage = (message: unknown): string => (typeof message === 'string' ? message : JSON.stringify(message));

// ---------------------------------------------------------------------------
// Component
// ---------------------------------------------------------------------------

export const LivePage = () => {
    const theme = useTheme();
    const dispatch = useDispatch();
    const entries = useLiveEntries();
    const paused = useLivePaused();
    const [filter, setFilter] = useState('');
    const deferredFilter = useDeferredValue(filter);
    const [expandedId, setExpandedId] = useState<string | null>(null);
    const [activeKinds, setActiveKinds] = useState<Set<EntryKind>>(new Set());

    // Kind counts
    const kindCounts = useMemo(() => {
        const counts: Record<EntryKind, number> = {log: 0, dump: 0};
        for (const entry of entries) counts[entry.kind]++;
        return counts;
    }, [entries]);

    // Filter entries
    const filtered = useMemo(() => {
        let result: LiveEntry[] = entries;

        if (activeKinds.size > 0) {
            result = result.filter((e) => activeKinds.has(e.kind));
        }

        if (deferredFilter) {
            const variants = searchVariants(deferredFilter.toLowerCase());
            result = result.filter((e) => {
                if (e.kind === 'log') {
                    const msg = formatMessage(e.payload.message).toLowerCase();
                    const lvl = e.payload.level.toLowerCase();
                    return variants.some((v) => msg.includes(v) || lvl.includes(v));
                }
                const val = JSON.stringify(e.payload.variable).toLowerCase();
                const line = (e.payload.line ?? '').toLowerCase();
                return variants.some((v) => val.includes(v) || line.includes(v));
            });
        }

        return result;
    }, [entries, activeKinds, deferredFilter]);

    const toggleKind = (kind: EntryKind) => {
        setActiveKinds((prev) => {
            const next = new Set(prev);
            if (next.has(kind)) {
                next.delete(kind);
            } else {
                next.add(kind);
            }
            return next;
        });
    };

    // ---------------------------------------------------------------------------
    // Render
    // ---------------------------------------------------------------------------

    return (
        <Box>
            <SectionTitle
                action={
                    <Box sx={{display: 'flex', alignItems: 'center', gap: 1}}>
                        <FilterInput value={filter} onChange={setFilter} placeholder="Filter..." />
                        <Tooltip title={paused ? 'Resume' : 'Pause'}>
                            <IconButton size="small" onClick={() => dispatch(toggleLivePaused())}>
                                <Icon sx={{fontSize: 18}}>{paused ? 'play_arrow' : 'pause'}</Icon>
                            </IconButton>
                        </Tooltip>
                        <Tooltip title="Clear">
                            <IconButton
                                size="small"
                                onClick={() => dispatch(clearLiveEntries())}
                                disabled={entries.length === 0}
                            >
                                <Icon sx={{fontSize: 18}}>delete_sweep</Icon>
                            </IconButton>
                        </Tooltip>
                    </Box>
                }
            >
                <Box sx={{display: 'flex', alignItems: 'center', gap: 1}}>
                    {!paused && <PulsingDot />}
                    {paused && <Icon sx={{fontSize: 14, color: 'warning.main'}}>pause_circle</Icon>}
                    {`Live Feed (${filtered.length})`}
                </Box>
            </SectionTitle>

            {/* Kind filter chips */}
            {entries.length > 0 && (kindCounts.log > 0 || kindCounts.dump > 0) && (
                <Box sx={{display: 'flex', flexWrap: 'wrap', gap: 0.75, mb: 1.5}}>
                    {(['log', 'dump'] as EntryKind[])
                        .filter((k) => kindCounts[k] > 0)
                        .map((kind) => {
                            const color = kindColor(kind, theme);
                            const isActive = activeKinds.has(kind);
                            const label = kind === 'log' ? 'Logs' : 'Dumps';
                            const icon = kind === 'log' ? 'description' : 'data_object';
                            return (
                                <Chip
                                    key={kind}
                                    icon={<Icon sx={{fontSize: '14px !important'}}>{icon}</Icon>}
                                    label={`${label} (${kindCounts[kind]})`}
                                    size="small"
                                    onClick={() => toggleKind(kind)}
                                    variant={isActive ? 'filled' : 'outlined'}
                                    sx={{
                                        height: 28,
                                        borderRadius: 1,
                                        fontWeight: 600,
                                        cursor: 'pointer',
                                        borderColor: color,
                                        ...(isActive
                                            ? {
                                                  backgroundColor: color,
                                                  color: theme.palette.common.white,
                                                  '& .MuiIcon-root': {color: theme.palette.common.white},
                                              }
                                            : {color, '& .MuiIcon-root': {color}}),
                                    }}
                                />
                            );
                        })}
                    {activeKinds.size > 0 && (
                        <Chip
                            label="Clear"
                            size="small"
                            onClick={() => setActiveKinds(new Set())}
                            variant="outlined"
                            sx={{height: 28, borderRadius: 1, fontSize: '11px'}}
                        />
                    )}
                </Box>
            )}

            {/* Empty state */}
            {entries.length === 0 && (
                <EmptyState
                    icon="stream"
                    title="No live events yet"
                    description="Live logs and dumps will appear here as they are broadcast from your application."
                />
            )}

            {/* Entries */}
            {filtered.map((entry) => {
                const expanded = expandedId === entry.id;
                const border = kindColor(entry.kind, theme);

                if (entry.kind === 'log') {
                    const color = levelColor(entry.payload.level, theme);
                    return (
                        <Box key={entry.id}>
                            <EntryRow
                                expanded={expanded}
                                borderColor={border}
                                onClick={() => setExpandedId(expanded ? null : entry.id)}
                            >
                                <TimeCell sx={{color: 'text.disabled'}}>{formatTime(entry.timestamp)}</TimeCell>
                                <Chip
                                    label={entry.payload.level.toUpperCase()}
                                    size="small"
                                    sx={{
                                        fontWeight: 600,
                                        fontSize: '10px',
                                        height: 20,
                                        minWidth: 60,
                                        backgroundColor: color,
                                        color: 'common.white',
                                        borderRadius: 1,
                                    }}
                                />
                                <MessageCell>{formatMessage(entry.payload.message)}</MessageCell>
                                <IconButton size="small" sx={{flexShrink: 0}}>
                                    <Icon sx={{fontSize: 16}}>{expanded ? 'expand_less' : 'expand_more'}</Icon>
                                </IconButton>
                            </EntryRow>
                            <Collapse in={expanded}>
                                <DetailBox>
                                    {entry.payload.context && Object.keys(entry.payload.context).length > 0 && (
                                        <Box>
                                            <Typography
                                                variant="caption"
                                                sx={{
                                                    fontWeight: 600,
                                                    mb: 0.5,
                                                    display: 'block',
                                                    color: 'text.disabled',
                                                }}
                                            >
                                                Context
                                            </Typography>
                                            <Box
                                                component="pre"
                                                sx={(theme) => ({
                                                    fontFamily: theme.adp.fontFamilyMono,
                                                    fontSize: '12px',
                                                    m: 0,
                                                    whiteSpace: 'pre-wrap',
                                                    wordBreak: 'break-word',
                                                })}
                                            >
                                                {JSON.stringify(entry.payload.context, null, 2)}
                                            </Box>
                                        </Box>
                                    )}
                                </DetailBox>
                            </Collapse>
                        </Box>
                    );
                }

                // Dump
                return (
                    <Box key={entry.id}>
                        <EntryRow
                            expanded={expanded}
                            borderColor={border}
                            onClick={() => setExpandedId(expanded ? null : entry.id)}
                        >
                            <TimeCell sx={{color: 'text.disabled'}}>{formatTime(entry.timestamp)}</TimeCell>
                            <Chip
                                label="DUMP"
                                size="small"
                                sx={{
                                    fontWeight: 600,
                                    fontSize: '10px',
                                    height: 20,
                                    minWidth: 60,
                                    backgroundColor: theme.palette.info?.main ?? theme.palette.primary.main,
                                    color: 'common.white',
                                    borderRadius: 1,
                                }}
                            />
                            <MessageCell>
                                {entry.payload.line && (
                                    <Typography
                                        component="span"
                                        sx={(theme) => ({fontFamily: theme.adp.fontFamilyMono, fontSize: '12px'})}
                                    >
                                        {entry.payload.line}
                                    </Typography>
                                )}
                                {!entry.payload.line && (
                                    <Typography component="span" sx={{fontSize: '12px', color: 'text.secondary'}}>
                                        var_dump()
                                    </Typography>
                                )}
                            </MessageCell>
                            <IconButton size="small" sx={{flexShrink: 0}}>
                                <Icon sx={{fontSize: 16}}>{expanded ? 'expand_less' : 'expand_more'}</Icon>
                            </IconButton>
                        </EntryRow>
                        <Collapse in={expanded}>
                            <DetailBox>
                                <DumpBody>
                                    <VarDumpValue value={entry.payload.variable} />
                                </DumpBody>
                            </DetailBox>
                        </Collapse>
                    </Box>
                );
            })}
        </Box>
    );
};
