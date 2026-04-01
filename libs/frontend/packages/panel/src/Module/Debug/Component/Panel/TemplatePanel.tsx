import {JsonRenderer} from '@app-dev-panel/panel/Module/Debug/Component/JsonRenderer';
import {EmptyState} from '@app-dev-panel/sdk/Component/EmptyState';
import {FileLink} from '@app-dev-panel/sdk/Component/FileLink';
import {FilterInput} from '@app-dev-panel/sdk/Component/FilterInput';
import {SectionTitle} from '@app-dev-panel/sdk/Component/SectionTitle';
import {primitives} from '@app-dev-panel/sdk/Component/Theme/tokens';
import {formatMillisecondsAsDuration} from '@app-dev-panel/sdk/Helper/formatDate';
import {
    Box,
    Chip,
    Collapse,
    Icon,
    IconButton,
    type Theme,
    ToggleButton,
    ToggleButtonGroup,
    Tooltip,
    Typography,
} from '@mui/material';
import {styled, useTheme} from '@mui/material/styles';
import {useDeferredValue, useMemo, useState} from 'react';

type Render = {template: string; renderTime: number; output: string; parameters: unknown[]; depth?: number};
type DuplicateGroup = {key: string; count: number; indices: number[]};
type DuplicatesData = {groups: DuplicateGroup[]; totalDuplicatedCount: number};
type TemplatePanelProps = {
    data: {renders: Render[]; totalTime: number; renderCount: number; duplicates: DuplicatesData} | null;
};

const OUTPUT_PREVIEW_LENGTH = 300;

function durationColor(ms: number, theme: Theme): string {
    if (ms > 100) return theme.palette.error.main;
    if (ms > 30) return theme.palette.warning.main;
    return theme.palette.success.main;
}

function basename(filePath: string): string {
    const parts = filePath.replace(/\\/g, '/').split('/');
    return parts[parts.length - 1] ?? filePath;
}

function dirname(filePath: string): string {
    const parts = filePath.replace(/\\/g, '/').split('/');
    parts.pop();
    return parts.join('/');
}

function hasDetailData(render: Render): boolean {
    return render.output.length > 0 || render.parameters.length > 0;
}

function hasTiming(renders: Render[]): boolean {
    return renders.some((r) => r.renderTime > 0);
}

function isFilePath(template: string): boolean {
    return template.includes('/') && /\.\w+$/.test(template);
}

const RenderRow = styled(Box, {shouldForwardProp: (p) => p !== 'expandable' && p !== 'expanded'})<{
    expandable?: boolean;
    expanded?: boolean;
}>(({theme, expandable, expanded}) => ({
    display: 'flex',
    alignItems: 'center',
    gap: theme.spacing(1.5),
    padding: theme.spacing(1, 1.5),
    borderBottom: `1px solid ${theme.palette.divider}`,
    transition: 'background-color 0.1s ease',
    cursor: expandable ? 'pointer' : 'default',
    backgroundColor: expanded ? theme.palette.action.hover : 'transparent',
    '&:hover': expandable ? {backgroundColor: theme.palette.action.hover} : {},
}));

const DetailBox = styled(Box)(({theme}) => ({
    padding: theme.spacing(1.5, 1.5, 1.5, 6),
    backgroundColor: theme.palette.action.hover,
    borderBottom: `1px solid ${theme.palette.divider}`,
    fontSize: '12px',
}));

const OutputPreview = styled(Typography)({
    fontFamily: primitives.fontFamilyMono,
    fontSize: '11px',
    whiteSpace: 'pre-wrap',
    wordBreak: 'break-word',
    lineHeight: 1.5,
});

const GroupHeader = styled(Box)(({theme}) => ({
    display: 'flex',
    alignItems: 'flex-start',
    gap: theme.spacing(1.5),
    padding: theme.spacing(1, 1.5),
    borderBottom: `1px solid ${theme.palette.divider}`,
    cursor: 'pointer',
    transition: 'background-color 0.1s ease',
    backgroundColor: theme.palette.action.selected,
    '&:hover': {backgroundColor: theme.palette.action.hover},
}));

