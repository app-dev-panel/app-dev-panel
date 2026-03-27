import {JsonRenderer} from '@app-dev-panel/panel/Module/Debug/Component/JsonRenderer';
import {EmptyState} from '@app-dev-panel/sdk/Component/EmptyState';
import {FilterInput} from '@app-dev-panel/sdk/Component/FilterInput';
import {SectionTitle} from '@app-dev-panel/sdk/Component/SectionTitle';
import {primitives} from '@app-dev-panel/sdk/Component/Theme/tokens';
import {
    Box,
    Chip,
    Collapse,
    Icon,
    IconButton,
    ToggleButton,
    ToggleButtonGroup,
    Tooltip,
    Typography,
} from '@mui/material';
import {styled} from '@mui/material/styles';
import {useDeferredValue, useMemo, useState} from 'react';

type WebViewEntry = {output: string; file: string; parameters: any[]};
type DuplicateGroup = {key: string; count: number; indices: number[]};
type DuplicatesData = {groups: DuplicateGroup[]; totalDuplicatedCount: number};
type WebViewPanelProps = {data: WebViewEntry[] | {renders: WebViewEntry[]; duplicates: DuplicatesData}};

const OUTPUT_PREVIEW_LENGTH = 300;

function basename(filePath: string): string {
    const parts = filePath.replace(/\\/g, '/').split('/');
    return parts[parts.length - 1] ?? filePath;
}

function dirname(filePath: string): string {
    const parts = filePath.replace(/\\/g, '/').split('/');
    parts.pop();
    return parts.join('/');
}

function normalizeData(
    data: WebViewPanelProps['data'],
): {renders: WebViewEntry[]; duplicates: DuplicatesData} | null {
    if (!data) return null;
    if (Array.isArray(data)) {
        return {renders: data, duplicates: {groups: [], totalDuplicatedCount: 0}};
    }
    return data;
}

const ViewRow = styled(Box, {shouldForwardProp: (p) => p !== 'expanded'})<{expanded?: boolean}>(
    ({theme, expanded}) => ({
        display: 'flex',
        alignItems: 'flex-start',
        gap: theme.spacing(1.5),
        padding: theme.spacing(1, 1.5),
        borderBottom: `1px solid ${theme.palette.divider}`,
        cursor: 'pointer',
        transition: 'background-color 0.1s ease',
        backgroundColor: expanded ? theme.palette.action.hover : 'transparent',
        '&:hover': {backgroundColor: theme.palette.action.hover},
    }),
);

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

const RenderItem = ({
    entry,
    index,
    expanded,
    showFullOutput,
    onToggle,
    onToggleFullOutput,
}: {
    entry: WebViewEntry;
    index: number;
    expanded: boolean;
    showFullOutput: boolean;
    onToggle: () => void;
    onToggleFullOutput: (index: number) => void;
}) => {
    const isTruncated = entry.output.length > OUTPUT_PREVIEW_LENGTH;
    return (
        <Box key={index}>
            <ViewRow expanded={expanded} onClick={onToggle}>
                <Box sx={{flex: 1, minWidth: 0}}>
                    <Typography sx={{fontFamily: primitives.fontFamilyMono, fontSize: '13px', fontWeight: 500}}>
                        {basename(entry.file)}
                    </Typography>
                    <Typography sx={{fontFamily: primitives.fontFamilyMono, fontSize: '10px', color: 'text.disabled'}}>
                        {dirname(entry.file)}
                    </Typography>
                </Box>
                {entry.parameters.length > 0 && (
                    <Chip
                        label={`${entry.parameters.length} param${entry.parameters.length !== 1 ? 's' : ''}`}
                        size="small"
                        variant="outlined"
                        sx={{fontSize: '10px', height: 20, borderRadius: 1, flexShrink: 0}}
                    />
                )}
                <IconButton size="small" sx={{flexShrink: 0}}>
                    <Icon sx={{fontSize: 16}}>{expanded ? 'expand_less' : 'expand_more'}</Icon>
                </IconButton>
            </ViewRow>
            <Collapse in={expanded}>
                <DetailBox>
                    {entry.output && (
                        <Box sx={{mb: 1.5}}>
                            <Typography sx={{fontSize: '11px', fontWeight: 600, color: 'text.disabled', mb: 0.5}}>
                                Output
                            </Typography>
                            <OutputPreview sx={{color: 'text.secondary'}}>
                                {showFullOutput || !isTruncated
                                    ? entry.output
                                    : entry.output.substring(0, OUTPUT_PREVIEW_LENGTH) + '...'}
                            </OutputPreview>
                            {isTruncated && (
                                <Typography
                                    onClick={(e) => {
                                        e.stopPropagation();
                                        onToggleFullOutput(index);
                                    }}
                                    sx={{
                                        fontSize: '11px',
                                        color: 'primary.main',
                                        cursor: 'pointer',
                                        mt: 0.5,
                                        '&:hover': {textDecoration: 'underline'},
                                    }}
                                >
                                    {showFullOutput ? 'Show less' : 'Show more'}
                                </Typography>
                            )}
                        </Box>
                    )}
                    {entry.parameters.length > 0 && (
                        <Box>
                            <Typography sx={{fontSize: '11px', fontWeight: 600, color: 'text.disabled', mb: 0.5}}>
                                Parameters
                            </Typography>
                            <JsonRenderer value={entry.parameters} />
                        </Box>
                    )}
                </DetailBox>
            </Collapse>
        </Box>
    );
};

