import {VarDumpValue} from '@app-dev-panel/panel/Module/Debug/Component/VarDumpValue';
import {
    clearLiveEntries,
    type LiveDumpEntry,
    type LiveEntry,
    type LiveLogEntry,
    useLiveEntries,
} from '@app-dev-panel/sdk/API/Debug/LiveContext';
import {EmptyState} from '@app-dev-panel/sdk/Component/EmptyState';
import {FileLink} from '@app-dev-panel/sdk/Component/FileLink';
import {componentTokens} from '@app-dev-panel/sdk/Component/Theme/tokens';
import {Box, Chip, Collapse, Divider, Icon, IconButton, type Theme, Tooltip, Typography} from '@mui/material';
import {styled, useTheme} from '@mui/material/styles';
import React, {useCallback, useMemo, useState} from 'react';
import {useDispatch} from 'react-redux';

const PANEL_WIDTH = 380;

export {PANEL_WIDTH as LIVE_FEED_PANEL_WIDTH};

const PanelRoot = styled(Box)(({theme}) => ({
    width: PANEL_WIDTH,
    flexShrink: 0,
    display: 'flex',
    flexDirection: 'column',
    borderRadius: componentTokens.contentPanel.borderRadius,
    backgroundColor: theme.palette.background.paper,
    border: `1px solid ${theme.palette.divider}`,
    overflow: 'hidden',
}));

const PanelHeader = styled(Box)(({theme}) => ({
    display: 'flex',
    alignItems: 'center',
    justifyContent: 'space-between',
    padding: theme.spacing(1.5, 2),
    flexShrink: 0,
}));

const EventRow = styled(Box, {shouldForwardProp: (p) => p !== 'expanded'})<{expanded?: boolean}>(
    ({theme, expanded}) => ({
        padding: theme.spacing(1, 2),
        borderBottom: `1px solid ${theme.palette.divider}`,
        cursor: 'pointer',
        transition: 'background-color 0.1s ease',
        backgroundColor: expanded ? theme.palette.action.hover : 'transparent',
        '&:hover': {backgroundColor: theme.palette.action.hover},
    }),
);

const TimeLabel = styled(Typography)(({theme}) => ({
    fontFamily: theme.adp.fontFamilyMono,
    fontSize: '10px',
    color: theme.palette.text.disabled,
    flexShrink: 0,
}));

const MessageText = styled(Typography)({fontSize: '12px', flex: 1, wordBreak: 'break-word', lineHeight: 1.5});

const DetailBox = styled(Box)(({theme}) => ({
    padding: theme.spacing(1, 2, 1, 4),
    backgroundColor: theme.palette.action.hover,
    fontSize: '12px',
    overflow: 'auto',
}));

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

const formatTime = (timestamp: number): string => {
    const d = new Date(timestamp);
    return d.toLocaleTimeString('en-GB', {
        hour: '2-digit',
        minute: '2-digit',
        second: '2-digit',
        fractionalSecondDigits: 3,
    });
};

const LogEventItem = React.memo(({entry}: {entry: LiveLogEntry}) => {
    const theme = useTheme();
    const [expanded, setExpanded] = useState(false);
    const level = entry.payload.level ?? 'debug';
    const color = levelColor(level, theme);
    const hasContext = entry.payload.context && Object.keys(entry.payload.context).length > 0;

    return (
        <>
            <EventRow expanded={expanded} onClick={() => hasContext && setExpanded(!expanded)}>
                <Box sx={{display: 'flex', alignItems: 'center', gap: 1, mb: 0.5}}>
                    <TimeLabel>{formatTime(entry.timestamp)}</TimeLabel>
                    <Chip
                        label={level.toUpperCase()}
                        size="small"
                        sx={{
                            fontWeight: 600,
                            fontSize: '9px',
                            height: 18,
                            minWidth: 48,
                            backgroundColor: color,
                            color: 'common.white',
                            borderRadius: 0.5,
                        }}
                    />
                    {hasContext && (
                        <Icon sx={{fontSize: 14, color: 'text.disabled', ml: 'auto'}}>
                            {expanded ? 'expand_less' : 'expand_more'}
                        </Icon>
                    )}
                </Box>
                <MessageText>{entry.payload.message}</MessageText>
            </EventRow>
            {hasContext && (
                <Collapse in={expanded}>
                    <DetailBox>
                        <VarDumpValue value={entry.payload.context} depth={0} defaultExpanded />
                    </DetailBox>
                </Collapse>
            )}
        </>
    );
});