const DurationCell = styled(Typography)({
    fontFamily: primitives.fontFamilyMono,
    fontSize: '11px',
    flexShrink: 0,
    textAlign: 'right',
    width: 80,
});

const RenderItem = ({
    render,
    index,
    expanded,
    showFullOutput,
    showTiming,
    onToggle,
    onToggleFullOutput,
}: {
    render: Render;
    index: number;
    expanded: boolean;
    showFullOutput: boolean;
    showTiming: boolean;
    onToggle: () => void;
    onToggleFullOutput: (index: number) => void;
}) => {
    const theme = useTheme();
    const expandable = hasDetailData(render);
    const isTruncated = render.output.length > OUTPUT_PREVIEW_LENGTH;
    const showAsPath = isFilePath(render.template);

    return (
        <Box>
            <RenderRow
                expandable={expandable}
                expanded={expanded}
                onClick={expandable ? onToggle : undefined}
                sx={render.depth != null && render.depth > 0 ? {pl: 1.5 + render.depth * 3} : undefined}
            >
                <Box sx={{flex: 1, minWidth: 0}}>
                    {showAsPath ? (
                        <>
                            <FileLink path={render.template}>
                                <Typography
                                    component="span"
                                    sx={{
                                        fontFamily: primitives.fontFamilyMono,
                                        fontSize: '13px',
                                        fontWeight: 500,
                                        color: 'primary.main',
                                        '&:hover': {textDecoration: 'underline'},
                                    }}
                                >
                                    {basename(render.template)}
                                </Typography>
                            </FileLink>
                            <Typography
                                sx={{fontFamily: primitives.fontFamilyMono, fontSize: '10px', color: 'text.disabled'}}
                            >
                                {dirname(render.template)}
                            </Typography>
                        </>
                    ) : (
                        <Typography
                            sx={{fontFamily: primitives.fontFamilyMono, fontSize: '12px', wordBreak: 'break-all'}}
                        >
                            {render.template}
                        </Typography>
                    )}
                </Box>
                {render.parameters.length > 0 && (
                    <Chip
                        label={`${render.parameters.length} param${render.parameters.length !== 1 ? 's' : ''}`}
                        size="small"
                        variant="outlined"
                        sx={{fontSize: '10px', height: 20, borderRadius: 1, flexShrink: 0}}
                    />
                )}
                {showTiming && (
                    <DurationCell sx={{color: durationColor(render.renderTime, theme)}}>
                        {formatMillisecondsAsDuration(render.renderTime)}
                    </DurationCell>
                )}
                {expandable && (
                    <IconButton
                        size="small"
                        aria-expanded={expanded}
                        aria-label={expanded ? 'Collapse' : 'Expand'}
                        sx={{flexShrink: 0}}
                    >
                        <Icon sx={{fontSize: 16}}>{expanded ? 'expand_less' : 'expand_more'}</Icon>
                    </IconButton>
                )}
            </RenderRow>
            {expandable && (
                <Collapse in={expanded} unmountOnExit>
                    <DetailBox>
                        {render.output && (
                            <Box sx={{mb: 1.5}}>
                                <Typography sx={{fontSize: '11px', fontWeight: 600, color: 'text.disabled', mb: 0.5}}>
                                    Output
                                </Typography>
                                <OutputPreview sx={{color: 'text.secondary'}}>
                                    {showFullOutput || !isTruncated
                                        ? render.output
                                        : render.output.substring(0, OUTPUT_PREVIEW_LENGTH) + '...'}
                                </OutputPreview>
                                {isTruncated && (
                                    <Typography
                                        component="button"
                                        onClick={(e: React.MouseEvent) => {
                                            e.stopPropagation();
                                            onToggleFullOutput(index);
                                        }}
                                        sx={{
                                            fontSize: '11px',
                                            color: 'primary.main',
                                            cursor: 'pointer',
                                            mt: 0.5,
                                            p: 0,
                                            border: 'none',
                                            background: 'none',
                                            '&:hover': {textDecoration: 'underline'},
                                        }}
                                    >
                                        {showFullOutput ? 'Show less' : 'Show more'}
                                    </Typography>
                                )}
                            </Box>
                        )}
                        {render.parameters.length > 0 && (
                            <Box>
                                <Typography sx={{fontSize: '11px', fontWeight: 600, color: 'text.disabled', mb: 0.5}}>
                                    Parameters
                                </Typography>
                                <JsonRenderer value={render.parameters} />
                            </Box>
                        )}
                    </DetailBox>
                </Collapse>
            )}
        </Box>
    );
};

