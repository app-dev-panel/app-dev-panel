import {DebugEntry} from '@app-dev-panel/sdk/API/Debug/Debug';
import {
    EntryFilterConfig,
    type EntryFilterState,
    type FilterCondition,
    loadFilterState,
    saveFilterState,
} from '@app-dev-panel/sdk/Component/Layout/EntryFilterConfig';
import {primitives} from '@app-dev-panel/sdk/Component/Theme/tokens';
import {isDebugEntryAboutConsole, isDebugEntryAboutWeb} from '@app-dev-panel/sdk/Helper/debugEntry';
import {formatBytes} from '@app-dev-panel/sdk/Helper/formatBytes';
import {formatDate} from '@app-dev-panel/sdk/Helper/formatDate';
import {Box, Chip, Icon, Popover, type Theme, Typography} from '@mui/material';
import {styled, useTheme} from '@mui/material/styles';
import React, {useCallback, useEffect, useRef, useState} from 'react';

// ---------------------------------------------------------------------------
// Fuzzy search utilities
// ---------------------------------------------------------------------------

type FuzzyMatch = {score: number; indices: number[]};

/**
 * Fuzzy-match `query` against `text`.
 * Returns null if no match, or {score, indices} where indices are
 * the character positions in `text` that matched.
 * Score: lower is better. Consecutive matches and early matches score best.
 */
export function fuzzyMatch(text: string, query: string): FuzzyMatch | null {
    const textLower = text.toLowerCase();
    const queryLower = query.toLowerCase();

    if (queryLower.length === 0) return {score: 0, indices: []};

    const indices: number[] = [];
    let qi = 0;

    for (let ti = 0; ti < textLower.length && qi < queryLower.length; ti++) {
        if (textLower[ti] === queryLower[qi]) {
            indices.push(ti);
            qi++;
        }
    }

    if (qi < queryLower.length) return null; // not all query chars matched

    // Score: penalize gaps between matched characters and late starts
    let score = indices[0]; // penalize late start
    for (let i = 1; i < indices.length; i++) {
        const gap = indices[i] - indices[i - 1] - 1;
        score += gap * 2; // penalize gaps
    }

    // Bonus for exact substring match
    if (textLower.includes(queryLower)) {
        score -= queryLower.length * 3;
    }

    return {score, indices};
}

/**
 * Renders text with fuzzy-matched characters highlighted.
 */
export const HighlightedText = ({text, indices}: {text: string; indices: number[]}) => {
    if (indices.length === 0) return <>{text}</>;

    const indexSet = new Set(indices);
    const parts: React.ReactNode[] = [];
    let current = '';
    let isHighlighted = false;

    for (let i = 0; i < text.length; i++) {
        const charHighlighted = indexSet.has(i);
        if (charHighlighted !== isHighlighted) {
            if (current) {
                parts.push(
                    isHighlighted ? (
                        <mark
                            key={`${i}-m`}
                            style={{
                                backgroundColor: 'transparent',
                                color: 'inherit',
                                fontWeight: 600,
                                textDecoration: 'underline',
                            }}
                        >
                            {current}
                        </mark>
                    ) : (
                        <span key={`${i}-s`}>{current}</span>
                    ),
                );
            }
            current = '';
            isHighlighted = charHighlighted;
        }
        current += text[i];
    }
    if (current) {
        parts.push(
            isHighlighted ? (
                <mark
                    key="end-m"
                    style={{
                        backgroundColor: 'transparent',
                        color: 'inherit',
                        fontWeight: 600,
                        textDecoration: 'underline',
                    }}
                >
                    {current}
                </mark>
            ) : (
                <span key="end-s">{current}</span>
            ),
        );
    }
    return <>{parts}</>;
};

// ---------------------------------------------------------------------------
// Types
// ---------------------------------------------------------------------------

type EntrySelectorProps = {
    anchorEl: HTMLElement | null;
    open: boolean;
    onClose: () => void;
    entries: DebugEntry[];
    currentEntryId?: string;
    onSelect: (entry: DebugEntry) => void;
    onAllClick?: () => void;
};

type MatchedEntry = {entry: DebugEntry; indices: number[]; searchText: string};

// ---------------------------------------------------------------------------
// Styled components
// ---------------------------------------------------------------------------