const DumpEventItem = React.memo(({entry}: {entry: LiveDumpEntry}) => {
    const theme = useTheme();
    const [expanded, setExpanded] = useState(true);

    return (
        <>
            <EventRow expanded={expanded} onClick={() => setExpanded(!expanded)}>
                <Box sx={{display: 'flex', alignItems: 'center', gap: 1, mb: 0.5}}>
                    <TimeLabel>{formatTime(entry.timestamp)}</TimeLabel>
                    <Chip
                        label="DUMP"
                        size="small"
                        sx={{
                            fontWeight: 600,
                            fontSize: '9px',
                            height: 18,
                            minWidth: 48,
                            backgroundColor: theme.palette.warning.main,
                            color: 'common.white',
                            borderRadius: 0.5,
                        }}
                    />
                    <Icon sx={{fontSize: 14, color: 'text.disabled', ml: 'auto'}}>
                        {expanded ? 'expand_less' : 'expand_more'}
                    </Icon>
                </Box>
                {entry.payload.line && (
                    <FileLink path={entry.payload.line}>
                        <Typography
                            component="span"
                            sx={(t) => ({
                                fontFamily: t.adp.fontFamilyMono,
                                fontSize: '11px',
                                color: 'primary.main',
                                textDecoration: 'none',
                                '&:hover': {textDecoration: 'underline'},
                            })}
                        >
                            {entry.payload.line}
                        </Typography>
                    </FileLink>
                )}
            </EventRow>
            <Collapse in={expanded}>
                <DetailBox>
                    <VarDumpValue value={entry.payload.variable} depth={0} defaultExpanded />
                </DetailBox>
            </Collapse>
        </>
    );
});

const FeedEventItem = React.memo(({entry}: {entry: LiveEntry}) => {
    if (entry.kind === 'log') {
        return <LogEventItem entry={entry} />;
    }
    return <DumpEventItem entry={entry} />;
});

type FilterTag = 'dump' | 'debug' | 'info' | 'notice' | 'warning' | 'error';

const filterDefs: {tag: FilterTag; label: string; color: (t: Theme) => string}[] = [
    {tag: 'error', label: 'Error', color: (t) => t.palette.error.main},
    {tag: 'warning', label: 'Warning', color: (t) => t.palette.warning.main},
    {tag: 'info', label: 'Info', color: (t) => t.palette.success.main},
    {tag: 'debug', label: 'Debug', color: (t) => t.palette.text.disabled},
    {tag: 'dump', label: 'Dump', color: (t) => t.palette.info?.main ?? t.palette.primary.main},
];

const errorLevels = new Set(['error', 'critical', 'alert', 'emergency']);

const entryMatchesTag = (entry: LiveEntry, tag: FilterTag): boolean => {
    if (tag === 'dump') return entry.kind === 'dump';
    if (entry.kind !== 'log') return false;
    if (tag === 'error') return errorLevels.has(entry.payload.level);
    return entry.payload.level === tag;
};

type LiveFeedPanelProps = {onClose: () => void};

