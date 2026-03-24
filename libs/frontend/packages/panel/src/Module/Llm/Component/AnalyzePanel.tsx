import {useAnalyzeMutation, useGetStatusQuery} from '@app-dev-panel/panel/Module/Llm/API/Llm';
import {useGetDebugQuery} from '@app-dev-panel/sdk/API/Debug/Debug';
import {
    Alert,
    Box,
    Button,
    Card,
    CardContent,
    CardHeader,
    CircularProgress,
    FormControl,
    InputLabel,
    MenuItem,
    Select,
    TextField,
    Typography,
} from '@mui/material';
import {useCallback, useState} from 'react';

export const AnalyzePanel = () => {
    const {data: status} = useGetStatusQuery();
    const {data: entries} = useGetDebugQuery();
    const [analyze, {isLoading}] = useAnalyzeMutation();
    const [selectedEntry, setSelectedEntry] = useState('');
    const [prompt, setPrompt] = useState('');
    const [result, setResult] = useState<string | null>(null);
    const [error, setError] = useState<string | null>(null);

    const handleAnalyze = useCallback(async () => {
        if (!selectedEntry) return;
        setError(null);
        setResult(null);

        const entry = (entries ?? []).find((e) => e.id === selectedEntry);
        if (!entry) return;

        try {
            const response = await analyze({
                context: entry as unknown as Record<string, unknown>,
                prompt: prompt || undefined,
            }).unwrap();
            setResult(response.analysis);
        } catch {
            setError('Failed to analyze debug entry.');
        }
    }, [selectedEntry, entries, analyze, prompt]);

    if (!status?.connected) {
        return <Alert severity="info">Connect your OpenRouter account first to analyze debug entries with AI.</Alert>;
    }

    return (
        <Box sx={{display: 'flex', flexDirection: 'column', gap: 2}}>
            <Box sx={{display: 'flex', gap: 2, alignItems: 'flex-start'}}>
                <FormControl size="small" sx={{minWidth: 300}}>
                    <InputLabel>Debug Entry</InputLabel>
                    <Select
                        value={selectedEntry}
                        label="Debug Entry"
                        onChange={(e) => setSelectedEntry(e.target.value)}
                    >
                        {(entries ?? []).slice(0, 20).map((entry) => (
                            <MenuItem key={entry.id} value={entry.id}>
                                {entry.id.substring(0, 8)}... —{' '}
                                {((entry as Record<string, unknown>).url as string) ?? 'N/A'}
                            </MenuItem>
                        ))}
                    </Select>
                </FormControl>
                <TextField
                    size="small"
                    placeholder="Custom prompt (optional)"
                    value={prompt}
                    onChange={(e) => setPrompt(e.target.value)}
                    sx={{flex: 1}}
                />
                <Button
                    variant="contained"
                    onClick={handleAnalyze}
                    disabled={isLoading || !selectedEntry}
                    startIcon={isLoading ? <CircularProgress size={16} /> : undefined}
                >
                    Analyze
                </Button>
            </Box>
            {error && (
                <Alert severity="error" onClose={() => setError(null)}>
                    {error}
                </Alert>
            )}
            {result && (
                <Card variant="outlined">
                    <CardHeader title="Analysis Result" titleTypographyProps={{variant: 'subtitle1'}} />
                    <CardContent>
                        <Typography variant="body2" sx={{whiteSpace: 'pre-wrap'}}>
                            {result}
                        </Typography>
                    </CardContent>
                </Card>
            )}
        </Box>
    );
};
