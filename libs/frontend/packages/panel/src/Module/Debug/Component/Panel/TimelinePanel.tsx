import {TimelineDetailCard} from '@app-dev-panel/panel/Module/Debug/Component/Panel/TimelineDetailCard';
import {TimelineListView} from '@app-dev-panel/panel/Module/Debug/Component/Panel/TimelineListView';
import {
    type TimelineItem,
    getCollectorColor as getCollectorColorFromTheme,
    parseLogLevel,
} from '@app-dev-panel/panel/Module/Debug/Component/Panel/timelineTypes';
import {useTimelineEnrichment} from '@app-dev-panel/panel/Module/Debug/Component/Panel/useTimelineEnrichment';
import {EmptyState} from '@app-dev-panel/sdk/Component/EmptyState';
import {FilterChip} from '@app-dev-panel/sdk/Component/FilterChip';
import {FilterInput} from '@app-dev-panel/sdk/Component/FilterInput';
import {PageToolbar} from '@app-dev-panel/sdk/Component/PageToolbar';
import {getCollectorLabel} from '@app-dev-panel/sdk/Helper/collectorMeta';
import {Box, Collapse, IconButton, Tooltip, Typography} from '@mui/material';
import {styled, useTheme} from '@mui/material/styles';
import {useCallback, useDeferredValue, useMemo, useState} from 'react';

type TimelinePanelProps = {data: TimelineItem[]};

type ViewMode = 'waterfall' | 'list';

// ---------------------------------------------------------------------------
// Styled components
// ---------------------------------------------------------------------------

const TimeAxis = styled(Box)(({theme}) => ({
    display: 'flex',
    justifyContent: 'space-between',
    paddingLeft: 180,
    paddingRight: 70,
    paddingBottom: theme.spacing(0.5),
    fontSize: '10px',
    fontFamily: theme.adp.fontFamilyMono,
    color: theme.palette.text.disabled,
    borderBottom: `1px solid ${theme.palette.divider}`,
    marginBottom: theme.spacing(0.5),
    [theme.breakpoints.down('sm')]: {paddingLeft: 100, paddingRight: 8},
}));

const Row = styled(Box, {shouldForwardProp: (p) => p !== 'selected'})<{selected?: boolean}>(({theme, selected}) => ({
    display: 'flex',
    alignItems: 'center',
    height: 32,
    padding: theme.spacing(0, 2),
    borderBottom: `1px solid ${theme.palette.divider}`,
    cursor: 'pointer',
    transition: 'background 0.1s',
    backgroundColor: selected ? theme.palette.action.selected : 'transparent',
    '&:hover': {backgroundColor: theme.palette.action.hover},
}));

const Label = styled(Typography)(({theme}) => ({
    width: 160,
    flexShrink: 0,
    fontSize: '12px',
    textAlign: 'right',
    paddingRight: 12,
    whiteSpace: 'nowrap',
    overflow: 'hidden',
    textOverflow: 'ellipsis',
    [theme.breakpoints.down('sm')]: {width: 80, fontSize: '10px', paddingRight: 6},
}));

const BarArea = styled(Box)({flex: 1, position: 'relative', height: 20});

const Bar = styled(Box)({
    position: 'absolute',
    height: 18,
    borderRadius: 3,
    top: 1,
    minWidth: 4,
    transition: 'opacity 0.15s, box-shadow 0.15s',
    '&:hover': {opacity: 0.85, boxShadow: '0 0 0 2px rgba(255,255,255,0.2)'},
});

const Duration = styled(Typography)(({theme}) => ({
    width: 60,
    flexShrink: 0,
    textAlign: 'right',
    fontFamily: theme.adp.fontFamilyMono,
    fontSize: '11px',
    color: theme.palette.text.disabled,
    paddingLeft: 8,
    [theme.breakpoints.down('sm')]: {display: 'none'},
}));