export const LiveFeedPanel = React.memo(({onClose}: LiveFeedPanelProps) => {
    const entries = useLiveEntries();
    const dispatch = useDispatch();
    const theme = useTheme();
    const [activeFilters, setActiveFilters] = useState<Set<FilterTag>>(new Set());

    const handleClear = useCallback(() => {
        dispatch(clearLiveEntries());
    }, [dispatch]);

    const toggleFilter = useCallback((tag: FilterTag) => {
        setActiveFilters((prev) => {
            const next = new Set(prev);
            if (next.has(tag)) {
                next.delete(tag);
            } else {
                next.add(tag);
            }
            return next;
        });
    }, []);

    const tagCounts = useMemo(() => {
        const counts: Record<FilterTag, number> = {dump: 0, debug: 0, info: 0, notice: 0, warning: 0, error: 0};
        for (const entry of entries) {
            if (entry.kind === 'dump') {
                counts.dump++;
            } else {
                const lvl = entry.payload.level;
                if (errorLevels.has(lvl)) counts.error++;
                else if (lvl in counts) counts[lvl as FilterTag]++;
            }
        }
        return counts;
    }, [entries]);

    const filtered = useMemo(() => {
        if (activeFilters.size === 0) return entries;
        return entries.filter((e) => {
            for (const tag of activeFilters) {
                if (entryMatchesTag(e, tag)) return true;
            }
            return false;
        });
    }, [entries, activeFilters]);

    const visibleFilterDefs = filterDefs.filter((f) => tagCounts[f.tag] > 0);

    return (
        <PanelRoot>
            <PanelHeader>
                <Box sx={{display: 'flex', alignItems: 'center', gap: 1}}>
                    <Icon sx={{fontSize: 18, color: 'primary.main'}}>terminal</Icon>
                    <Typography variant="body2" sx={{fontWeight: 600}}>
                        Live Feed
                    </Typography>
                    {entries.length > 0 && (
                        <Typography variant="caption" color="text.secondary">
                            ({filtered.length})
                        </Typography>
                    )}
                </Box>
                <Box sx={{display: 'flex', gap: 0.25}}>
                    {entries.length > 0 && (
                        <Tooltip title="Clear all">
                            <IconButton size="small" onClick={handleClear}>
                                <Icon sx={{fontSize: 16}}>delete_sweep</Icon>
                            </IconButton>
                        </Tooltip>
                    )}
                    <Tooltip title="Close">
                        <IconButton size="small" onClick={onClose}>
                            <Icon sx={{fontSize: 16}}>close</Icon>
                        </IconButton>
                    </Tooltip>
                </Box>
            </PanelHeader>
            {visibleFilterDefs.length > 0 && (
                <Box sx={{display: 'flex', flexWrap: 'wrap', gap: 0.5, px: 2, pb: 1}}>
                    {visibleFilterDefs.map(({tag, label, color: colorFn}) => {
                        const c = colorFn(theme);
                        const active = activeFilters.has(tag);
                        return (
                            <Chip
                                key={tag}
                                label={`${label} ${tagCounts[tag]}`}
                                size="small"
                                onClick={() => toggleFilter(tag)}
                                variant={active ? 'filled' : 'outlined'}
                                sx={{
                                    height: 22,
                                    fontSize: '10px',
                                    fontWeight: 600,
                                    borderRadius: 0.5,
                                    cursor: 'pointer',
                                    borderColor: c,
                                    ...(active ? {backgroundColor: c, color: 'common.white'} : {color: c}),
                                }}
                            />
                        );
                    })}
                    {activeFilters.size > 0 && (
                        <Chip
                            label="Clear"
                            size="small"
                            onClick={() => setActiveFilters(new Set())}
                            variant="outlined"
                            sx={{height: 22, fontSize: '10px', borderRadius: 0.5, cursor: 'pointer'}}
                        />
                    )}
                </Box>
            )}
            <Divider />
            <Box sx={{overflowY: 'auto', flex: 1}}>
                {entries.length === 0 ? (
                    <EmptyState
                        icon="terminal"
                        title="No live events yet"
                        description="Real-time logs and dumps will appear here as they arrive"
                    />
                ) : filtered.length === 0 ? (
                    <EmptyState
                        icon="filter_list"
                        title="No matching entries"
                        description="Try adjusting the filters"
                    />
                ) : (
                    filtered.map((entry) => <FeedEventItem key={entry.id} entry={entry} />)
                )}
            </Box>
        </PanelRoot>
    );
});
