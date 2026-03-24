import {
    LlmModel,
    LlmProvider,
    useConnectMutation,
    useDisconnectMutation,
    useGetModelsQuery,
    useGetStatusQuery,
    useOauthInitiateMutation,
    useSetModelMutation,
} from '@app-dev-panel/panel/Module/Llm/API/Llm';
import {
    Alert,
    Box,
    Button,
    Card,
    CardActions,
    CardContent,
    CardHeader,
    Chip,
    FormControl,
    InputLabel,
    MenuItem,
    Select,
    Skeleton,
    TextField,
    ToggleButton,
    ToggleButtonGroup,
    Typography,
} from '@mui/material';
import {useCallback, useState} from 'react';

const providerLabels: Record<LlmProvider, string> = {openrouter: 'OpenRouter', anthropic: 'Anthropic (Claude)'};

export const ConnectionCard = () => {
    const {data: status, isLoading} = useGetStatusQuery();
    const [initiate] = useOauthInitiateMutation();
    const [connect] = useConnectMutation();
    const [disconnect] = useDisconnectMutation();
    const [setModel] = useSetModelMutation();
    const {data: models, isLoading: modelsLoading} = useGetModelsQuery(undefined, {skip: !status?.connected});
    const [error, setError] = useState<string | null>(null);
    const [selectedProvider, setSelectedProvider] = useState<LlmProvider>('anthropic');
    const [apiKey, setApiKey] = useState('');

    const handleOpenRouterConnect = useCallback(async () => {
        setError(null);
        try {
            const callbackUrl = window.location.origin + window.location.pathname.replace(/\/[^/]*$/, '/llm/callback');
            const result = await initiate({callbackUrl}).unwrap();
            localStorage.setItem('llm_code_verifier', result.codeVerifier);
            window.open(result.authUrl, '_blank', 'width=600,height=700');
        } catch {
            setError('Failed to initiate OAuth flow.');
        }
    }, [initiate]);

    const handleApiKeyConnect = useCallback(async () => {
        setError(null);
        if (!apiKey.trim()) {
            setError('API key is required.');
            return;
        }
        try {
            await connect({provider: selectedProvider, apiKey: apiKey.trim()}).unwrap();
            setApiKey('');
        } catch {
            setError('Failed to connect with API key.');
        }
    }, [connect, selectedProvider, apiKey]);

    const handleDisconnect = useCallback(async () => {
        await disconnect();
    }, [disconnect]);

    const handleModelChange = useCallback(
        async (modelId: string) => {
            await setModel({model: modelId});
        },
        [setModel],
    );

    if (isLoading) {
        return <Skeleton variant="rectangular" height={200} sx={{borderRadius: 2}} />;
    }

    const isAnthropic = status?.provider === 'anthropic';

    const popularModels = (models ?? []).filter((m: LlmModel) =>
        isAnthropic
            ? m.id.startsWith('claude-')
            : m.id.startsWith('anthropic/') ||
              m.id.startsWith('openai/') ||
              m.id.startsWith('google/') ||
              m.id.startsWith('meta-llama/') ||
              m.id.startsWith('mistralai/'),
    );

    return (
        <Card variant="outlined">
            <CardHeader
                title="LLM Connection"
                subheader="Connect an LLM provider to use AI-powered debug analysis"
                action={
                    <Chip
                        label={status?.connected ? 'Connected' : 'Disconnected'}
                        color={status?.connected ? 'success' : 'default'}
                        size="small"
                    />
                }
            />
            <CardContent>
                {error && (
                    <Alert severity="error" sx={{mb: 2}} onClose={() => setError(null)}>
                        {error}
                    </Alert>
                )}
                {status?.connected ? (
                    <Box sx={{display: 'flex', flexDirection: 'column', gap: 2}}>
                        <Typography variant="body2" color="text.secondary">
                            Provider:{' '}
                            <strong>{providerLabels[status.provider as LlmProvider] ?? status.provider}</strong>
                        </Typography>
                        <FormControl size="small" fullWidth>
                            <InputLabel>Model</InputLabel>
                            <Select
                                value={status.model ?? ''}
                                label="Model"
                                onChange={(e) => handleModelChange(e.target.value)}
                            >
                                {modelsLoading ? (
                                    <MenuItem disabled>Loading models...</MenuItem>
                                ) : (
                                    popularModels.map((m: LlmModel) => (
                                        <MenuItem key={m.id} value={m.id}>
                                            {m.name} ({m.id})
                                        </MenuItem>
                                    ))
                                )}
                            </Select>
                        </FormControl>
                    </Box>
                ) : (
                    <Box sx={{display: 'flex', flexDirection: 'column', gap: 2}}>
                        <ToggleButtonGroup
                            value={selectedProvider}
                            exclusive
                            onChange={(_, value) => value && setSelectedProvider(value)}
                            size="small"
                            fullWidth
                        >
                            <ToggleButton value="anthropic">Anthropic (Claude)</ToggleButton>
                            <ToggleButton value="openrouter">OpenRouter</ToggleButton>
                        </ToggleButtonGroup>

                        {selectedProvider === 'anthropic' ? (
                            <Box sx={{display: 'flex', flexDirection: 'column', gap: 2}}>
                                <Typography variant="body2" color="text.secondary">
                                    Connect directly with your Anthropic API key to use Claude models.
                                </Typography>
                                <TextField
                                    size="small"
                                    fullWidth
                                    label="API Key"
                                    type="password"
                                    placeholder="sk-ant-..."
                                    value={apiKey}
                                    onChange={(e) => setApiKey(e.target.value)}
                                    onKeyDown={(e) => e.key === 'Enter' && handleApiKeyConnect()}
                                />
                            </Box>
                        ) : (
                            <Typography variant="body2" color="text.secondary">
                                Connect your OpenRouter account to access Claude, GPT-4, Llama, Mistral, and many other
                                models through a single OAuth integration.
                            </Typography>
                        )}
                    </Box>
                )}
            </CardContent>
            <CardActions>
                {status?.connected ? (
                    <Button size="small" color="error" onClick={handleDisconnect}>
                        Disconnect
                    </Button>
                ) : selectedProvider === 'anthropic' ? (
                    <Button size="small" variant="contained" onClick={handleApiKeyConnect} disabled={!apiKey.trim()}>
                        Connect with API Key
                    </Button>
                ) : (
                    <Button size="small" variant="contained" onClick={handleOpenRouterConnect}>
                        Connect with OpenRouter
                    </Button>
                )}
            </CardActions>
        </Card>
    );
};