const WaterfallDetail = styled(Typography)(({theme}) => ({
    fontSize: '11px',
    fontFamily: theme.adp.fontFamilyMono,
    color: theme.palette.text.secondary,
    whiteSpace: 'nowrap',
    overflow: 'hidden',
    textOverflow: 'ellipsis',
    minWidth: 0,
    flex: 1,
    paddingLeft: 8,
    [theme.breakpoints.down('sm')]: {display: 'none'},
}));

// ---------------------------------------------------------------------------
// View toggle button
// ---------------------------------------------------------------------------

const ViewToggle = ({mode, onChange}: {mode: ViewMode; onChange: (mode: ViewMode) => void}) => {
    return (
        <Box sx={{display: 'flex', gap: 0.25}}>
            <Tooltip title="Waterfall view">
                <IconButton
                    size="small"
                    onClick={() => onChange('waterfall')}
                    sx={{
                        borderRadius: 1,
                        color: mode === 'waterfall' ? 'primary.main' : 'text.disabled',
                        backgroundColor: mode === 'waterfall' ? 'action.selected' : 'transparent',
                        '&:hover': {backgroundColor: 'action.hover'},
                        width: 28,
                        height: 28,
                    }}
                >
                    <span className="material-icons" style={{fontSize: 18}}>
                        waterfall_chart
                    </span>
                </IconButton>
            </Tooltip>
            <Tooltip title="List view">
                <IconButton
                    size="small"
                    onClick={() => onChange('list')}
                    sx={{
                        borderRadius: 1,
                        color: mode === 'list' ? 'primary.main' : 'text.disabled',
                        backgroundColor: mode === 'list' ? 'action.selected' : 'transparent',
                        '&:hover': {backgroundColor: 'action.hover'},
                        width: 28,
                        height: 28,
                    }}
                >
                    <span className="material-icons" style={{fontSize: 18}}>
                        view_list
                    </span>
                </IconButton>
            </Tooltip>
        </Box>
    );
};

