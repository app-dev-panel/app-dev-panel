import {JsonRenderer} from '@app-dev-panel/panel/Module/Debug/Component/JsonRenderer';
import {useDebugEntry} from '@app-dev-panel/sdk/API/Debug/Context';
import {EmptyState} from '@app-dev-panel/sdk/Component/EmptyState';
import {SectionTitle} from '@app-dev-panel/sdk/Component/SectionTitle';
import {primitives} from '@app-dev-panel/sdk/Component/Theme/tokens';
import {parseFilename, parseFilePathWithLineAnchor} from '@app-dev-panel/sdk/Helper/filePathParser';
import {formatMicrotime} from '@app-dev-panel/sdk/Helper/formatDate';
import {parseObjectId} from '@app-dev-panel/sdk/Helper/objectString';
import {Box, Chip, Collapse, Icon, IconButton, TextField, Tooltip, Typography} from '@mui/material';
import {styled} from '@mui/material/styles';
import {useCallback, useDeferredValue, useMemo, useState} from 'react';

type EventType = {event: string; file: string; line: string; name: string; time: number};
type EventTimelineProps = {events: EventType[]};

const EventRow = styled(Box, {shouldForwardProp: (p) => p !== 'expanded'})<{expanded?: boolean}>(
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

export const EventPanel = ({events}: EventTimelineProps) => {
    const [filter, setFilter] = useState('');
    const deferredFilter = useDeferredValue(filter);
    const [activeFilters, setActiveFilters] = useState<Set<string>>(new Set());
    const [expandedIndex, setExpandedIndex] = useState<number | null>(null);
    const debugEntry = useDebugEntry();

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

    return (
        <Box>
            <Box sx={{display: 'flex', alignItems: 'center', gap: 2, mb: 2}}>
                <SectionTitle>{`${filtered.length} event${filtered.length !== 1 ? 's' : ''}`}</SectionTitle>
                <TextField
                    size="small"
                    placeholder="Filter events..."
                    value={filter}
                    onChange={(e) => setFilter(e.target.value)}
                    InputProps={{sx: {fontSize: '13px'}}}
                    sx={{ml: 'auto', width: 240}}
                />
            </Box>

            {badgeCounts.length > 1 && (
                <Box sx={{display: 'flex', flexWrap: 'wrap', gap: 0.75, mb: 2}}>
                    {badgeCounts.map(([name, count]) => (
                        <Chip
                            key={name}
                            label={`${name} (${count})`}
                            size="small"
                            onClick={() => toggleFilter(name)}
                            variant={activeFilters.has(name) ? 'filled' : 'outlined'}
                            color={activeFilters.has(name) ? 'primary' : 'default'}
                            sx={{
                                fontSize: '11px',
                                height: 24,
                                borderRadius: 1,
                                fontWeight: activeFilters.has(name) ? 600 : 400,
                                cursor: 'pointer',
                            }}
                        />
                    ))}
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
                const objectId = parseObjectId(event.event || '');

                return (
                    <Box key={index}>
                        <EventRow expanded={expanded} onClick={() => setExpandedIndex(expanded ? null : index)}>
                            <TimeCell sx={{color: 'text.disabled'}}>{formatMicrotime(event.time)}</TimeCell>
                            <NameCell>
                                <Tooltip title={event.name}>
                                    <span>{shortName}</span>
                                </Tooltip>
                            </NameCell>
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
                                    <Chip
                                        component="a"
                                        clickable
                                        href={`/inspector/files?path=${parseFilePathWithLineAnchor(event.line)}`}
                                        label="Open File"
                                        size="small"
                                        icon={<Icon sx={{fontSize: '14px !important'}}>open_in_new</Icon>}
                                        sx={{fontSize: '11px', height: 24}}
                                        variant="outlined"
                                    />
                                    {objectId && debugEntry && (
                                        <Chip
                                            component="a"
                                            clickable
                                            href={`/debug/object?debugEntry=${debugEntry.id}&id=${objectId}`}
                                            label="Examine Object"
                                            size="small"
                                            icon={<Icon sx={{fontSize: '14px !important'}}>data_object</Icon>}
                                            sx={{fontSize: '11px', height: 24}}
                                            variant="outlined"
                                        />
                                    )}
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