const EntryRow = styled(Box, {shouldForwardProp: (p) => p !== 'active' && p !== 'highlighted'})<{
    active?: boolean;
    highlighted?: boolean;
}>(({theme, active, highlighted}) => ({
    display: 'flex',
    alignItems: 'center',
    gap: theme.spacing(1.25),
    padding: theme.spacing(1, 2),
    cursor: 'pointer',
    fontFamily: primitives.fontFamilyMono,
    fontSize: '13px',
    backgroundColor: highlighted ? theme.palette.action.selected : active ? theme.palette.primary.light : 'transparent',
    '&:hover': {backgroundColor: theme.palette.action.hover},
}));

const MethodLabel = styled('span')({fontWeight: 600, fontSize: '11px', minWidth: 40});

const PathLabel = styled('span')({
    flex: 1,
    fontSize: '13px',
    overflow: 'hidden',
    textOverflow: 'ellipsis',
    whiteSpace: 'nowrap',
});

const StatusLabel = styled('span')({fontWeight: 500, fontSize: '12px'});

const TimeLabel = styled('span')(({theme}) => ({fontSize: '11px', color: theme.palette.text.disabled}));

const statusColor = (status: number, theme: Theme): string => {
    if (status >= 500) return theme.palette.error.main;
    if (status >= 400) return theme.palette.warning.main;
    return theme.palette.success.main;
};

const methodColor = (method: string, theme: Theme): string => {
    switch (method.toUpperCase()) {
        case 'GET':
            return theme.palette.success.main;
        case 'POST':
            return theme.palette.primary.main;
        case 'PUT':
        case 'PATCH':
            return theme.palette.warning.main;
        case 'DELETE':
            return theme.palette.error.main;
        default:
            return theme.palette.text.secondary;
    }
};

const FilterRow = styled(Box)(({theme}) => ({
    display: 'flex',
    alignItems: 'center',
    borderBottom: `1px solid ${theme.palette.divider}`,
}));

const FilterInput = styled('input')(({theme}) => ({
    flex: 1,
    border: 'none',
    outline: 'none',
    fontSize: '13px',
    fontFamily: theme.typography.fontFamily,
    backgroundColor: 'transparent',
    color: theme.palette.text.primary,
    padding: theme.spacing(1.25, 2),
    '&::placeholder': {color: theme.palette.text.disabled},
}));

const ToolbarButton = styled('button', {shouldForwardProp: (p) => p !== 'active'})<{active?: boolean}>(
    ({theme, active}) => ({
        border: 'none',
        background: active ? theme.palette.primary.main : 'none',
        cursor: 'pointer',
        fontSize: '12px',
        fontWeight: 600,
        color: active ? theme.palette.common.white : theme.palette.text.secondary,
        padding: theme.spacing(0.75, 1.5),
        borderRadius: theme.shape.borderRadius / 2,
        whiteSpace: 'nowrap',
        display: 'flex',
        alignItems: 'center',
        gap: theme.spacing(0.5),
        '&:hover': {
            backgroundColor: active ? theme.palette.primary.dark : theme.palette.action.hover,
            color: active ? theme.palette.common.white : theme.palette.text.primary,
        },
    }),
);

const GearButton = styled('button')(({theme}) => ({
    border: 'none',
    background: 'none',
    cursor: 'pointer',
    padding: theme.spacing(0.5),
    borderRadius: theme.shape.borderRadius / 2,
    display: 'flex',
    alignItems: 'center',
    color: theme.palette.text.disabled,
    '&:hover': {backgroundColor: theme.palette.action.hover, color: theme.palette.text.primary},
}));

const ListButton = styled('button')(({theme}) => ({
    border: 'none',
    background: 'none',
    cursor: 'pointer',
    fontSize: '12px',
    fontWeight: 600,
    color: theme.palette.text.secondary,
    padding: theme.spacing(0.75, 1.5),
    marginRight: theme.spacing(1),
    borderRadius: theme.shape.borderRadius / 2,
    whiteSpace: 'nowrap',
    '&:hover': {backgroundColor: theme.palette.action.hover, color: theme.palette.text.primary},
}));

const CountLabel = styled(Typography)(({theme}) => ({
    fontSize: '11px',
    color: theme.palette.text.disabled,
    padding: theme.spacing(0.5, 2),
    borderTop: `1px solid ${theme.palette.divider}`,
    textAlign: 'right',
}));