export const TimelinePanel = ({data}: TimelinePanelProps) => {
    const theme = useTheme();
    const [expandedIndex, setExpandedIndex] = useState<number | null>(null);
    const [filter, setFilter] = useState('');
    const deferredFilter = useDeferredValue(filter);
    const [activeFilters, setActiveFilters] = useState<Set<string>>(new Set());
    const [viewMode, setViewMode] = useState<ViewMode>('list');

    const getCollectorColor = useCallback(
        (collectorClass: string) => getCollectorColorFromTheme(theme, collectorClass),
        [theme],
    );

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

    // Unique collector classes for legend (stores FQCN for color lookup, short name for filtering)
    const uniqueCollectors = useMemo(() => {
        if (!data || data.length === 0) return [];
        const seen = new Set<string>();
        return data
            .filter((r) => {
                const cls = r[2];
                if (seen.has(cls)) return false;
                seen.add(cls);
                return true;
            })
            .map((r) => ({fqcn: r[2], shortName: r[2].split('\\').pop() ?? r[2], label: getCollectorLabel(r[2])}));
    }, [data]);

    const filtered = useMemo(() => {
        if (!data || !Array.isArray(data)) return [];
        let result = data;
        if (activeFilters.size > 0) {
            result = result.filter((r) => {
                const shortName = r[2].split('\\').pop() ?? r[2];
                return activeFilters.has(shortName);
            });
        }
        if (deferredFilter) {
            const lower = deferredFilter.toLowerCase();
            result = result.filter((r) => r[2].toLowerCase().includes(lower));
        }
        return result;
    }, [data, deferredFilter, activeFilters]);

    // Shared enrichment hook — used by both views
    const enrichedDetails = useTimelineEnrichment(data ?? [], filtered);

    if (!data || !Array.isArray(data) || data.length === 0) {
        return <EmptyState icon="timeline" title="No timeline items found" />;
    }

    // Events are point-in-time: row[0] is microtime, row[1] is a reference (not duration)
    // Use full data range for consistent axis regardless of filters
    const timestamps = data.map((r) => r[0]);
    const minTime = Math.min(...timestamps);
    const maxTime = Math.max(...timestamps);
    const totalSpan = maxTime - minTime || 0.001;

    // Build tick marks based on the time span
    const tickCount = 6;
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

    return (
        <Box>
            <PageToolbar
                sticky
                actions={
                    <Box sx={{display: 'flex', alignItems: 'center', gap: 1.5}}>
                        <ViewToggle mode={viewMode} onChange={setViewMode} />
                        <FilterInput value={filter} onChange={setFilter} placeholder="Filter timeline..." />
                    </Box>
                }
            >{`${filtered.length} timeline events`}</PageToolbar>

            <Box sx={{display: 'flex', flexWrap: 'wrap', gap: 0.75, mb: 2, px: {xs: 1.5, sm: 2.5}, pt: 1.5}}>
                {uniqueCollectors.map((col) => {
                    const isActive = activeFilters.has(col.shortName);
                    const color = getCollectorColor(col.fqcn);
                    const clickable = uniqueCollectors.length > 1;
                    return (
                        <FilterChip
                            key={col.shortName}
                            label={col.label !== 'Unknown' ? col.label : col.shortName}
                            color={color.fg}
                            active={isActive}
                            onClick={clickable ? () => toggleFilter(col.shortName) : undefined}
                        />
                    );
                })}
                {activeFilters.size > 0 && <FilterChip label="Clear" onClick={() => setActiveFilters(new Set())} />}
            </Box>

            {viewMode === 'list' ? (
                <TimelineListView data={data} filtered={filtered} enrichedDetails={enrichedDetails} />
            ) : (
                <>
                    <TimeAxis>
                        {ticks.map((tick, i) => (
                            <span key={i}>{tick}</span>
                        ))}
                    </TimeAxis>

                    {filtered.map((row, index) => {
                        const collectorClass = row[2];
                        const shortName = collectorClass.split('\\').pop() ?? collectorClass;
                        const relativeTime = row[0] - minTime;
                        const offset = (relativeTime / totalSpan) * 100;
                        const expanded = expandedIndex === index;
                        const enriched = enrichedDetails[index];
                        const color = getCollectorColor(collectorClass);
                        const label = getCollectorLabel(collectorClass);

                        const parsed = enriched ? parseLogLevel(enriched.preview) : null;
                        const detail = parsed ? parsed.message : (enriched?.preview ?? null);
                        const fullDetail = parsed ? enriched!.full.replace(/^\[\w+] /, '') : (enriched?.full ?? null);

                        // Format relative time offset
                        const offsetLabel =
                            relativeTime < 0.001
                                ? `${(relativeTime * 1000000).toFixed(0)}µs`
                                : relativeTime < 1
                                  ? `${(relativeTime * 1000).toFixed(1)}ms`
                                  : `${relativeTime.toFixed(3)}s`;

                        return (
                            <Box key={index}>
                                <Row
                                    selected={expanded}
                                    onClick={() => setExpandedIndex(expanded ? null : index)}
                                    sx={{borderLeft: `3px solid ${color.fg}`}}
                                >
                                    <Tooltip title={collectorClass} placement="left">
                                        <Label sx={{color: color.fg, fontWeight: 600}}>
                                            {label !== 'Unknown' ? label : shortName}
                                        </Label>
                                    </Tooltip>
                                    <BarArea>
                                        <Bar
                                            sx={{left: `${offset}%`, width: 6, minWidth: 6, backgroundColor: color.fg}}
                                        />
                                    </BarArea>
                                    {detail && <WaterfallDetail title={detail}>{detail}</WaterfallDetail>}
                                    <Duration>{offsetLabel}</Duration>
                                </Row>
                                <Collapse in={expanded}>
                                    <TimelineDetailCard
                                        row={row}
                                        fullDetail={fullDetail}
                                        logLevel={parsed?.level ?? null}
                                        accentColor={color.fg}
                                        offsetLabel={`+${offsetLabel}`}
                                        rawValue={enriched?.rawValue}
                                    />
                                </Collapse>
                            </Box>
                        );
                    })}
                </>
            )}
        </Box>
    );
};
