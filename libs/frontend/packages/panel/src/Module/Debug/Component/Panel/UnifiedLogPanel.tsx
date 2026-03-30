import {JsonRenderer} from '@app-dev-panel/panel/Module/Debug/Component/JsonRenderer';
import {VarDumpValue} from '@app-dev-panel/panel/Module/Debug/Component/VarDumpValue';
import {EmptyState} from '@app-dev-panel/sdk/Component/EmptyState';
import {FileLink} from '@app-dev-panel/sdk/Component/FileLink';
import {FilterInput} from '@app-dev-panel/sdk/Component/FilterInput';
import {SectionTitle} from '@app-dev-panel/sdk/Component/SectionTitle';
import {primitives} from '@app-dev-panel/sdk/Component/Theme/tokens';
import {formatMicrotime} from '@app-dev-panel/sdk/Helper/formatDate';
import {searchVariants} from '@app-dev-panel/sdk/Helper/layoutTranslit';
import {usePathMapper} from '@app-dev-panel/sdk/Helper/usePathMapper';
import {Box, Chip, Collapse, Icon, IconButton, type Theme, Typography} from '@mui/material';
import {styled, useTheme} from '@mui/material/styles';
import {useDeferredValue, useMemo, useState} from 'react';

// ---------------------------------------------------------------------------
// Types
// ---------------------------------------------------------------------------

type Level = 'emergency' | 'alert' | 'critical' | 'error' | 'warning' | 'notice' | 'info' | 'debug';
type DeprecationCategory = 'user' | 'php';
type TraceFrame = {file: string; line: number; function: string; class: string};

type LogEntry = {context: object; level: Level; line: string; message: unknown; time: number};
type DeprecationEntry = {
    time: number;
    message: string;
    file: string;
    line: number;
    category: DeprecationCategory;
    trace: TraceFrame[];
};
type VarDumperEntry = {variable: unknown; line: string};

type EntryKind = 'log' | 'deprecation' | 'dump';

type UnifiedEntry =
    | {kind: 'log'; time: number; data: LogEntry}
    | {kind: 'deprecation'; time: number; data: DeprecationEntry}
    | {kind: 'dump'; time: number; data: VarDumperEntry; index: number};

type UnifiedLogPanelProps = {logs: LogEntry[]; deprecations: DeprecationEntry[]; dumps: VarDumperEntry[]};

// ---------------------------------------------------------------------------
// Color helpers
// ---------------------------------------------------------------------------

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

const categoryColor = (category: DeprecationCategory, theme: Theme): string =>
    category === 'php' ? theme.palette.error.main : theme.palette.warning.main;

const kindColor = (kind: EntryKind, theme: Theme): string => {
    switch (kind) {
        case 'log':
            return theme.palette.primary.main;
        case 'deprecation':
            return theme.palette.warning.main;
        case 'dump':
            return theme.palette.info?.main ?? theme.palette.primary.main;
    }
};

const kindIcon = (kind: EntryKind): string => {
    switch (kind) {
        case 'log':
            return 'description';
        case 'deprecation':
            return 'warning_amber';
        case 'dump':
            return 'data_object';
    }
};

const SEVERITY_ORDER: Level[] = ['emergency', 'alert', 'critical', 'error', 'warning', 'notice', 'info', 'debug'];

// ---------------------------------------------------------------------------
// Styled components
// ---------------------------------------------------------------------------