const DuplicateGroupView = ({
    group,
    renders,
    expandedIndex,
    showFullOutput,
    showTiming,
    onToggleExpand,
    onToggleFullOutput,
}: {
    group: DuplicateGroup & {items: Render[]};
    renders: Render[];
    expandedIndex: number | null;
    showFullOutput: Set<number>;
    showTiming: boolean;
    onToggleExpand: (index: number | null) => void;
    onToggleFullOutput: (index: number) => void;
}) => {
    const [expanded, setExpanded] = useState(false);

    return (
        <Box>
            <GroupHeader onClick={() => setExpanded(!expanded)}>
                <Box sx={{flex: 1, minWidth: 0}}>
                    <FileLink path={group.key}>
                        <Typography
                            component="span"
                            sx={{
                                fontFamily: primitives.fontFamilyMono,
                                fontSize: '13px',
                                fontWeight: 500,
                                color: 'primary.main',
                                '&:hover': {textDecoration: 'underline'},
                            }}
                        >
                            {basename(group.key)}
                        </Typography>
                    </FileLink>
                    <Typography sx={{fontFamily: primitives.fontFamilyMono, fontSize: '10px', color: 'text.disabled'}}>
                        {dirname(group.key)}
                    </Typography>
                </Box>
                <Chip
                    label={`${group.count}x`}
                    size="small"
                    color="warning"
                    sx={{fontWeight: 700, fontSize: '11px', height: 22, borderRadius: 1, flexShrink: 0}}
                />
                <IconButton
                    size="small"
                    aria-expanded={expanded}
                    aria-label={expanded ? 'Collapse group' : 'Expand group'}
                    sx={{flexShrink: 0}}
                >
                    <Icon sx={{fontSize: 16}}>{expanded ? 'expand_less' : 'expand_more'}</Icon>
                </IconButton>
            </GroupHeader>
            <Collapse in={expanded} unmountOnExit>
                <Box sx={{pl: 2}}>
                    {group.indices.map((originalIndex) => {
                        const entry = renders[originalIndex];
                        if (!entry) return null;
                        return (
                            <RenderItem
                                key={originalIndex}
                                render={entry}
                                index={originalIndex}
                                expanded={expandedIndex === originalIndex}
                                showFullOutput={showFullOutput.has(originalIndex)}
                                showTiming={showTiming}
                                onToggle={() => onToggleExpand(expandedIndex === originalIndex ? null : originalIndex)}
                                onToggleFullOutput={onToggleFullOutput}
                            />
                        );
                    })}
                </Box>
            </Collapse>
        </Box>
    );
};

