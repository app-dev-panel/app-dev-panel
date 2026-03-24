import {useAnalyzeMutation, useGetStatusQuery} from '@app-dev-panel/panel/Module/Llm/API/Llm';
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
    Button,
    Card,
    CardContent,
    CardHeader,
    Chip,
    CircularProgress,
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
    const [selectedEntry, setSelectedEntry] = useState<string | null>(null);
    const [prompt, setPrompt] = useState('');
    const [result, setResult] = useState<string | null>(null);
    const [error, setError] = useState<string | null>(null);
    const [filter, setFilter] = useState('');
    const [highlightedIndex, setHighlightedIndex] = useState(-1);
    const listRef = useRef<HTMLDivElement>(null);
    const inputRef = useRef<HTMLInputElement>(null);

    const recentEntries = useMemo(() => (entries ?? []).slice(0, 50), [entries]);
    const currentEntry = useMemo(
        () => recentEntries.find((e) => e.id === selectedEntry),
        [recentEntries, selectedEntry],
    );

    // Fuzzy-filter entries (with layout-aware search)
    const matched: MatchedEntry[] = useMemo(() => {
        if (!filter.trim()) {
            return recentEntries.map((entry) => ({entry, indices: [], searchText: getSearchText(entry)}));
        }

        const variants = searchVariants(filter);
        const results: (MatchedEntry & {score: number})[] = [];
        for (const entry of recentEntries) {
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
    }, [recentEntries, filter]);

    const handleAnalyze = useCallback(async () => {
        if (!currentEntry) return;
        setError(null);
        setResult(null);

        try {
            const response = await analyze({
                context: currentEntry as unknown as Record<string, unknown>,
                prompt: prompt || undefined,
            }).unwrap();
            setResult(response.analysis);
        } catch (err: unknown) {
            const message = extractErrorMessage(err) ?? 'Failed to analyze debug entry.';
            setError(message);
        }
    }, [currentEntry, analyze, prompt]);

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
                    setSelectedEntry(matched[highlightedIndex].entry.id);
                }
            }
        },
        [matched, highlightedIndex],
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

    return (
        <Box sx={{display: 'flex', flexDirection: 'column', gap: 2, height: '100%'}}>
            {/* Entry list with filter — matching navbar style */}
            <Paper variant="outlined" sx={{overflow: 'hidden', borderRadius: 1.5}}>
                <FilterRow>
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
                    {filter && (
                        <Typography variant="caption" color="text.disabled" sx={{mr: 2, flexShrink: 0}}>
                            {matched.length} of {recentEntries.length}
                        </Typography>
                    )}
                </FilterRow>
                <Box ref={listRef} sx={{overflowY: 'auto', maxHeight: 280}}>
                    {matched.length === 0 && (
                        <Box sx={{textAlign: 'center', py: 3, color: 'text.disabled'}}>
                            <Typography variant="body2">
                                {filter ? `No entries match "${filter}"` : 'No debug entries'}
                            </Typography>
                        </Box>
                    )}
                    {matched.map(({entry, indices}, idx) => {
                        const active = entry.id === selectedEntry;
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
                                    onClick={() => setSelectedEntry(entry.id)}
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
                                    onClick={() => setSelectedEntry(entry.id)}
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
                                onClick={() => setSelectedEntry(entry.id)}
                            >
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
                        <Typography variant="body2" sx={{whiteSpace: 'pre-wrap'}}>
                            {result}
                        </Typography>
                    </CardContent>
                </Card>
            )}

            {error && (
                <Alert severity="error" onClose={() => setError(null)}>
                    {error}
                </Alert>
            )}

            {/* Input area — chat-like, at the bottom */}
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
                <Button
                    variant="contained"
                    onClick={handleAnalyze}
                    disabled={isLoading || !selectedEntry}
                    startIcon={isLoading ? <CircularProgress size={16} color="inherit" /> : undefined}
                >
                    Analyze
                </Button>
            </Box>
        </Box>
    );
};
