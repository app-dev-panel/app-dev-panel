import {useAnalyzeMutation, useGetStatusQuery} from '@app-dev-panel/panel/Module/Llm/API/Llm';
import {DebugEntry, useGetDebugQuery} from '@app-dev-panel/sdk/API/Debug/Debug';
import {
    Alert,
    Box,
    Button,
    Card,
    CardContent,
    CardHeader,
    Chip,
    CircularProgress,
    FormControl,
    InputLabel,
    MenuItem,
    Select,
    TextField,
    Typography,
} from '@mui/material';
import {useCallback, useMemo, useState} from 'react';

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

const formatTime = (ms: number): string => {
    if (ms < 1) return `${(ms * 1000).toFixed(0)} us`;
    if (ms < 1000) return `${ms.toFixed(0)} ms`;
    return `${(ms / 1000).toFixed(2)} s`;
};

const formatMemory = (bytes: number): string => {
    if (bytes < 1024) return `${bytes} B`;
    if (bytes < 1024 * 1024) return `${(bytes / 1024).toFixed(1)} KB`;
    return `${(bytes / (1024 * 1024)).toFixed(1)} MB`;
};

const getEntryLabel = (entry: DebugEntry): string => {
    if (entry.request) {
        return `${entry.request.method} ${entry.request.path || entry.request.url}`;
    }
    if (entry.command) {
        return entry.command.name || entry.command.input || entry.command.class;
    }
    return entry.id.substring(0, 12);
};

const EntrySummary = ({entry}: {entry: DebugEntry}) => {
    const web = entry.web ?? entry.console;
    const hasException = !!entry.exception;
    const statusCode = entry.response?.statusCode;

    return (
        <Box
            sx={{
                display: 'flex',
                flexWrap: 'wrap',
                gap: 1,
                alignItems: 'center',
                px: 1,
                py: 0.75,
                borderRadius: 1,
                bgcolor: 'background.default',
            }}
        >
            {entry.request && (
                <>
                    <Chip label={entry.request.method} size="small" variant="outlined" sx={{fontWeight: 600}} />
                    <Typography variant="body2" noWrap sx={{minWidth: 0, flex: '0 1 auto'}}>
                        {entry.request.path || entry.request.url}
                    </Typography>
                </>
            )}
            {entry.command && (
                <Chip label={entry.command.name || 'command'} size="small" variant="outlined" sx={{fontWeight: 600}} />
            )}
            {statusCode != null && (
                <Chip
                    label={statusCode}
                    size="small"
                    color={statusCode < 400 ? 'success' : statusCode < 500 ? 'warning' : 'error'}
                />
            )}
            {web && (
                <Typography variant="caption" color="text.secondary">
                    {formatTime(web.request.processingTime)}
                </Typography>
            )}
            {web && (
                <Typography variant="caption" color="text.secondary">
                    {formatMemory(web.memory.peakUsage)}
                </Typography>
            )}
            {entry.db && entry.db.queries.total > 0 && (
                <Chip
                    label={`${entry.db.queries.total} queries`}
                    size="small"
                    variant="outlined"
                    color={entry.db.queries.error > 0 ? 'error' : 'default'}
                />
            )}
            {entry.logger && entry.logger.total > 0 && (
                <Chip label={`${entry.logger.total} logs`} size="small" variant="outlined" />
            )}
            {hasException && (
                <Chip
                    label={`${entry.exception!.class.split('\\').pop()}: ${entry.exception!.message}`}
                    size="small"
                    color="error"
                    sx={{maxWidth: 300}}
                />
            )}
        </Box>
    );
};

export const AnalyzePanel = () => {
    const {data: status} = useGetStatusQuery();
    const {data: entries} = useGetDebugQuery();
    const [analyze, {isLoading}] = useAnalyzeMutation();
    const [selectedEntry, setSelectedEntry] = useState('');
    const [prompt, setPrompt] = useState('');
    const [result, setResult] = useState<string | null>(null);
    const [error, setError] = useState<string | null>(null);

    const recentEntries = useMemo(() => (entries ?? []).slice(0, 30), [entries]);
    const currentEntry = useMemo(
        () => recentEntries.find((e) => e.id === selectedEntry),
        [recentEntries, selectedEntry],
    );

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

    if (!status?.connected) {
        return <Alert severity="info">Connect an LLM provider first to analyze debug entries with AI.</Alert>;
    }

    return (
        <Box sx={{display: 'flex', flexDirection: 'column', gap: 2, height: '100%'}}>
            {/* Entry selector — full width */}
            <FormControl size="small" fullWidth>
                <InputLabel>Debug Entry</InputLabel>
                <Select value={selectedEntry} label="Debug Entry" onChange={(e) => setSelectedEntry(e.target.value)}>
                    {recentEntries.map((entry) => (
                        <MenuItem key={entry.id} value={entry.id}>
                            <Box sx={{display: 'flex', gap: 1, alignItems: 'center', width: '100%'}}>
                                {entry.request && (
                                    <Typography component="span" variant="body2" sx={{fontWeight: 600, flexShrink: 0}}>
                                        {entry.request.method}
                                    </Typography>
                                )}
                                <Typography component="span" variant="body2" noWrap>
                                    {getEntryLabel(entry)}
                                </Typography>
                                {entry.response?.statusCode != null && (
                                    <Chip
                                        label={entry.response.statusCode}
                                        size="small"
                                        color={
                                            entry.response.statusCode < 400
                                                ? 'success'
                                                : entry.response.statusCode < 500
                                                  ? 'warning'
                                                  : 'error'
                                        }
                                        sx={{ml: 'auto', height: 20, '& .MuiChip-label': {px: 0.75, fontSize: 11}}}
                                    />
                                )}
                                {entry.exception && (
                                    <Chip
                                        label="error"
                                        size="small"
                                        color="error"
                                        sx={{height: 20, '& .MuiChip-label': {px: 0.75, fontSize: 11}}}
                                    />
                                )}
                            </Box>
                        </MenuItem>
                    ))}
                </Select>
            </FormControl>

            {/* Entry summary — shown when an entry is selected */}
            {currentEntry && <EntrySummary entry={currentEntry} />}

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
