import {useAnalyzeMutation, useGetStatusQuery} from '@app-dev-panel/panel/Module/Llm/API/Llm';
import {Markdown} from '@app-dev-panel/panel/Module/Llm/Component/Markdown';
import {SendButton} from '@app-dev-panel/panel/Module/Llm/Component/SendButton';
import {DebugEntry, useGetDebugQuery} from '@app-dev-panel/sdk/API/Debug/Debug';
import {HighlightedText, fuzzyMatch} from '@app-dev-panel/sdk/Component/Layout/EntrySelector';
import {primitives} from '@app-dev-panel/sdk/Component/Theme/tokens';
import {isDebugEntryAboutConsole, isDebugEntryAboutWeb} from '@app-dev-panel/sdk/Helper/debugEntry';
import {formatBytes} from '@app-dev-panel/sdk/Helper/formatBytes';
import {formatDate} from '@app-dev-panel/sdk/Helper/formatDate';
import {searchVariants} from '@app-dev-panel/sdk/Helper/layoutTranslit';
import {
    Alert,
    Box,
    Card,
    CardContent,
    CardHeader,
    Checkbox,
    Chip,
    Icon,
    Paper,
    TextField,
    Typography,
    type Theme,
} from '@mui/material';
import {styled, useTheme} from '@mui/material/styles';
import {useCallback, useMemo, useRef, useState} from 'react';

// ---------------------------------------------------------------------------
// Error extraction
// ---------------------------------------------------------------------------

const extractErrorMessage = (err: unknown): string | null => {
    if (typeof err === 'object' && err !== null && 'data' in err) {
        const data = (err as {data: unknown}).data;
        if (typeof data === 'object' && data !== null) {
            const obj = data as Record<string, unknown>;
            if (typeof obj.error === 'string') return obj.error;
            if (typeof obj.data === 'object' && obj.data !== null) {
                const inner = obj.data as Record<string, unknown>;
                if (typeof inner.error === 'string') return inner.error;
            }
        }
    }
    return null;
};

// ---------------------------------------------------------------------------
// Styled components — matching EntrySelector style
// ---------------------------------------------------------------------------

