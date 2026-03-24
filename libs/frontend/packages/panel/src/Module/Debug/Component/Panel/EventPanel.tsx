import {JsonRenderer} from '@app-dev-panel/panel/Module/Debug/Component/JsonRenderer';
import {EmptyState} from '@app-dev-panel/sdk/Component/EmptyState';
import {FileLink} from '@app-dev-panel/sdk/Component/FileLink';
import {FilterInput} from '@app-dev-panel/sdk/Component/FilterInput';
import {SectionTitle} from '@app-dev-panel/sdk/Component/SectionTitle';
import {primitives} from '@app-dev-panel/sdk/Component/Theme/tokens';
import {parseFilename} from '@app-dev-panel/sdk/Helper/filePathParser';
import {formatMicrotime} from '@app-dev-panel/sdk/Helper/formatDate';
import {Code} from '@mui/icons-material';
import {Box, Chip, Collapse, Icon, IconButton, Tooltip, Typography} from '@mui/material';
import {styled, useTheme} from '@mui/material/styles';
import {useCallback, useDeferredValue, useMemo, useState} from 'react';

type EventType = {event: string; file: string; line: string; name: string; time: number};
type EventTimelineProps = {events: EventType[]};

const EVENT_PALETTE = [
    {paletteKey: 'primary', shade: 'main'} as const,
    {paletteKey: 'success', shade: 'main'} as const,
    {paletteKey: 'warning', shade: 'main'} as const,
    {paletteKey: 'error', shade: 'main'} as const,
];

const getEventColorIndex = (name: string): number => {
    let hash = 0;
    for (let i = 0; i < name.length; i++) {
        hash = (hash * 31 + name.charCodeAt(i)) | 0;
    }
    return Math.abs(hash) % EVENT_PALETTE.length;
};

const EventRow = styled(Box, {shouldForwardProp: (p) => p !== 'expanded' && p !== 'accentColor'})<{
    expanded?: boolean;
    accentColor?: string;
}>(({theme, expanded, accentColor}) => ({
    display: 'flex',
    alignItems: 'center',
    gap: theme.spacing(1.5),
    padding: theme.spacing(1, 1.5),
    borderBottom: `1px solid ${theme.palette.divider}`,
    borderLeft: `3px solid ${accentColor ?? 'transparent'}`,
    cursor: 'pointer',
    transition: 'background-color 0.1s ease',
    backgroundColor: expanded ? theme.palette.action.hover : 'transparent',
    '&:hover': {backgroundColor: theme.palette.action.hover},
}));

const TimeCell = styled(Typography)({
    fontFamily: primitives.fontFamilyMono,
    fontSize: '11px',
    flexShrink: 0,
    width: 110,
});

const NameCell = styled(Typography)({fontSize: '13px', fontWeight: 500, flex: 1, wordBreak: 'break-word'});

const FileCell = styled(Typography)({
    fontFamily: primitives.fontFamilyMono,
    fontSize: '11px',
    flexShrink: 0,
    whiteSpace: 'nowrap',
}) as typeof Typography;

const DetailBox = styled(Box)(({theme}) => ({
    padding: theme.spacing(1.5, 1.5, 1.5, 15.5),
    backgroundColor: theme.palette.action.hover,
    borderBottom: `1px solid ${theme.palette.divider}`,
    fontSize: '12px',
}));

const DeltaChip = styled(Typography)(({theme}) => ({
    fontFamily: primitives.fontFamilyMono,
    fontSize: '10px',
    color: theme.palette.text.disabled,
    backgroundColor: theme.palette.action.selected,
    padding: '1px 6px',
    borderRadius: 4,
    flexShrink: 0,
    lineHeight: '16px',
}));