// ---------------------------------------------------------------------------
// Helper: get searchable text for an entry
// ---------------------------------------------------------------------------

function getSearchText(entry: DebugEntry): string {
    if (isDebugEntryAboutWeb(entry)) {
        return `${entry.request.method} ${entry.request.path}`;
    }
    if (isDebugEntryAboutConsole(entry)) {
        return entry.command?.input ?? entry.command?.name ?? '';
    }
    return entry.id;
}

// ---------------------------------------------------------------------------
// Filter matching
// ---------------------------------------------------------------------------

function getEntryField(entry: DebugEntry, field: FilterCondition['field']): string {
    switch (field) {
        case 'url':
            if (isDebugEntryAboutWeb(entry)) return entry.request.path;
            if (isDebugEntryAboutConsole(entry)) return entry.command?.input ?? '';
            return '';
        case 'status':
            if (isDebugEntryAboutWeb(entry)) return String(entry.response.statusCode);
            if (isDebugEntryAboutConsole(entry)) return String(entry.command?.exitCode ?? '');
            return '';
        case 'type':
            if (isDebugEntryAboutWeb(entry)) return 'http';
            if (isDebugEntryAboutConsole(entry)) return 'cli';
            return 'unknown';
    }
}

function matchCondition(entry: DebugEntry, condition: FilterCondition): boolean {
    if (!condition.value.trim()) return true;
    const fieldValue = getEntryField(entry, condition.field);
    const val = condition.value.toLowerCase();
    const fv = fieldValue.toLowerCase();

    switch (condition.operator) {
        case 'contains':
            return fv.includes(val);
        case 'starts_with':
            return fv.startsWith(val);
        case 'ends_with':
            return fv.endsWith(val);
        case 'greater_than':
            return Number(fieldValue) > Number(condition.value);
        case 'equals':
            return fv === val;
    }
}

function applyFilter(entries: DebugEntry[], filterState: EntryFilterState): DebugEntry[] {
    if (!filterState.enabled || filterState.conditions.length === 0) return entries;
    return entries.filter((entry) => filterState.conditions.every((c) => matchCondition(entry, c)));
}

// ---------------------------------------------------------------------------
// Component
// ---------------------------------------------------------------------------

