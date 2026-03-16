import {JsonRenderer} from '@app-dev-panel/panel/Module/Debug/Component/JsonRenderer';
import {SectionTitle} from '@app-dev-panel/sdk/Component/SectionTitle';
import {primitives} from '@app-dev-panel/sdk/Component/Theme/tokens';
import {isClassString} from '@app-dev-panel/sdk/Helper/classMatcher';
import {formatMicrotime} from '@app-dev-panel/sdk/Helper/formatDate';
import {toObjectString} from '@app-dev-panel/sdk/Helper/objectString';
import {Alert, AlertTitle, Box, Collapse, Tooltip, Typography} from '@mui/material';
import {styled} from '@mui/material/styles';
import {useState} from 'react';

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

const Duration = styled(Typography)({
    width: 60,
    flexShrink: 0,
    textAlign: 'right',
    fontFamily: primitives.fontFamilyMono,
    fontSize: '11px',
    color: primitives.gray400,
    paddingLeft: 8,
});

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

    if (!data || !Array.isArray(data) || data.length === 0) {
        return (
            <Box m={2}>
                <Alert severity="info">
                    <AlertTitle>No timeline items found during the process</AlertTitle>
                </Alert>
            </Box>
        );
    }

    // Calculate time bounds
    const startTimes = data.map((r) => r[0]);
    const minTime = Math.min(...startTimes);
    const maxTime = Math.max(...startTimes);
    const totalDuration = maxTime - minTime || 1;

    // Build tick marks
    const tickCount = 6;
    const ticks: string[] = [];
    for (let i = 0; i <= tickCount; i++) {
        const t = (totalDuration / tickCount) * i;
        ticks.push(t < 1 ? `${(t * 1000).toFixed(0)}us` : `${t.toFixed(1)}ms`);
    }

    // Unique labels for legend
    const uniqueLabels = [...new Set(data.map((r) => r[2].split('\\').pop() ?? r[2]))];

    return (
        <Box>
            <Box sx={{display: 'flex', alignItems: 'center', gap: 2, mb: 2}}>
                <SectionTitle>{`${data.length} timeline events`}</SectionTitle>
            </Box>

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

            {data.map((row, index) => {
                const shortName = row[2].split('\\').pop() ?? row[2];
                const offset = ((row[0] - minTime) / totalDuration) * 100;
                const barWidth = Math.max(1, ((row[1] || 0.001) / totalDuration) * 100);
                const colorIdx = uniqueLabels.indexOf(shortName);
                const expanded = expandedIndex === index;
                const durationMs = row[1];
                const durationLabel =
                    durationMs < 0.001
                        ? `${(durationMs * 1000000).toFixed(0)}ns`
                        : durationMs < 1
                          ? `${(durationMs * 1000).toFixed(0)}us`
                          : `${durationMs.toFixed(1)}ms`;

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
                                        width: `${barWidth}%`,
                                        backgroundColor: getBarColor(colorIdx),
                                    }}
                                />
                            </BarArea>
                            <Duration>{durationLabel}</Duration>
                        </Row>
                        <Collapse in={expanded}>
                            <DetailBox>
                                <Box sx={{display: 'flex', gap: 3, mb: 1}}>
                                    <Typography variant="caption" sx={{color: 'text.disabled'}}>
                                        Time: {formatMicrotime(row[0])}
                                    </Typography>
                                    <Typography variant="caption" sx={{color: 'text.disabled'}}>
                                        Duration: {durationLabel}
                                    </Typography>
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