export const EventPanel = ({events}: EventTimelineProps) => {
    const [filter, setFilter] = useState('');
    const deferredFilter = useDeferredValue(filter);
    const [activeFilters, setActiveFilters] = useState<Set<string>>(new Set());
    const [expandedIndex, setExpandedIndex] = useState<number | null>(null);
    const theme = useTheme();

    const badgeCounts = useMemo(() => {
        const counts = new Map<string, number>();
        if (!events) return [];
        for (const event of events) {
            const shortName = event.name.split('\\').pop() ?? event.name;
            counts.set(shortName, (counts.get(shortName) ?? 0) + 1);
        }
        return [...counts.entries()].sort((a, b) => b[1] - a[1]);
    }, [events]);

    const toggleFilter = useCallback((name: string) => {
        setActiveFilters((prev) => {
            const next = new Set(prev);
            if (next.has(name)) {
                next.delete(name);
            } else {
                next.add(name);
            }
            return next;
        });
    }, []);

    const getColor = useCallback(
        (shortName: string): string => {
            const idx = getEventColorIndex(shortName);
            const entry = EVENT_PALETTE[idx];
            return (theme.palette[entry.paletteKey] as Record<string, string>)[entry.shade];
        },
        [theme],
    );

    if (!events || events.length === 0) {
        return <EmptyState icon="bolt" title="No dispatched events found" />;
    }

    const filtered = useMemo(() => {
        let result = events;
        if (activeFilters.size > 0) {
            result = result.filter((e) => {
                const shortName = e.name.split('\\').pop() ?? e.name;
                return activeFilters.has(shortName);
            });
        }
        if (deferredFilter) {
            const lower = deferredFilter.toLowerCase();
            result = result.filter((e) => e.name.toLowerCase().includes(lower) || e.file.toLowerCase().includes(lower));
        }
        return result;
    }, [events, deferredFilter, activeFilters]);

    const formatDelta = (ms: number): string => {
        if (ms < 1) return `+${(ms * 1000).toFixed(0)}µs`;
        if (ms < 1000) return `+${ms.toFixed(1)}ms`;
        return `+${(ms / 1000).toFixed(2)}s`;
    };

    return (
        <Box>
            <SectionTitle
                action={<FilterInput value={filter} onChange={setFilter} placeholder="Filter events..." />}
            >{`${filtered.length} event${filtered.length !== 1 ? 's' : ''}`}</SectionTitle>

            {badgeCounts.length > 1 && (
                <Box sx={{display: 'flex', flexWrap: 'wrap', gap: 0.75, mb: 2}}>
                    {badgeCounts.map(([name, count]) => {
                        const color = getColor(name);
                        const isActive = activeFilters.has(name);
                        return (
                            <Chip
                                key={name}
                                label={`${name} (${count})`}
                                size="small"
                                onClick={() => toggleFilter(name)}
                                variant={isActive ? 'filled' : 'outlined'}
                                sx={{
                                    fontSize: '11px',
                                    height: 24,
                                    borderRadius: 1,
                                    fontWeight: isActive ? 600 : 400,
                                    cursor: 'pointer',
                                    borderColor: color,
                                    ...(isActive
                                        ? {backgroundColor: color, color: theme.palette.common.white}
                                        : {color}),
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
                            sx={{fontSize: '11px', height: 24, borderRadius: 1}}
                        />
                    )}
                </Box>
            )}

            {filtered.map((event, index) => {
                const expanded = expandedIndex === index;
                const shortName = event.name.split('\\').pop() ?? event.name;
                const color = getColor(shortName);
                const prevEvent = index > 0 ? filtered[index - 1] : null;
                const deltaMs = prevEvent ? (event.time - prevEvent.time) * 1000 : null;

                return (
                    <Box key={index}>
                        <EventRow
                            expanded={expanded}
                            accentColor={color}
                            onClick={() => setExpandedIndex(expanded ? null : index)}
                        >
                            <TimeCell sx={{color: 'text.disabled'}}>{formatMicrotime(event.time)}</TimeCell>
                            <Box
                                sx={{width: 8, height: 8, borderRadius: '50%', backgroundColor: color, flexShrink: 0}}
                            />
                            <NameCell>
                                <Tooltip title={event.name}>
                                    <span>{shortName}</span>
                                </Tooltip>
                            </NameCell>
                            {deltaMs !== null && deltaMs >= 0 && (
                                <Tooltip title="Time since previous event">
                                    <DeltaChip component="span">{formatDelta(deltaMs)}</DeltaChip>
                                </Tooltip>
                            )}
                            <FileCell component="span" sx={{color: 'text.disabled'}}>
                                {parseFilename(event.line)}
                            </FileCell>
                            <IconButton size="small" sx={{flexShrink: 0}}>
                                <Icon sx={{fontSize: 16}}>{expanded ? 'expand_less' : 'expand_more'}</Icon>
                            </IconButton>
                        </EventRow>
                        <Collapse in={expanded}>
                            <DetailBox>
                                <Typography
                                    variant="caption"
                                    sx={{
                                        fontFamily: primitives.fontFamilyMono,
                                        color: 'text.secondary',
                                        display: 'block',
                                        mb: 1,
                                    }}
                                >
                                    {event.name}
                                </Typography>

                                <Box sx={{display: 'flex', gap: 1, mb: 1.5}}>
                                    <FileLink path={event.line}>
                                        <Chip
                                            component="span"
                                            clickable
                                            label="Open File"
                                            size="small"
                                            icon={<Code sx={{fontSize: '14px !important'}} />}
                                            sx={{fontSize: '11px', height: 24}}
                                            variant="outlined"
                                        />
                                    </FileLink>
                                </Box>

                                {event.event && <JsonRenderer value={event.event} depth={3} />}
                            </DetailBox>
                        </Collapse>
                    </Box>
                );
            })}
        </Box>
    );
};