export const EntrySelector = ({
    anchorEl,
    open,
    onClose,
    entries,
    currentEntryId,
    onSelect,
    onAllClick,
}: EntrySelectorProps) => {
    const theme = useTheme();
    const [filter, setFilter] = useState('');
    const inputRef = useRef<HTMLInputElement>(null);
    const listRef = useRef<HTMLDivElement>(null);
    const [filterState, setFilterState] = useState<EntryFilterState>(loadFilterState);
    const [filterConfigAnchor, setFilterConfigAnchor] = useState<HTMLElement | null>(null);
    const [highlightedIndex, setHighlightedIndex] = useState(-1);

    // Auto-focus input when popover opens
    useEffect(() => {
        if (open) {
            setFilter('');
            setHighlightedIndex(-1);
            // Small delay to let Popover render
            const timer = setTimeout(() => inputRef.current?.focus(), 50);
            return () => clearTimeout(timer);
        }
    }, [open]);

    const toggleFilter = useCallback(() => {
        setFilterState((prev) => {
            const next = {...prev, enabled: !prev.enabled};
            saveFilterState(next);
            return next;
        });
    }, []);

    const handleFilterConfigOpen = useCallback((e: React.MouseEvent<HTMLElement>) => {
        e.stopPropagation();
        setFilterConfigAnchor(e.currentTarget);
    }, []);

    // Apply custom filter first, then fuzzy search
    const filteredEntries = React.useMemo(() => applyFilter(entries, filterState), [entries, filterState]);

    // Fuzzy-filter and sort entries
    const matched: MatchedEntry[] = React.useMemo(() => {
        if (!filter.trim()) {
            return filteredEntries.map((entry) => ({entry, indices: [], searchText: getSearchText(entry)}));
        }

        const results: (MatchedEntry & {score: number})[] = [];
        for (const entry of filteredEntries) {
            const searchText = getSearchText(entry);
            const match = fuzzyMatch(searchText, filter);
            if (match) {
                results.push({entry, indices: match.indices, searchText, score: match.score});
            }
        }

        results.sort((a, b) => a.score - b.score);
        return results;
    }, [filteredEntries, filter]);

    const handleClose = useCallback(() => {
        onClose();
        setFilter('');
    }, [onClose]);

    const handleSelect = useCallback(
        (entry: DebugEntry) => {
            onSelect(entry);
            handleClose();
        },
        [onSelect, handleClose],
    );

    // Reset highlight when matched list changes
    useEffect(() => {
        setHighlightedIndex(-1);
    }, [matched.length]);

    // Scroll highlighted row into view
    useEffect(() => {
        if (highlightedIndex < 0 || !listRef.current) return;
        const rows = listRef.current.querySelectorAll('[data-entry-row]');
        rows[highlightedIndex]?.scrollIntoView({block: 'nearest'});
    }, [highlightedIndex]);

    const handleKeyDown = useCallback(
        (e: React.KeyboardEvent) => {
            if (e.key === 'ArrowDown') {
                e.preventDefault();
                setHighlightedIndex((prev) => (prev < matched.length - 1 ? prev + 1 : prev));
            } else if (e.key === 'ArrowUp') {
                e.preventDefault();
                setHighlightedIndex((prev) => (prev > 0 ? prev - 1 : 0));
            } else if (e.key === 'Enter') {
                e.preventDefault();
                if (highlightedIndex >= 0 && highlightedIndex < matched.length) {
                    handleSelect(matched[highlightedIndex].entry);
                }
            } else if (e.key === 'Escape') {
                handleClose();
            }
        },
        [matched, highlightedIndex, handleSelect, handleClose],
    );

    const hasConditions = filterState.conditions.length > 0;

    return (
        <Popover
            open={open}
            anchorEl={anchorEl}
            onClose={handleClose}
            anchorOrigin={{vertical: 'bottom', horizontal: 'left'}}
            transformOrigin={{vertical: 'top', horizontal: 'left'}}
            slotProps={{
                paper: {
                    sx: {
                        width: 520,
                        maxHeight: 440,
                        mt: 0.5,
                        borderRadius: 1.5,
                        border: 1,
                        borderColor: 'divider',
                        boxShadow: '0 8px 32px rgba(0,0,0,0.12), 0 2px 8px rgba(0,0,0,0.08)',
                        backdropFilter: 'blur(12px)',
                        backgroundColor: (t) =>
                            t.palette.mode === 'dark' ? 'rgba(30, 41, 59, 0.92)' : 'rgba(255, 255, 255, 0.92)',
                    },
                },
            }}
        >
            <FilterRow>
                <FilterInput
                    ref={inputRef}
                    placeholder="Search by URL, method, or command..."
                    value={filter}
                    onChange={(e) => setFilter(e.target.value)}
                    onKeyDown={handleKeyDown}
                />
                <Box sx={{display: 'flex', alignItems: 'center', gap: 0.5, mr: 1}}>
                    {hasConditions && (
                        <ToolbarButton active={filterState.enabled} onClick={toggleFilter}>
                            Filter
                            {filterState.enabled && (
                                <span style={{fontSize: '10px', opacity: 0.8}}>({filterState.conditions.length})</span>
                            )}
                        </ToolbarButton>
                    )}
                    <GearButton onClick={handleFilterConfigOpen} title="Configure filter">
                        <Icon sx={{fontSize: 16}}>tune</Icon>
                    </GearButton>
                    {onAllClick && (
                        <ListButton
                            onClick={() => {
                                onAllClick();
                                handleClose();
                            }}
                        >
                            List
                        </ListButton>
                    )}
                </Box>
            </FilterRow>
            <EntryFilterConfig
                anchorEl={filterConfigAnchor}
                open={Boolean(filterConfigAnchor)}
                onClose={() => setFilterConfigAnchor(null)}
                filterState={filterState}
                onChange={setFilterState}
            />
            <Box ref={listRef} sx={{overflowY: 'auto', maxHeight: 360}}>
                {matched.length === 0 && (
                    <Box sx={{textAlign: 'center', py: 3, color: 'text.disabled'}}>
                        <Typography variant="body2">No entries match "{filter}"</Typography>
                    </Box>
                )}
                {matched.map(({entry, indices, searchText}, idx) => {
                    const active = entry.id === currentEntryId;
                    const isHighlighted = idx === highlightedIndex;
                    if (isDebugEntryAboutWeb(entry)) {
                        // Split indices: method part vs path part
                        const methodLen = entry.request.method.length;
                        const methodIndices = indices.filter((i) => i < methodLen);
                        const pathIndices = indices.filter((i) => i > methodLen).map((i) => i - methodLen - 1);

                        return (
                            <EntryRow
                                key={entry.id}
                                data-entry-row
                                active={active}
                                highlighted={isHighlighted}
                                onClick={() => handleSelect(entry)}
                            >
                                <MethodLabel sx={{color: methodColor(entry.request.method, theme)}}>
                                    <HighlightedText text={entry.request.method} indices={methodIndices} />
                                </MethodLabel>
                                <PathLabel>
                                    <HighlightedText text={entry.request.path} indices={pathIndices} />
                                </PathLabel>
                                {entry.web?.request?.processingTime != null && (
                                    <Typography
                                        component="span"
                                        sx={{
                                            fontFamily: primitives.fontFamilyMono,
                                            fontSize: '10px',
                                            color: 'primary.main',
                                            flexShrink: 0,
                                        }}
                                    >
                                        {(entry.web.request.processingTime * 1000).toFixed(0)}ms
                                    </Typography>
                                )}
                                {entry.web?.memory?.peakUsage != null && (
                                    <Typography
                                        component="span"
                                        sx={{
                                            fontFamily: primitives.fontFamilyMono,
                                            fontSize: '10px',
                                            color: 'success.main',
                                            flexShrink: 0,
                                        }}
                                    >
                                        {formatBytes(entry.web.memory.peakUsage)}
                                    </Typography>
                                )}
                                <StatusLabel sx={{color: statusColor(entry.response.statusCode, theme)}}>
                                    {entry.response.statusCode}
                                </StatusLabel>
                                <TimeLabel>{formatDate(entry.web.request.startTime)}</TimeLabel>
                            </EntryRow>
                        );
                    }
                    if (isDebugEntryAboutConsole(entry)) {
                        const commandText = entry.command?.input ?? 'Unknown';
                        return (
                            <EntryRow
                                key={entry.id}
                                data-entry-row
                                active={active}
                                highlighted={isHighlighted}
                                onClick={() => handleSelect(entry)}
                            >
                                <Icon sx={{fontSize: 14, color: 'text.disabled'}}>terminal</Icon>
                                <PathLabel>
                                    <HighlightedText text={commandText} indices={indices} />
                                </PathLabel>
                                {entry.console?.request?.processingTime != null && (
                                    <Typography
                                        component="span"
                                        sx={{
                                            fontFamily: primitives.fontFamilyMono,
                                            fontSize: '10px',
                                            color: 'primary.main',
                                            flexShrink: 0,
                                        }}
                                    >
                                        {(entry.console.request.processingTime * 1000).toFixed(0)}ms
                                    </Typography>
                                )}
                                {entry.console?.memory?.peakUsage != null && (
                                    <Typography
                                        component="span"
                                        sx={{
                                            fontFamily: primitives.fontFamilyMono,
                                            fontSize: '10px',
                                            color: 'success.main',
                                            flexShrink: 0,
                                        }}
                                    >
                                        {formatBytes(entry.console.memory.peakUsage)}
                                    </Typography>
                                )}
                                <Chip
                                    label={entry.command?.exitCode === 0 ? 'OK' : `EXIT ${entry.command?.exitCode}`}
                                    size="small"
                                    color={entry.command?.exitCode === 0 ? 'success' : 'error'}
                                    sx={{height: 18, fontSize: '10px'}}
                                />
                                <TimeLabel>{formatDate(entry.console.request.startTime)}</TimeLabel>
                            </EntryRow>
                        );
                    }
                    return (
                        <EntryRow
                            key={entry.id}
                            data-entry-row
                            active={active}
                            highlighted={isHighlighted}
                            onClick={() => handleSelect(entry)}
                        >
                            <PathLabel>
                                <HighlightedText text={entry.id} indices={indices} />
                            </PathLabel>
                        </EntryRow>
                    );
                })}
            </Box>
            {(filter || filterState.enabled) && (
                <CountLabel>
                    {matched.length} of {entries.length} entries
                    {filterState.enabled && ` (filter active)`}
                </CountLabel>
            )}
        </Popover>
    );
};