const EntryRow = styled(Box, {shouldForwardProp: (p) => p !== 'expanded' && p !== 'borderColor'})<{
    expanded?: boolean;
    borderColor?: string;
}>(({theme, expanded, borderColor}) => ({
    display: 'flex',
    alignItems: 'flex-start',
    gap: theme.spacing(1.5),
    padding: theme.spacing(1, 1.5),
    borderBottom: `1px solid ${theme.palette.divider}`,
    borderLeft: `3px solid ${borderColor || 'transparent'}`,
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
    paddingTop: 2,
});

const MessageCell = styled(Typography)({fontSize: '13px', flex: 1, wordBreak: 'break-word', minWidth: 0});

const DetailBox = styled(Box)(({theme}) => ({
    padding: theme.spacing(1.5, 1.5, 1.5, 15),
    backgroundColor: theme.palette.action.hover,
    borderBottom: `1px solid ${theme.palette.divider}`,
    fontSize: '12px',
}));

const TraceRow = styled(Box)(({theme}) => ({
    fontFamily: primitives.fontFamilyMono,
    fontSize: '11px',
    padding: theme.spacing(0.25, 0),
    color: theme.palette.text.secondary,
    '&:hover': {color: theme.palette.text.primary},
}));

const DumpBody = styled(Box)(({theme}) => ({
    padding: theme.spacing(1.5, 2),
    backgroundColor: theme.palette.mode === 'dark' ? theme.palette.background.default : theme.palette.grey[50],
    borderRadius: theme.shape.borderRadius,
    overflow: 'auto',
}));

const CountBadge = styled(Box)(({theme}) => ({
    display: 'inline-flex',
    alignItems: 'center',
    gap: theme.spacing(0.5),
    padding: theme.spacing(0.25, 1),
    borderRadius: theme.shape.borderRadius,
    fontSize: '11px',
    fontWeight: 600,
}));

// ---------------------------------------------------------------------------
// Helpers
// ---------------------------------------------------------------------------

const formatMessage = (message: unknown): string => (typeof message === 'string' ? message : JSON.stringify(message));

function buildUnifiedEntries(
    logs: LogEntry[],
    deprecations: DeprecationEntry[],
    dumps: VarDumperEntry[],
): UnifiedEntry[] {
    const entries: UnifiedEntry[] = [];

    for (const log of logs) {
        entries.push({kind: 'log', time: log.time, data: log});
    }
    for (const dep of deprecations) {
        entries.push({kind: 'deprecation', time: dep.time, data: dep});
    }
    for (let i = 0; i < dumps.length; i++) {
        // Dumps don't have timestamps — place them at the end with index 0
        entries.push({kind: 'dump', time: 0, data: dumps[i], index: i});
    }

    // Sort by time descending; dumps (time=0) go to the end
    entries.sort((a, b) => {
        if (a.time === 0 && b.time === 0) return 0;
        if (a.time === 0) return 1;
        if (b.time === 0) return -1;
        return a.time - b.time;
    });

    return entries;
}

// ---------------------------------------------------------------------------
// Component
// ---------------------------------------------------------------------------

export const UnifiedLogPanel = ({logs, deprecations, dumps}: UnifiedLogPanelProps) => {
    const theme = useTheme();
    const pathMapper = usePathMapper();
    const [filter, setFilter] = useState('');
    const deferredFilter = useDeferredValue(filter);
    const [expandedIndex, setExpandedIndex] = useState<number | null>(null);
    const [activeKinds, setActiveKinds] = useState<Set<EntryKind>>(new Set());
    const [activeLevels, setActiveLevels] = useState<Set<Level>>(new Set());
    const [activeCategories, setActiveCategories] = useState<Set<DeprecationCategory>>(new Set());

    // Build unified entry list
    const allEntries = useMemo(
        () => buildUnifiedEntries(logs || [], deprecations || [], dumps || []),
        [logs, deprecations, dumps],
    );

    // Level counts (for log sub-filters)
    const levelCounts = useMemo(() => {
        const counts = new Map<Level, number>();
        for (const entry of allEntries) {
            if (entry.kind === 'log') {
                const level = entry.data.level;
                counts.set(level, (counts.get(level) || 0) + 1);
            }
        }
        return counts;
    }, [allEntries]);

    const presentLevels = useMemo(
        () => SEVERITY_ORDER.filter((level) => (levelCounts.get(level) || 0) > 0),
        [levelCounts],
    );

    // Category counts (for deprecation sub-filters)
    const categoryCounts = useMemo(() => {
        const counts = new Map<DeprecationCategory, number>();
        for (const entry of allEntries) {
            if (entry.kind === 'deprecation') {
                const cat = entry.data.category;
                counts.set(cat, (counts.get(cat) || 0) + 1);
            }
        }
        return counts;
    }, [allEntries]);

    const presentCategories = useMemo(
        () => (['user', 'php'] as DeprecationCategory[]).filter((c) => (categoryCounts.get(c) || 0) > 0),
        [categoryCounts],
    );

    // Kind counts
    const kindCounts = useMemo(() => {
        const counts: Record<EntryKind, number> = {log: 0, deprecation: 0, dump: 0};
        for (const entry of allEntries) {
            counts[entry.kind]++;
        }
        return counts;
    }, [allEntries]);

    const presentKinds = useMemo(
        () => (['log', 'deprecation', 'dump'] as EntryKind[]).filter((k) => kindCounts[k] > 0),
        [kindCounts],
    );

    // Toggle helpers
    const toggleKind = (kind: EntryKind) => {
        setActiveKinds((prev) => {
            const next = new Set(prev);
            if (next.has(kind)) {
                next.delete(kind);
            } else {
                next.add(kind);
            }
            return next;
        });
        setExpandedIndex(null);
    };

    const toggleLevel = (level: Level) => {
        setActiveLevels((prev) => {
            const next = new Set(prev);
            if (next.has(level)) {
                next.delete(level);
            } else {
                next.add(level);
            }
            return next;
        });
        setExpandedIndex(null);
    };

    const toggleCategory = (category: DeprecationCategory) => {
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

    const clearAllFilters = () => {
        setActiveKinds(new Set());
        setActiveLevels(new Set());
        setActiveCategories(new Set());
        setExpandedIndex(null);
    };

    const hasAnyActiveFilter = activeKinds.size > 0 || activeLevels.size > 0 || activeCategories.size > 0;

    // Determine which sub-filters to show
    const showLogSubFilters = hasLogs && (activeKinds.size === 0 || activeKinds.has('log')) && presentLevels.length > 1;
    const showDeprecationSubFilters =
        hasDeprecations && (activeKinds.size === 0 || activeKinds.has('deprecation')) && presentCategories.length > 0;

    // Filter entries
    const filtered = useMemo(() => {
        let result = allEntries;

        // Filter by kind
        if (activeKinds.size > 0) {
            result = result.filter((e) => activeKinds.has(e.kind));
        }

        // Filter by log level
        if (activeLevels.size > 0) {
            result = result.filter((e) => e.kind !== 'log' || activeLevels.has((e.data as LogEntry).level));
        }

        // Filter by deprecation category
        if (activeCategories.size > 0) {
            result = result.filter(
                (e) => e.kind !== 'deprecation' || activeCategories.has((e.data as DeprecationEntry).category),
            );
        }

        // Text search
        if (deferredFilter) {
            const variants = searchVariants(deferredFilter.toLowerCase());
            result = result.filter((e) => {
                switch (e.kind) {
                    case 'log': {
                        const msg = formatMessage(e.data.message).toLowerCase();
                        const lvl = e.data.level.toLowerCase();
                        return variants.some((v) => msg.includes(v) || lvl.includes(v));
                    }
                    case 'deprecation': {
                        const msg = e.data.message.toLowerCase();
                        const file = e.data.file.toLowerCase();
                        return variants.some((v) => msg.includes(v) || file.includes(v));
                    }
                    case 'dump': {
                        const line = e.data.line.toLowerCase();
                        const val = JSON.stringify(e.data.variable).toLowerCase();
                        return variants.some((v) => line.includes(v) || val.includes(v));
                    }
                }
            });
        }

        return result;
    }, [allEntries, activeKinds, activeLevels, activeCategories, deferredFilter]);

    // ---------------------------------------------------------------------------
    // Render
    // ---------------------------------------------------------------------------

    if (allEntries.length === 0) {
        return <EmptyState icon="description" title="No logs, deprecations, or dumps" />;
    }

    return (
        <Box>
            {/* Header with counts */}
            <SectionTitle
                action={<FilterInput value={filter} onChange={setFilter} placeholder="Filter entries..." />}
            >{`${filtered.length} entries`}</SectionTitle>

            {/* Type filter chips */}
            {presentKinds.length > 1 && (
                <Box sx={{display: 'flex', flexWrap: 'wrap', gap: 0.75, mb: 1.5}}>
                    {presentKinds.map((kind) => {
                        const color = kindColor(kind, theme);
                        const isActive = activeKinds.has(kind);
                        const label = kind === 'log' ? 'Logs' : kind === 'deprecation' ? 'Deprecations' : 'Dumps';
                        return (
                            <Chip
                                key={kind}
                                icon={<Icon sx={{fontSize: '14px !important'}}>{kindIcon(kind)}</Icon>}
                                label={
                                    <CountBadge>
                                        {label} ({kindCounts[kind]})
                                    </CountBadge>
                                }
                                size="small"
                                onClick={() => toggleKind(kind)}
                                variant={isActive ? 'filled' : 'outlined'}
                                sx={{
                                    height: 28,
                                    borderRadius: 1,
                                    fontWeight: 600,
                                    cursor: 'pointer',
                                    borderColor: color,
                                    ...(isActive
                                        ? {
                                              backgroundColor: color,
                                              color: theme.palette.common.white,
                                              '& .MuiIcon-root': {color: theme.palette.common.white},
                                          }
                                        : {color, '& .MuiIcon-root': {color}}),
                                }}
                            />
                        );
                    })}
                    {hasAnyActiveFilter && (
                        <Chip
                            label="Clear all"
                            size="small"
                            onClick={clearAllFilters}
                            variant="outlined"
                            sx={{height: 28, borderRadius: 1, fontSize: '11px'}}
                        />
                    )}
                </Box>
            )}

            {/* Log level sub-filters */}
            {showLogSubFilters && (
                <Box sx={{display: 'flex', flexWrap: 'wrap', gap: 0.5, mb: 1.5, pl: 1}}>
                    {presentLevels.map((level) => {
                        const color = levelColor(level, theme);
                        const isActive = activeLevels.has(level);
                        return (
                            <Chip
                                key={level}
                                label={`${level.toUpperCase()} (${levelCounts.get(level)})`}
                                size="small"
                                onClick={() => toggleLevel(level)}
                                variant={isActive ? 'filled' : 'outlined'}
                                sx={{
                                    fontSize: '10px',
                                    height: 22,
                                    borderRadius: 0.75,
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
                    {activeLevels.size > 0 && (
                        <Chip
                            label="Clear"
                            size="small"
                            onClick={() => setActiveLevels(new Set())}
                            variant="outlined"
                            sx={{fontSize: '10px', height: 22, borderRadius: 0.75}}
                        />
                    )}
                </Box>
            )}

            {/* Deprecation category sub-filters */}
            {showDeprecationSubFilters && (
                <Box sx={{display: 'flex', flexWrap: 'wrap', gap: 0.5, mb: 1.5, pl: 1}}>
                    {presentCategories.map((category) => {
                        const color = categoryColor(category, theme);
                        const isActive = activeCategories.has(category);
                        const label = category === 'php' ? 'PHP' : 'USER';
                        return (
                            <Chip
                                key={category}
                                label={`${label} (${categoryCounts.get(category)})`}
                                size="small"
                                onClick={() => toggleCategory(category)}
                                variant={isActive ? 'filled' : 'outlined'}
                                sx={{
                                    fontSize: '10px',
                                    height: 22,
                                    borderRadius: 0.75,
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
                            sx={{fontSize: '10px', height: 22, borderRadius: 0.75}}
                        />
                    )}
                </Box>
            )}

            {/* Unified entry list */}
            {filtered.map((entry, index) => {
                const expanded = expandedIndex === index;
                const border = kindColor(entry.kind, theme);

                switch (entry.kind) {
                    case 'log': {
                        const log = entry.data;
                        const color = levelColor(log.level, theme);
                        return (
                            <Box key={index}>
                                <EntryRow
                                    expanded={expanded}
                                    borderColor={border}
                                    onClick={() => setExpandedIndex(expanded ? null : index)}
                                >
                                    <TimeCell sx={{color: 'text.disabled'}}>{formatMicrotime(log.time)}</TimeCell>
                                    <Chip
                                        label={log.level.toUpperCase()}
                                        size="small"
                                        sx={{
                                            fontWeight: 600,
                                            fontSize: '10px',
                                            height: 20,
                                            minWidth: 60,
                                            backgroundColor: color,
                                            color: 'common.white',
                                            borderRadius: 1,
                                        }}
                                    />
                                    <MessageCell>{formatMessage(log.message)}</MessageCell>
                                    <IconButton size="small" sx={{flexShrink: 0}}>
                                        <Icon sx={{fontSize: 16}}>{expanded ? 'expand_less' : 'expand_more'}</Icon>
                                    </IconButton>
                                </EntryRow>
                                <Collapse in={expanded}>
                                    <DetailBox>
                                        {log.line && (
                                            <Box sx={{mb: 1}}>
                                                <FileLink path={pathMapper.toLocalWithLine(log.line)}>
                                                    <Typography
                                                        variant="caption"
                                                        component="span"
                                                        sx={{
                                                            fontFamily: primitives.fontFamilyMono,
                                                            color: 'primary.main',
                                                            textDecoration: 'none',
                                                            '&:hover': {textDecoration: 'underline'},
                                                        }}
                                                    >
                                                        {pathMapper.toLocalWithLine(log.line)}
                                                    </Typography>
                                                </FileLink>
                                            </Box>
                                        )}
                                        {log.context && Object.keys(log.context).length > 0 && (
                                            <JsonRenderer value={log.context} depth={2} />
                                        )}
                                    </DetailBox>
                                </Collapse>
                            </Box>
                        );
                    }

                    case 'deprecation': {
                        const dep = entry.data;
                        const color = categoryColor(dep.category, theme);
                        const categoryLabel = dep.category === 'php' ? 'PHP' : 'USER';
                        return (
                            <Box key={index}>
                                <EntryRow
                                    expanded={expanded}
                                    borderColor={border}
                                    onClick={() => setExpandedIndex(expanded ? null : index)}
                                >
                                    <TimeCell sx={{color: 'text.disabled'}}>{formatMicrotime(dep.time)}</TimeCell>
                                    <Chip
                                        label={`DEPR ${categoryLabel}`}
                                        size="small"
                                        sx={{
                                            fontWeight: 600,
                                            fontSize: '10px',
                                            height: 20,
                                            minWidth: 60,
                                            backgroundColor: color,
                                            color: 'common.white',
                                            borderRadius: 1,
                                        }}
                                    />
                                    <MessageCell>{dep.message}</MessageCell>
                                    <Typography
                                        sx={{
                                            fontFamily: primitives.fontFamilyMono,
                                            fontSize: '11px',
                                            flexShrink: 0,
                                            maxWidth: 250,
                                            overflow: 'hidden',
                                            textOverflow: 'ellipsis',
                                            whiteSpace: 'nowrap',
                                            color: 'text.disabled',
                                            pt: 0.25,
                                        }}
                                    >
                                        {dep.file}:{dep.line}
                                    </Typography>
                                    <IconButton size="small" sx={{flexShrink: 0}}>
                                        <Icon sx={{fontSize: 16}}>{expanded ? 'expand_less' : 'expand_more'}</Icon>
                                    </IconButton>
                                </EntryRow>
                                <Collapse in={expanded}>
                                    <DetailBox>
                                        <Typography variant="caption" sx={{fontWeight: 600, mb: 0.5, display: 'block'}}>
                                            {dep.file}:{dep.line}
                                        </Typography>
                                        {dep.trace && dep.trace.length > 0 && (
                                            <Box sx={{mt: 1}}>
                                                <Typography
                                                    variant="caption"
                                                    sx={{
                                                        fontWeight: 600,
                                                        mb: 0.5,
                                                        display: 'block',
                                                        color: 'text.disabled',
                                                    }}
                                                >
                                                    Stack Trace
                                                </Typography>
                                                {dep.trace.map((frame, i) => (
                                                    <TraceRow key={i}>
                                                        {frame.file && `${frame.file}:${frame.line} `}
                                                        {frame.class
                                                            ? `${frame.class}::${frame.function}()`
                                                            : `${frame.function}()`}
                                                    </TraceRow>
                                                ))}
                                            </Box>
                                        )}
                                    </DetailBox>
                                </Collapse>
                            </Box>
                        );
                    }

                    case 'dump': {
                        const dump = entry.data;
                        const dumpIndex = entry.index;
                        return (
                            <Box key={index}>
                                <EntryRow
                                    expanded={expanded}
                                    borderColor={border}
                                    onClick={() => setExpandedIndex(expanded ? null : index)}
                                >
                                    <Box
                                        sx={{
                                            width: 110,
                                            flexShrink: 0,
                                            display: 'flex',
                                            alignItems: 'center',
                                            gap: 0.75,
                                        }}
                                    >
                                        <Box
                                            sx={{
                                                width: 22,
                                                height: 22,
                                                borderRadius: '50%',
                                                backgroundColor: 'info.main',
                                                color: 'common.white',
                                                display: 'flex',
                                                alignItems: 'center',
                                                justifyContent: 'center',
                                                fontSize: '10px',
                                                fontWeight: 700,
                                                flexShrink: 0,
                                            }}
                                        >
                                            {dumpIndex + 1}
                                        </Box>
                                    </Box>
                                    <Chip
                                        label="DUMP"
                                        size="small"
                                        sx={{
                                            fontWeight: 600,
                                            fontSize: '10px',
                                            height: 20,
                                            minWidth: 60,
                                            backgroundColor: theme.palette.info?.main ?? theme.palette.primary.main,
                                            color: 'common.white',
                                            borderRadius: 1,
                                        }}
                                    />
                                    <MessageCell>
                                        <FileLink path={dump.line}>
                                            <Typography
                                                component="span"
                                                sx={{
                                                    fontFamily: primitives.fontFamilyMono,
                                                    fontSize: '12px',
                                                    color: 'primary.main',
                                                    textDecoration: 'none',
                                                    '&:hover': {textDecoration: 'underline'},
                                                }}
                                            >
                                                {dump.line}
                                            </Typography>
                                        </FileLink>
                                    </MessageCell>
                                    <IconButton size="small" sx={{flexShrink: 0}}>
                                        <Icon sx={{fontSize: 16}}>{expanded ? 'expand_less' : 'expand_more'}</Icon>
                                    </IconButton>
                                </EntryRow>
                                <Collapse in={expanded}>
                                    <DetailBox>
                                        <DumpBody>
                                            <VarDumpValue value={dump.variable} />
                                        </DumpBody>
                                    </DetailBox>
                                </Collapse>
                            </Box>
                        );
                    }
                }
            })}
        </Box>
    );
};
