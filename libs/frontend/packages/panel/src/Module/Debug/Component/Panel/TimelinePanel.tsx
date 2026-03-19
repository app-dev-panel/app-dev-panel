import {JsonRenderer} from '@app-dev-panel/panel/Module/Debug/Component/JsonRenderer';
import {EmptyState} from '@app-dev-panel/sdk/Component/EmptyState';
import {SectionTitle} from '@app-dev-panel/sdk/Component/SectionTitle';
import {primitives} from '@app-dev-panel/sdk/Component/Theme/tokens';
import {isClassString} from '@app-dev-panel/sdk/Helper/classMatcher';
import {formatMicrotime} from '@app-dev-panel/sdk/Helper/formatDate';
import {toObjectString} from '@app-dev-panel/sdk/Helper/objectString';
import {Box, Chip, Collapse, TextField, Tooltip, Typography} from '@mui/material';
import {styled} from '@mui/material/styles';
import {useCallback, useMemo, useState} from 'react';

// Data format from PHP: [microtime, reference, collectorClass, additionalData?]
// row[0] = microtime(true) — start timestamp
// row[1] = reference — object ID or count (NOT a duration)
// row[2] = collector class name
// row[3] = additional data (optional)
type Item = [number, number, string] | [number, number, string, string];
type TimelinePanelProps = {data: Item[]};

// Colors for different span types
const barColors = [
    '#42A5F5', // blue
    '#AB47BC', // purple
    '#66BB6A', // green
    '#FFA726', // orange
    '#26C6DA', // cyan
    '#EC407A', // pink
    '#8D6E63', // brown
    '#78909C', // blue-gray
    '#FFEE58', // yellow
    '#7C3AED', // violet
];

const getBarColor = (index: number): string => barColors[index % barColors.length];

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
    fontFamily: primitives.fontFamilyMono,
    color: theme.palette.text.disabled,
    borderBottom: `1px solid ${theme.palette.divider}`,
    marginBottom: theme.spacing(0.5),
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

const Label = styled(Typography)({
    width: 160,
    flexShrink: 0,
    fontSize: '12px',
    textAlign: 'right',
    paddingRight: 12,
    whiteSpace: 'nowrap',
    overflow: 'hidden',
    textOverflow: 'ellipsis',
});

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
    fontFamily: primitives.fontFamilyMono,
    fontSize: '11px',
    color: theme.palette.text.disabled,
    paddingLeft: 8,
}));

const DetailBox = styled(Box)(({theme}) => ({
    padding: theme.spacing(1.5, 2, 1.5, 23),
    backgroundColor: theme.palette.action.hover,
    borderBottom: `1px solid ${theme.palette.divider}`,
    fontSize: '12px',
}));

const LegendBar = styled(Box)(({theme}) => ({
    display: 'flex',
    gap: theme.spacing(2),
    flexWrap: 'wrap',
    paddingLeft: 180,
    paddingBottom: theme.spacing(1.5),
    fontSize: '11px',
    color: theme.palette.text.disabled,
}));

const LegendItem = styled(Box)({display: 'flex', alignItems: 'center', gap: 4});

const Swatch = styled(Box)({width: 12, height: 8, borderRadius: 2});

export const TimelinePanel = ({data}: TimelinePanelProps) => {
    const [expandedIndex, setExpandedIndex] = useState<number | null>(null);
    const [filter, setFilter] = useState('');
    const [activeFilters, setActiveFilters] = useState<Set<string>>(new Set());

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

    if (!data || !Array.isArray(data) || data.length === 0) {
        return <EmptyState icon="timeline" title="No timeline items found" />;
    }

    // Unique labels for legend & badge counts
    const uniqueLabels = [...new Set(data.map((r) => r[2].split('\\').pop() ?? r[2]))];

    const badgeCounts = useMemo(() => {
        const counts = new Map<string, number>();
        for (const row of data) {
            const shortName = row[2].split('\\').pop() ?? row[2];
            counts.set(shortName, (counts.get(shortName) ?? 0) + 1);
        }
        return [...counts.entries()].sort((a, b) => b[1] - a[1]);
    }, [data]);

    const filtered = useMemo(() => {
        let result = data;
        if (activeFilters.size > 0) {
            result = result.filter((r) => {
                const shortName = r[2].split('\\').pop() ?? r[2];
                return activeFilters.has(shortName);
            });
        }
        if (filter) {
            result = result.filter((r) => r[2].toLowerCase().includes(filter.toLowerCase()));
        }
        return result;
    }, [data, filter, activeFilters]);

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
            <Box sx={{display: 'flex', alignItems: 'center', gap: 2, mb: 2}}>
                <SectionTitle>{`${filtered.length} timeline events`}</SectionTitle>
                <TextField
                    size="small"
                    placeholder="Filter timeline..."
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

            <LegendBar>
                {uniqueLabels.slice(0, 10).map((label, i) => (
                    <LegendItem key={label}>
                        <Swatch sx={{backgroundColor: getBarColor(i)}} />
                        <span>{label}</span>
                    </LegendItem>
                ))}
            </LegendBar>

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
                                <Typography
                                    variant="caption"
                                    sx={{
                                        fontFamily: primitives.fontFamilyMono,
                                        color: 'text.secondary',
                                        display: 'block',
                                        mb: 1,
                                    }}
                                >
                                    {row[2]}
                                </Typography>
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
        </Box>
    );
};