export const TemplatePanel = ({data}: TemplatePanelProps) => {
    const [filter, setFilter] = useState('');
    const deferredFilter = useDeferredValue(filter);
    const [expandedIndex, setExpandedIndex] = useState<number | null>(null);
    const [showFullOutput, setShowFullOutput] = useState<Set<number>>(new Set());
    const [viewMode, setViewMode] = useState<'flat' | 'grouped'>('flat');

    const renders = data?.renders ?? [];
    const duplicates = data?.duplicates ?? {groups: [], totalDuplicatedCount: 0};
    const showTiming = hasTiming(renders);
    const hasDuplicates = duplicates.groups.length > 0;

    const filtered = useMemo(() => {
        if (!deferredFilter) return renders;
        return renders.filter((r) => r.template.toLowerCase().includes(deferredFilter.toLowerCase()));
    }, [renders, deferredFilter]);

    const groupedView = useMemo(() => {
        if (!hasDuplicates || viewMode !== 'grouped') return null;
        const filterLower = deferredFilter.toLowerCase();
        return duplicates.groups
            .filter((group) => !deferredFilter || group.key.toLowerCase().includes(filterLower))
            .map((group) => ({...group, items: group.indices.map((i) => renders[i]).filter(Boolean)}));
    }, [hasDuplicates, viewMode, duplicates.groups, renders, deferredFilter]);

    if (renders.length === 0) {
        return <EmptyState icon="code" title="No template renders found" />;
    }

    const toggleFullOutput = (index: number) => {
        setShowFullOutput((prev) => {
            const next = new Set(prev);
            if (next.has(index)) {
                next.delete(index);
            } else {
                next.add(index);
            }
            return next;
        });
    };

    return (
        <Box>
            <SectionTitle
                action={
                    <Box sx={{display: 'flex', alignItems: 'center', gap: 1}}>
                        {hasDuplicates && (
                            <ToggleButtonGroup
                                value={viewMode}
                                exclusive
                                onChange={(_e, value) => value && setViewMode(value)}
                                size="small"
                                sx={{height: 28}}
                            >
                                <ToggleButton value="flat" sx={{fontSize: '11px', px: 1.5, textTransform: 'none'}}>
                                    All
                                </ToggleButton>
                                <ToggleButton value="grouped" sx={{fontSize: '11px', px: 1.5, textTransform: 'none'}}>
                                    <Tooltip title="Show duplicate renders (N+1)" placement="top">
                                        <Box sx={{display: 'flex', alignItems: 'center', gap: 0.5}}>
                                            Duplicates
                                            <Chip
                                                label={duplicates.groups.length}
                                                size="small"
                                                color="warning"
                                                sx={{fontSize: '10px', height: 18, minWidth: 20, borderRadius: 1}}
                                            />
                                        </Box>
                                    </Tooltip>
                                </ToggleButton>
                            </ToggleButtonGroup>
                        )}
                        <FilterInput value={filter} onChange={setFilter} placeholder="Filter templates..." />
                    </Box>
                }
            >
                {`${filtered.length} renders`}
                {showTiming && ` · ${formatMillisecondsAsDuration(data.totalTime)} total`}
                {hasDuplicates && (
                    <Chip
                        label={`N+1`}
                        size="small"
                        color="warning"
                        sx={{fontSize: '10px', height: 18, borderRadius: 1, ml: 1}}
                    />
                )}
            </SectionTitle>

            {viewMode === 'grouped' && groupedView
                ? groupedView.map((group) => (
                      <DuplicateGroupView
                          key={group.key}
                          group={group}
                          renders={renders}
                          expandedIndex={expandedIndex}
                          showFullOutput={showFullOutput}
                          showTiming={showTiming}
                          onToggleExpand={setExpandedIndex}
                          onToggleFullOutput={toggleFullOutput}
                      />
                  ))
                : filtered.map((render, index) => (
                      <RenderItem
                          key={index}
                          render={render}
                          index={index}
                          expanded={expandedIndex === index}
                          showFullOutput={showFullOutput.has(index)}
                          showTiming={showTiming}
                          onToggle={() => setExpandedIndex(expandedIndex === index ? null : index)}
                          onToggleFullOutput={toggleFullOutput}
                      />
                  ))}
        </Box>
    );
};