const EntryRow = styled(Box, {shouldForwardProp: (p) => p !== 'active' && p !== 'highlighted'})<{
    active?: boolean;
    highlighted?: boolean;
}>(({theme, active, highlighted}) => ({
    display: 'flex',
    alignItems: 'center',
    gap: theme.spacing(1),
    padding: theme.spacing(0.75, 1, 0.75, 0),
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

const StatusLabel = styled('span')({fontWeight: 500, fontSize: '12px', flexShrink: 0});

const TimeLabel = styled('span')(({theme}) => ({
    fontSize: '11px',
    color: theme.palette.text.disabled,
    flexShrink: 0,
    whiteSpace: 'nowrap',
}));

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

// ---------------------------------------------------------------------------
// Quick filters
// ---------------------------------------------------------------------------

type QuickFilterKey = '4xx' | '5xx' | '500ms' | '1s' | 'errors' | 'POST';

type QuickFilterDef = {
    key: QuickFilterKey;
    label: string;
    color: 'error' | 'warning' | 'primary' | 'info';
    test: (e: DebugEntry) => boolean;
};

const QUICK_FILTERS: QuickFilterDef[] = [
    {
        key: '4xx',
        label: '4xx',
        color: 'warning',
        test: (e) => (e.response?.statusCode ?? 0) >= 400 && (e.response?.statusCode ?? 0) < 500,
    },
    {key: '5xx', label: '5xx', color: 'error', test: (e) => (e.response?.statusCode ?? 0) >= 500},
    {key: 'errors', label: 'Errors', color: 'error', test: (e) => e.exception != null},
    {
        key: '500ms',
        label: '500ms+',
        color: 'info',
        test: (e) => (e.web?.request?.processingTime ?? e.console?.request?.processingTime ?? 0) >= 0.5,
    },
    {
        key: '1s',
        label: '1s+',
        color: 'warning',
        test: (e) => (e.web?.request?.processingTime ?? e.console?.request?.processingTime ?? 0) >= 1,
    },
    {key: 'POST', label: 'POST', color: 'primary', test: (e) => e.request?.method === 'POST'},
];

// ---------------------------------------------------------------------------
// Helpers
// ---------------------------------------------------------------------------

type MatchedEntry = {entry: DebugEntry; indices: number[]; searchText: string};

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
// Component
// ---------------------------------------------------------------------------

export const AnalyzePanel = () => {
    const theme = useTheme();
    const {data: status} = useGetStatusQuery();
    const {data: entries} = useGetDebugQuery();
    const [analyze, {isLoading}] = useAnalyzeMutation();
    const [selectedEntries, setSelectedEntries] = useState<Set<string>>(new Set());
    const [prompt, setPrompt] = useState('');
    const [result, setResult] = useState<string | null>(null);
    const [error, setError] = useState<string | null>(null);
    const [filter, setFilter] = useState('');
    const [activeQuickFilters, setActiveQuickFilters] = useState<Set<QuickFilterKey>>(new Set());
    const [highlightedIndex, setHighlightedIndex] = useState(-1);
    const listRef = useRef<HTMLDivElement>(null);
    const inputRef = useRef<HTMLInputElement>(null);

    const recentEntries = useMemo(() => (entries ?? []).slice(0, 50), [entries]);

    // Apply quick filters first, then fuzzy text filter
    const quickFiltered = useMemo(() => {
        if (activeQuickFilters.size === 0) return recentEntries;
        const activeDefs = QUICK_FILTERS.filter((f) => activeQuickFilters.has(f.key));
        return recentEntries.filter((entry) => activeDefs.some((f) => f.test(entry)));
    }, [recentEntries, activeQuickFilters]);

    const matched: MatchedEntry[] = useMemo(() => {
        if (!filter.trim()) {
            return quickFiltered.map((entry) => ({entry, indices: [], searchText: getSearchText(entry)}));
        }

        const variants = searchVariants(filter);
        const results: (MatchedEntry & {score: number})[] = [];
        for (const entry of quickFiltered) {
            const searchText = getSearchText(entry);
            let bestMatch: ReturnType<typeof fuzzyMatch> = null;
            for (const variant of variants) {
                const match = fuzzyMatch(searchText, variant);
                if (match && (bestMatch === null || match.score < bestMatch.score)) {
                    bestMatch = match;
                }
            }
            if (bestMatch) {
                results.push({entry, indices: bestMatch.indices, searchText, score: bestMatch.score});
            }
        }

        results.sort((a, b) => a.score - b.score);
        return results;
    }, [quickFiltered, filter]);

    const toggleQuickFilter = useCallback((key: QuickFilterKey) => {
        setActiveQuickFilters((prev) => {
            const next = new Set(prev);
            if (next.has(key)) {
                next.delete(key);
            } else {
                next.add(key);
            }
            return next;
        });
        setHighlightedIndex(-1);
    }, []);

    const toggleEntry = useCallback((id: string) => {
        setSelectedEntries((prev) => {
            const next = new Set(prev);
            if (next.has(id)) {
                next.delete(id);
            } else {
                next.add(id);
            }
            return next;
        });
    }, []);

    const toggleAll = useCallback(() => {
        const visibleIds = matched.map((m) => m.entry.id);
        setSelectedEntries((prev) => {
            const allSelected = visibleIds.every((id) => prev.has(id));
            if (allSelected) {
                const next = new Set(prev);
                visibleIds.forEach((id) => next.delete(id));
                return next;
            }
            return new Set([...prev, ...visibleIds]);
        });
    }, [matched]);

    const handleAnalyze = useCallback(async () => {
        if (selectedEntries.size === 0) return;
        setError(null);
        setResult(null);

        const selected = recentEntries.filter((e) => selectedEntries.has(e.id));
        const context = selected.length === 1 ? selected[0] : selected;

        try {
            const response = await analyze({
                context: context as unknown as Record<string, unknown>,
                prompt: prompt || undefined,
            }).unwrap();
            setResult(response.analysis);
        } catch (err: unknown) {
            const message = extractErrorMessage(err) ?? 'Failed to analyze debug entry.';
            setError(message);
        }
    }, [selectedEntries, recentEntries, analyze, prompt]);

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
                    toggleEntry(matched[highlightedIndex].entry.id);
                }
            }
        },
        [matched, highlightedIndex, toggleEntry],
    );

    // Scroll highlighted row into view
    const prevHighlighted = useRef(-1);
    if (highlightedIndex !== prevHighlighted.current) {
        prevHighlighted.current = highlightedIndex;
        if (highlightedIndex >= 0 && listRef.current) {
            const rows = listRef.current.querySelectorAll('[data-entry-row]');
            rows[highlightedIndex]?.scrollIntoView({block: 'nearest'});
        }
    }

    if (!status?.connected) {
        return <Alert severity="info">Connect an LLM provider first to analyze debug entries with AI.</Alert>;
    }

    const allVisibleSelected = matched.length > 0 && matched.every((m) => selectedEntries.has(m.entry.id));
    const someVisibleSelected = matched.some((m) => selectedEntries.has(m.entry.id));

    return (
        <Box sx={{display: 'flex', flexDirection: 'column', gap: 2, height: '100%'}}>
            {/* Entry list with filter and checkboxes */}
            <Paper variant="outlined" sx={{overflow: 'hidden', borderRadius: 1.5}}>
                <FilterRow>
                    <Checkbox
                        size="small"
                        checked={allVisibleSelected}
                        indeterminate={someVisibleSelected && !allVisibleSelected}
                        onChange={toggleAll}
                        sx={{ml: 0.5}}
                    />
                    <FilterInput
                        ref={inputRef}
                        placeholder="Search by URL, method, or command..."
                        value={filter}
                        onChange={(e) => {
                            setFilter(e.target.value);
                            setHighlightedIndex(-1);
                        }}
                        onKeyDown={handleKeyDown}
                    />
                    {selectedEntries.size > 0 && (
                        <Chip
                            label={`${selectedEntries.size} selected`}
                            size="small"
                            color="primary"
                            sx={{mr: 1, flexShrink: 0}}
                        />
                    )}
                    {(filter || activeQuickFilters.size > 0) && (
                        <Typography variant="caption" color="text.disabled" sx={{mr: 2, flexShrink: 0}}>
                            {matched.length} of {recentEntries.length}
                        </Typography>
                    )}
                </FilterRow>
                <Box
                    sx={{
                        display: 'flex',
                        gap: 0.5,
                        px: 1,
                        py: 0.5,
                        borderBottom: 1,
                        borderColor: 'divider',
                        flexWrap: 'wrap',
                    }}
                >
                    {QUICK_FILTERS.map((qf) => (
                        <Chip
                            key={qf.key}
                            label={qf.label}
                            size="small"
                            color={activeQuickFilters.has(qf.key) ? qf.color : 'default'}
                            variant={activeQuickFilters.has(qf.key) ? 'filled' : 'outlined'}
                            onClick={() => toggleQuickFilter(qf.key)}
                            sx={{height: 22, fontSize: '11px', cursor: 'pointer'}}
                        />
                    ))}
                </Box>
                <Box ref={listRef} sx={{overflowY: 'auto', maxHeight: 280}}>
                    {matched.length === 0 && (
                        <Box sx={{textAlign: 'center', py: 3, color: 'text.disabled'}}>
                            <Typography variant="body2">
                                {filter ? `No entries match "${filter}"` : 'No debug entries'}
                            </Typography>
                        </Box>
                    )}
                    {matched.map(({entry, indices}, idx) => {
                        const active = selectedEntries.has(entry.id);
                        const isHighlighted = idx === highlightedIndex;

                        if (isDebugEntryAboutWeb(entry)) {
                            const methodLen = entry.request.method.length;
                            const methodIndices = indices.filter((i) => i < methodLen);
                            const pathIndices = indices.filter((i) => i > methodLen).map((i) => i - methodLen - 1);

                            return (
                                <EntryRow
                                    key={entry.id}
                                    data-entry-row
                                    active={active}
                                    highlighted={isHighlighted}
                                    onClick={() => toggleEntry(entry.id)}
                                >
                                    <Checkbox size="small" checked={active} sx={{p: 0.25, ml: 0.5}} />
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
                                    onClick={() => toggleEntry(entry.id)}
                                >
                                    <Checkbox size="small" checked={active} sx={{p: 0.25, ml: 0.5}} />
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
                                onClick={() => toggleEntry(entry.id)}
                            >
                                <Checkbox size="small" checked={active} sx={{p: 0.25, ml: 0.5}} />
                                <PathLabel>
                                    <HighlightedText text={entry.id} indices={indices} />
                                </PathLabel>
                            </EntryRow>
                        );
                    })}
                </Box>
            </Paper>

            {/* Result area */}
            {result && (
                <Card variant="outlined" sx={{flex: 1, overflow: 'auto'}}>
                    <CardHeader title="Analysis Result" titleTypographyProps={{variant: 'subtitle1'}} />
                    <CardContent sx={{pt: 0}}>
                        <Markdown content={result} />
                    </CardContent>
                </Card>
            )}

            {error && (
                <Alert severity="error" onClose={() => setError(null)}>
                    {error}
                </Alert>
            )}

            {/* Input area with split send/timeout button */}
            <Box sx={{display: 'flex', gap: 1, mt: 'auto'}}>
                <TextField
                    fullWidth
                    size="small"
                    placeholder="Describe what to analyze (optional)..."
                    value={prompt}
                    onChange={(e) => setPrompt(e.target.value)}
                    onKeyDown={(e) => {
                        if (e.key === 'Enter' && !e.shiftKey) {
                            e.preventDefault();
                            handleAnalyze();
                        }
                    }}
                    multiline
                    maxRows={3}
                />
                <SendButton
                    label="Analyze"
                    onClick={handleAnalyze}
                    disabled={selectedEntries.size === 0}
                    loading={isLoading}
                />
            </Box>
        </Box>
    );
};
