import {JsonRenderer} from '@app-dev-panel/panel/Module/Debug/Component/JsonRenderer';
import {TimelineListView} from '@app-dev-panel/panel/Module/Debug/Component/Panel/TimelineListView';
import {EmptyState} from '@app-dev-panel/sdk/Component/EmptyState';
import {FileLink} from '@app-dev-panel/sdk/Component/FileLink';
import {FilterInput} from '@app-dev-panel/sdk/Component/FilterInput';
import {SectionTitle} from '@app-dev-panel/sdk/Component/SectionTitle';
import {isClassString} from '@app-dev-panel/sdk/Helper/classMatcher';
import {formatMicrotime} from '@app-dev-panel/sdk/Helper/formatDate';
import {toObjectString} from '@app-dev-panel/sdk/Helper/objectString';
import {Box, Chip, Collapse, IconButton, Tooltip, Typography} from '@mui/material';
import {styled, useTheme} from '@mui/material/styles';
import {useCallback, useDeferredValue, useMemo, useState} from 'react';

// Data format from PHP: [microtime, reference, collectorClass, additionalData?]
// row[0] = microtime(true) — start timestamp
// row[1] = reference — object ID or count (NOT a duration)
// row[2] = collector class name
// row[3] = additional data (optional)
type Item = [number, number, string] | [number, number, string, string];
type TimelinePanelProps = {data: Item[]};

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

const DetailBox = styled(Box)(({theme}) => ({
    padding: theme.spacing(1.5, 2, 1.5, 23),
    backgroundColor: theme.palette.action.hover,
    borderBottom: `1px solid ${theme.palette.divider}`,
    fontSize: '12px',
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
    const chartColors = theme.adp.chartColors;
    const getBarColor = (index: number): string => chartColors[index % chartColors.length];
    const [expandedIndex, setExpandedIndex] = useState<number | null>(null);
    const [filter, setFilter] = useState('');
    const deferredFilter = useDeferredValue(filter);
    const [activeFilters, setActiveFilters] = useState<Set<string>>(new Set());
    const [viewMode, setViewMode] = useState<ViewMode>('list');

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

    // Unique labels for legend
    const uniqueLabels = useMemo(
        () => (!data || data.length === 0 ? [] : [...new Set(data.map((r) => r[2].split('\\').pop() ?? r[2]))]),
        [data],
    );

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
            <SectionTitle
                action={
                    <Box sx={{display: 'flex', alignItems: 'center', gap: 1.5}}>
                        <ViewToggle mode={viewMode} onChange={setViewMode} />
                        <FilterInput value={filter} onChange={setFilter} placeholder="Filter timeline..." />
                    </Box>
                }
            >{`${filtered.length} timeline events`}</SectionTitle>

            <Box sx={{display: 'flex', flexWrap: 'wrap', gap: 0.75, mb: 2}}>
                {uniqueLabels.map((label, i) => {
                    const isActive = activeFilters.has(label);
                    const color = getBarColor(i);
                    return (
                        <Chip
                            key={label}
                            label={label}
                            size="small"
                            onClick={() => uniqueLabels.length > 1 && toggleFilter(label)}
                            sx={{
                                fontSize: '11px',
                                height: 24,
                                borderRadius: 1,
                                fontWeight: 600,
                                cursor: uniqueLabels.length > 1 ? 'pointer' : 'default',
                                backgroundColor: isActive ? color : 'transparent',
                                color: isActive ? 'common.white' : color,
                                border: `1px solid ${color}`,
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

            {viewMode === 'list' ? (
                <TimelineListView data={data} filter={deferredFilter} activeFilters={activeFilters} />
            ) : (
                <>
                    <TimeAxis>
                        {ticks.map((tick, i) => (
                            <span key={i}>{tick}</span>
                        ))}
                    </TimeAxis>

                    {filtered.map((row, index) => {
                        const shortName = row[2].split('\\').pop() ?? row[2];
                        const relativeTime = row[0] - minTime;
                        const offset = (relativeTime / totalSpan) * 100;
                        const colorIdx = uniqueLabels.indexOf(shortName);
                        const expanded = expandedIndex === index;

                        // Format relative time offset
                        const offsetLabel =
                            relativeTime < 0.001
                                ? `${(relativeTime * 1000000).toFixed(0)}µs`
                                : relativeTime < 1
                                  ? `${(relativeTime * 1000).toFixed(1)}ms`
                                  : `${relativeTime.toFixed(3)}s`;

                        return (
                            <Box key={index}>
                                <Row selected={expanded} onClick={() => setExpandedIndex(expanded ? null : index)}>
                                    <Tooltip title={row[2]} placement="left">
                                        <Label sx={{color: 'text.secondary'}}>{shortName}</Label>
                                    </Tooltip>
                                    <BarArea>
                                        <Bar
                                            sx={{
                                                left: `${offset}%`,
                                                width: 6,
                                                minWidth: 6,
                                                backgroundColor: getBarColor(colorIdx),
                                            }}
                                        />
                                    </BarArea>
                                    <Duration>{offsetLabel}</Duration>
                                </Row>
                                <Collapse in={expanded}>
                                    <DetailBox>
                                        <Box sx={{display: 'flex', gap: 3, mb: 1}}>
                                            <Typography variant="caption" sx={{color: 'text.disabled'}}>
                                                Time: {formatMicrotime(row[0])}
                                            </Typography>
                                            <Typography variant="caption" sx={{color: 'text.disabled'}}>
                                                Offset: +{offsetLabel}
                                            </Typography>
                                            {row[1] != null && (
                                                <Typography variant="caption" sx={{color: 'text.disabled'}}>
                                                    Ref: {String(row[1])}
                                                </Typography>
                                            )}
                                        </Box>
                                        <FileLink className={row[2]}>
                                            <Typography
                                                variant="caption"
                                                component="span"
                                                sx={(theme) => ({
                                                    fontFamily: theme.adp.fontFamilyMono,
                                                    color: 'primary.main',
                                                    '&:hover': {textDecoration: 'underline'},
                                                })}
                                            >
                                                {row[2]}
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
                </>
            )}
        </Box>
    );
};
