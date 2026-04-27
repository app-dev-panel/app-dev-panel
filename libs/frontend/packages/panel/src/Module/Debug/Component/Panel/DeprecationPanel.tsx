import {ClassName} from '@app-dev-panel/panel/Application/Component/ClassName';
import {EmptyState} from '@app-dev-panel/sdk/Component/EmptyState';
import {FilterInput} from '@app-dev-panel/sdk/Component/FilterInput';
import {PageToolbar} from '@app-dev-panel/sdk/Component/PageToolbar';
import {formatMicrotime} from '@app-dev-panel/sdk/Helper/formatDate';
import {searchVariants} from '@app-dev-panel/sdk/Helper/layoutTranslit';
import {Box, Chip, Collapse, Icon, IconButton, type Theme, Typography} from '@mui/material';
import {styled, useTheme} from '@mui/material/styles';
import {useDeferredValue, useMemo, useState} from 'react';

type Category = 'user' | 'php';
type TraceFrame = {file: string; line: number; function: string; class: string};
type DeprecationEntry = {
    time: number;
    message: string;
    file: string;
    line: number;
    category: Category;
    trace: TraceFrame[];
};
type DeprecationPanelProps = {data: DeprecationEntry[]};

const categoryColor = (category: Category, theme: Theme): string => {
    switch (category) {
        case 'user':
            return theme.palette.warning.main;
        case 'php':
            return theme.palette.error.main;
        default:
            return theme.palette.text.disabled;
    }
};

const categoryLabel = (category: Category): string => {
    switch (category) {
        case 'user':
            return 'USER';
        case 'php':
            return 'PHP';
        default:
            return (category as string).toUpperCase();
    }
};

const Row = styled(Box, {shouldForwardProp: (p) => p !== 'expanded'})<{expanded?: boolean}>(({theme, expanded}) => ({
    display: 'flex',
    alignItems: 'flex-start',
    gap: theme.spacing(1.5),
    padding: theme.spacing(1, 1.5),
    borderBottom: `1px solid ${theme.palette.divider}`,
    cursor: 'pointer',
    transition: 'background-color 0.1s ease',
    backgroundColor: expanded ? theme.palette.action.hover : 'transparent',
    '&:hover': {backgroundColor: theme.palette.action.hover},
}));

const TimeCell = styled(Typography)(({theme}) => ({
    fontFamily: theme.adp.fontFamilyMono,
    fontSize: '11px',
    flexShrink: 0,
    width: 110,
    paddingTop: 2,
}));

const MessageCell = styled(Typography)({fontSize: '13px', flex: 1, wordBreak: 'break-word'});

const LocationCell = styled(Typography)(({theme}) => ({
    fontFamily: theme.adp.fontFamilyMono,
    fontSize: '11px',
    flexShrink: 0,
    maxWidth: 300,
    overflow: 'hidden',
    textOverflow: 'ellipsis',
    whiteSpace: 'nowrap',
    paddingTop: 2,
}));

const DetailBox = styled(Box)(({theme}) => ({
    padding: theme.spacing(1.5, 1.5, 1.5, 15),
    backgroundColor: theme.palette.action.hover,
    borderBottom: `1px solid ${theme.palette.divider}`,
    fontSize: '12px',
}));

const TraceRow = styled(Box)(({theme}) => ({
    fontFamily: theme.adp.fontFamilyMono,
    fontSize: '11px',
    padding: theme.spacing(0.25, 0),
    color: theme.palette.text.secondary,
    '&:hover': {color: theme.palette.text.primary},
}));

