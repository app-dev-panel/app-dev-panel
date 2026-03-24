import {
    LlmModel,
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
    Typography,
} from '@mui/material';
import {useCallback, useState} from 'react';

export const ConnectionCard = () => {
    const {data: status, isLoading} = useGetStatusQuery();
    const [initiate] = useOauthInitiateMutation();
    const [disconnect] = useDisconnectMutation();
    const [setModel] = useSetModelMutation();
    const {data: models, isLoading: modelsLoading} = useGetModelsQuery(undefined, {skip: !status?.connected});
    const [error, setError] = useState<string | null>(null);

    const handleConnect = useCallback(async () => {
        setError(null);
        try {
            const callbackUrl = window.location.origin + window.location.pathname.replace(/\/[^/]*$/, '/llm/callback');
            const result = await initiate({callbackUrl}).unwrap();
            sessionStorage.setItem('llm_code_verifier', result.codeVerifier);
            window.open(result.authUrl, '_blank', 'width=600,height=700');
        } catch {
            setError('Failed to initiate OAuth flow.');
        }
    }, [initiate]);

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

    const popularModels = (models ?? []).filter(
        (m: LlmModel) =>
            m.id.startsWith('anthropic/') ||
            m.id.startsWith('openai/') ||
            m.id.startsWith('google/') ||
            m.id.startsWith('meta-llama/') ||
            m.id.startsWith('mistralai/'),
    );

    return (
        <Card variant="outlined">
            <CardHeader
                title="LLM Connection"
                subheader="Connect via OpenRouter to use AI-powered debug analysis"
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
                            Provider: <strong>{status.provider}</strong>
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
                    <Typography variant="body2" color="text.secondary">
                        Connect your OpenRouter account to enable AI-powered analysis of debug data. OpenRouter provides
                        access to Claude, GPT-4, Llama, Mistral, and many other models through a single OAuth
                        integration.
                    </Typography>
                )}
            </CardContent>
            <CardActions>
                {status?.connected ? (
                    <Button size="small" color="error" onClick={handleDisconnect}>
                        Disconnect
                    </Button>
                ) : (
                    <Button size="small" variant="contained" onClick={handleConnect}>
                        Connect with OpenRouter
                    </Button>
                )}
            </CardActions>
        </Card>
    );
};