export const WebViewPanel = ({data}: WebViewPanelProps) => {
    const normalized = normalizeData(data);
    const renders = normalized?.renders ?? [];
    const duplicates = normalized?.duplicates ?? {groups: [], totalDuplicatedCount: 0};
    const [filter, setFilter] = useState('');
    const deferredFilter = useDeferredValue(filter);
    const [expandedIndex, setExpandedIndex] = useState<number | null>(null);
    const [showFullOutput, setShowFullOutput] = useState<Set<number>>(new Set());
    const [viewMode, setViewMode] = useState<'flat' | 'grouped'>('flat');

    const hasDuplicates = duplicates.groups.length > 0;

    const filtered = useMemo(() => {
        if (!deferredFilter) return renders;
        return renders.filter((entry) => entry.file.toLowerCase().includes(deferredFilter.toLowerCase()));
    }, [renders, deferredFilter]);

    const groupedView = useMemo(() => {
        if (!hasDuplicates || viewMode !== 'grouped') return null;
        const filterLower = deferredFilter.toLowerCase();
        return duplicates.groups
            .filter((group) => !deferredFilter || group.key.toLowerCase().includes(filterLower))
            .map((group) => ({...group, items: group.indices.map((i) => renders[i]).filter(Boolean)}));
    }, [hasDuplicates, viewMode, duplicates.groups, renders, deferredFilter]);

    if (!renders || renders.length === 0) {
        return <EmptyState icon="web" title="No WebView renders found" />;
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
                        <FilterInput value={filter} onChange={setFilter} placeholder="Filter files..." />
                    </Box>
                }
            >
                {`${filtered.length} renders`}
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
                          onToggleExpand={setExpandedIndex}
                          onToggleFullOutput={toggleFullOutput}
                      />
                  ))
                : filtered.map((entry, index) => (
                      <RenderItem
                          key={index}
                          entry={entry}
                          index={index}
                          expanded={expandedIndex === index}
                          showFullOutput={showFullOutput.has(index)}
                          onToggle={() => setExpandedIndex(expandedIndex === index ? null : index)}
                          onToggleFullOutput={toggleFullOutput}
                      />
                  ))}
        </Box>
    );
};

const DuplicateGroupView = ({
    group,
    renders,
    expandedIndex,
    showFullOutput,
    onToggleExpand,
    onToggleFullOutput,
}: {
    group: DuplicateGroup & {items: WebViewEntry[]};
    renders: WebViewEntry[];
    expandedIndex: number | null;
    showFullOutput: Set<number>;
    onToggleExpand: (index: number | null) => void;
    onToggleFullOutput: (index: number) => void;
}) => {
    const [expanded, setExpanded] = useState(false);

    return (
        <Box>
            <GroupHeader onClick={() => setExpanded(!expanded)}>
                <Box sx={{flex: 1, minWidth: 0}}>
                    <Typography sx={{fontFamily: primitives.fontFamilyMono, fontSize: '13px', fontWeight: 500}}>
                        {basename(group.key)}
                    </Typography>
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
                <IconButton size="small" sx={{flexShrink: 0}}>
                    <Icon sx={{fontSize: 16}}>{expanded ? 'expand_less' : 'expand_more'}</Icon>
                </IconButton>
            </GroupHeader>
            <Collapse in={expanded}>
                <Box sx={{pl: 2}}>
                    {group.indices.map((originalIndex) => {
                        const entry = renders[originalIndex];
                        if (!entry) return null;
                        return (
                            <RenderItem
                                key={originalIndex}
                                entry={entry}
                                index={originalIndex}
                                expanded={expandedIndex === originalIndex}
                                showFullOutput={showFullOutput.has(originalIndex)}
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