export const DeprecationPanel = ({data}: DeprecationPanelProps) => {
    const theme = useTheme();
    const [filter, setFilter] = useState('');
    const deferredFilter = useDeferredValue(filter);
    const [expandedIndex, setExpandedIndex] = useState<number | null>(null);
    const [activeCategories, setActiveCategories] = useState<Set<Category>>(new Set());

    if (!data || data.length === 0) {
        return <EmptyState icon="warning_amber" title="No deprecations found" />;
    }

    const categoryCounts = useMemo(() => {
        const counts = new Map<Category, number>();
        for (const entry of data) {
            counts.set(entry.category, (counts.get(entry.category) || 0) + 1);
        }
        return counts;
    }, [data]);

    const presentCategories = useMemo(
        () => (['user', 'php'] as Category[]).filter((c) => (categoryCounts.get(c) || 0) > 0),
        [categoryCounts],
    );

    const toggleCategory = (category: Category) => {
        setActiveCategories((prev) => {
            const next = new Set(prev);
            if (next.has(category)) {
                next.delete(category);
            } else {
                next.add(category);
            }
            return next;
        });
        setExpandedIndex(null);
    };

    const filtered = useMemo(() => {
        let result = data;

        if (activeCategories.size > 0) {
            result = result.filter((e) => activeCategories.has(e.category));
        }

        if (deferredFilter) {
            const variants = searchVariants(deferredFilter.toLowerCase());
            result = result.filter((e) => {
                const message = e.message.toLowerCase();
                const file = e.file.toLowerCase();
                return variants.some((v) => message.includes(v) || file.includes(v));
            });
        }

        return result;
    }, [data, activeCategories, deferredFilter]);

    return (
        <Box>
            <PageToolbar
                sticky
                actions={<FilterInput value={filter} onChange={setFilter} placeholder="Filter deprecations..." />}
            >{`${filtered.length} deprecation${filtered.length !== 1 ? 's' : ''}`}</PageToolbar>

            {presentCategories.length > 1 && (
                <Box sx={{display: 'flex', flexWrap: 'wrap', gap: 0.75, mb: 2, px: {xs: 1.5, sm: 2.5}, pt: 1.5}}>
                    {presentCategories.map((category) => {
                        const color = categoryColor(category, theme);
                        const isActive = activeCategories.has(category);
                        return (
                            <Chip
                                key={category}
                                label={`${categoryLabel(category)} (${categoryCounts.get(category)})`}
                                size="small"
                                onClick={() => toggleCategory(category)}
                                variant={isActive ? 'filled' : 'outlined'}
                                sx={{
                                    fontSize: '11px',
                                    height: 24,
                                    borderRadius: 1,
                                    fontWeight: 600,
                                    cursor: 'pointer',
                                    borderColor: color,
                                    ...(isActive
                                        ? {backgroundColor: color, color: theme.palette.common.white}
                                        : {color}),
                                }}
                            />
                        );
                    })}
                    {activeCategories.size > 0 && (
                        <Chip
                            label="Clear"
                            size="small"
                            onClick={() => setActiveCategories(new Set())}
                            variant="outlined"
                            sx={{fontSize: '11px', height: 24, borderRadius: 1}}
                        />
                    )}
                </Box>
            )}

            {filtered.map((entry, index) => {
                const expanded = expandedIndex === index;
                const color = categoryColor(entry.category, theme);
                return (
                    <Box key={index}>
                        <Row expanded={expanded} onClick={() => setExpandedIndex(expanded ? null : index)}>
                            <TimeCell sx={{color: 'text.disabled'}}>{formatMicrotime(entry.time)}</TimeCell>
                            <Chip
                                label={categoryLabel(entry.category)}
                                size="small"
                                sx={{
                                    fontWeight: 600,
                                    fontSize: '10px',
                                    height: 20,
                                    minWidth: 44,
                                    backgroundColor: color,
                                    color: 'common.white',
                                    borderRadius: 1,
                                }}
                            />
                            <MessageCell>{entry.message}</MessageCell>
                            <LocationCell sx={{color: 'text.disabled'}}>
                                {entry.file}:{entry.line}
                            </LocationCell>
                            <IconButton size="small" sx={{flexShrink: 0}}>
                                <Icon sx={{fontSize: 16}}>{expanded ? 'expand_less' : 'expand_more'}</Icon>
                            </IconButton>
                        </Row>
                        <Collapse in={expanded}>
                            <DetailBox>
                                <Typography variant="caption" sx={{fontWeight: 600, mb: 0.5, display: 'block'}}>
                                    {entry.file}:{entry.line}
                                </Typography>
                                {entry.trace && entry.trace.length > 0 && (
                                    <Box sx={{mt: 1}}>
                                        <Typography
                                            variant="caption"
                                            sx={{fontWeight: 600, mb: 0.5, display: 'block', color: 'text.disabled'}}
                                        >
                                            Stack Trace
                                        </Typography>
                                        {entry.trace.map((frame, i) => (
                                            <TraceRow key={i}>
                                                {frame.file && `${frame.file}:${frame.line} `}
                                                {frame.class ? (
                                                    <ClassName value={frame.class} methodName={frame.function}>
                                                        {`${frame.class}::${frame.function}()`}
                                                    </ClassName>
                                                ) : (
                                                    `${frame.function}()`
                                                )}
                                            </TraceRow>
                                        ))}
                                    </Box>
                                )}
                            </DetailBox>
                        </Collapse>
                    </Box>
                );
            })}
        </Box>
    );
};
