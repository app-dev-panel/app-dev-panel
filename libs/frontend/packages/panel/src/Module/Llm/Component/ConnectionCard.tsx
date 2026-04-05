import {
    clearAcpSessionId,
    LlmModel,
    LlmProvider,
    useConnectMutation,
    useDisconnectMutation,
    useGetModelsQuery,
    useGetStatusQuery,
    useOauthInitiateMutation,
    useSetCustomPromptMutation,
    useSetModelMutation,
} from '@app-dev-panel/panel/Module/Llm/API/Llm';
import ExpandMoreIcon from '@mui/icons-material/ExpandMore';
import {
    Alert,
    Autocomplete,
    Box,
    Button,
    Chip,
    Collapse,
    IconButton,
    InputAdornment,
    Paper,
    Skeleton,
    TextField,
    ToggleButton,
    ToggleButtonGroup,
    Typography,
} from '@mui/material';
import {useCallback, useEffect, useMemo, useState} from 'react';

const providerLabels: Record<LlmProvider, string> = {
    openrouter: 'OpenRouter',
    anthropic: 'Anthropic',
    openai: 'OpenAI',
    acp: 'ACP',
};

const isFreeModel = (model: LlmModel): boolean => {
    const prompt = model.pricing?.prompt;
    const completion = model.pricing?.completion;
    return (prompt === '0' || prompt === '0.0') && (completion === '0' || completion === '0.0');
};

export const ConnectionCard = () => {
    const {data: status, isLoading} = useGetStatusQuery();
    const [initiate] = useOauthInitiateMutation();
    const [connect] = useConnectMutation();
    const [disconnect] = useDisconnectMutation();
    const [setModel] = useSetModelMutation();
    const [setCustomPrompt] = useSetCustomPromptMutation();
    const {data: models, isLoading: modelsLoading} = useGetModelsQuery(undefined, {skip: !status?.connected});
    const [error, setError] = useState<string | null>(null);
    const [selectedProvider, setSelectedProvider] = useState<LlmProvider>('openrouter');
    const [apiKey, setApiKey] = useState('');
    const [acpCommand, setAcpCommand] = useState('npx');
    const [acpArgs, setAcpArgs] = useState('@agentclientprotocol/claude-agent-acp');
    const [expanded, setExpanded] = useState(false);
    const [freeOnly, setFreeOnly] = useState(false);
    const [localPrompt, setLocalPrompt] = useState(status?.customPrompt ?? '');

    useEffect(() => {
        if (status?.customPrompt !== undefined) {
            setLocalPrompt(status.customPrompt);
        }
    }, [status?.customPrompt]);

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
            await connect({
                provider: selectedProvider as 'openrouter' | 'anthropic' | 'openai',
                apiKey: apiKey.trim(),
            }).unwrap();
            setApiKey('');
        } catch {
            setError('Failed to connect with API key.');
        }
    }, [connect, selectedProvider, apiKey]);

    const handleAcpConnect = useCallback(async () => {
        setError(null);
        const cmd = acpCommand.trim();
        if (!cmd) {
            setError('Agent command is required.');
            return;
        }
        try {
            const args = acpArgs
                .trim()
                .split(/\s+/)
                .filter((s) => s !== '');
            const result = await connect({
                provider: 'acp',
                acpCommand: cmd,
                ...(args.length > 0 ? {acpArgs: args} : {}),
            }).unwrap();
            if (!result.connected) {
                setError(result.error ?? 'Failed to connect ACP agent.');
            }
        } catch {
            setError('Failed to connect ACP agent.');
        }
    }, [connect, acpCommand, acpArgs]);

    const handleDisconnect = useCallback(async () => {
        await disconnect();
        clearAcpSessionId();
        setExpanded(false);
    }, [disconnect]);

    const handleModelChange = useCallback(
        async (modelId: string) => {
            await setModel({model: modelId});
        },
        [setModel],
    );

    const provider = status?.provider;
    const connected = status?.connected ?? false;
    const isOpenRouter = provider === 'openrouter';
    const isAcp = provider === 'acp';

    const popularModels = useMemo(() => {
        let filtered = (models ?? []).filter((m: LlmModel) => {
            if (provider === 'anthropic') return m.id.startsWith('claude-');
            if (provider === 'openai')
                return m.id.startsWith('gpt-') || m.id.startsWith('o') || m.id.startsWith('chatgpt-');
            return (
                m.id.startsWith('anthropic/') ||
                m.id.startsWith('openai/') ||
                m.id.startsWith('google/') ||
                m.id.startsWith('meta-llama/') ||
                m.id.startsWith('mistralai/')
            );
        });
        if (freeOnly && isOpenRouter) {
            filtered = filtered.filter(isFreeModel);
        }
        return filtered;
    }, [models, provider, freeOnly, isOpenRouter]);

    const selectedModel = popularModels.find((m) => m.id === status?.model);

    if (isLoading) {
        return <Skeleton variant="rectangular" height={48} sx={{borderRadius: 1}} />;
    }

    // Connected: compact summary bar, expandable
    if (connected) {
        return (
            <Paper variant="outlined" sx={{overflow: 'hidden'}}>
                <Box
                    onClick={() => setExpanded((prev) => !prev)}
                    sx={{
                        display: 'flex',
                        alignItems: 'center',
                        gap: 1.5,
                        px: 2,
                        py: 1,
                        cursor: 'pointer',
                        '&:hover': {bgcolor: 'action.hover'},
                        transition: 'background-color 0.15s',
                    }}
                >
                    <Chip label="Connected" color="success" size="small" />
                    <Typography variant="body2" color="text.secondary">
                        {providerLabels[provider as LlmProvider] ?? provider}
                    </Typography>
                    {isAcp ? (
                        <Typography variant="body2" noWrap sx={{flex: 1, minWidth: 0}}>
                            {status?.model ?? 'Agent'}
                        </Typography>
                    ) : selectedModel ? (
                        <>
                            <Typography variant="body2" color="text.disabled">
                                /
                            </Typography>
                            <Typography variant="body2" noWrap sx={{flex: 1, minWidth: 0}}>
                                {selectedModel.name}
                            </Typography>
                        </>
                    ) : (
                        <Typography variant="body2" color="warning.main" sx={{flex: 1}}>
                            No model selected
                        </Typography>
                    )}
                    <IconButton
                        size="small"
                        sx={{transform: expanded ? 'rotate(180deg)' : 'rotate(0deg)', transition: 'transform 0.2s'}}
                    >
                        <ExpandMoreIcon fontSize="small" />
                    </IconButton>
                </Box>
                <Collapse in={expanded}>
                    <Box sx={{px: 2, pb: 2, pt: 1, display: 'flex', flexDirection: 'column', gap: 2}}>
                        {error && (
                            <Alert severity="error" onClose={() => setError(null)}>
                                {error}
                            </Alert>
                        )}
                        {!isAcp && (
                            <Autocomplete
                                size="small"
                                fullWidth
                                options={popularModels}
                                getOptionLabel={(option: LlmModel) =>
                                    isOpenRouter && isFreeModel(option)
                                        ? `${option.name} (free) (${option.id})`
                                        : `${option.name} (${option.id})`
                                }
                                value={selectedModel ?? null}
                                onChange={(_, option) => option && handleModelChange(option.id)}
                                loading={modelsLoading}
                                isOptionEqualToValue={(option, value) => option.id === value.id}
                                renderInput={(params) => (
                                    <TextField
                                        {...params}
                                        label="Model"
                                        InputProps={{
                                            ...params.InputProps,
                                            endAdornment: (
                                                <>
                                                    {isOpenRouter && (
                                                        <InputAdornment position="end" sx={{mr: -0.5}}>
                                                            <ToggleButton
                                                                value="free"
                                                                selected={freeOnly}
                                                                onChange={(e) => {
                                                                    e.stopPropagation();
                                                                    setFreeOnly((prev) => !prev);
                                                                }}
                                                                size="small"
                                                                sx={{
                                                                    px: 1,
                                                                    py: 0,
                                                                    height: 24,
                                                                    textTransform: 'none',
                                                                    fontWeight: 600,
                                                                    fontSize: '11px',
                                                                    lineHeight: 1,
                                                                    borderRadius: 1,
                                                                }}
                                                            >
                                                                Free
                                                            </ToggleButton>
                                                        </InputAdornment>
                                                    )}
                                                    {params.InputProps.endAdornment}
                                                </>
                                            ),
                                        }}
                                    />
                                )}
                            />
                        )}
                        {isAcp && (
                            <Typography variant="body2" color="text.secondary">
                                Connected to local AI agent via Agent Client Protocol (stdio subprocess). Model
                                selection is managed by the agent.
                            </Typography>
                        )}
                        <TextField
                            size="small"
                            fullWidth
                            multiline
                            minRows={2}
                            maxRows={5}
                            label="Custom instructions"
                            placeholder="e.g. Reply in English. Focus on root causes and fixes..."
                            value={localPrompt}
                            onChange={(e) => setLocalPrompt(e.target.value)}
                            onBlur={() => {
                                if (localPrompt !== (status?.customPrompt ?? '')) {
                                    setCustomPrompt({customPrompt: localPrompt});
                                }
                            }}
                            helperText="Appended to every LLM request (chat & analyze)"
                        />
                        <Box>
                            <Button size="small" color="error" onClick={handleDisconnect}>
                                Disconnect
                            </Button>
                        </Box>
                    </Box>
                </Collapse>
            </Paper>
        );
    }

    // Disconnected: full setup form
    return (
        <Paper variant="outlined" sx={{p: 2, display: 'flex', flexDirection: 'column', gap: 2}}>
            <Box
                sx={{display: 'flex', alignItems: 'center', justifyContent: 'space-between', gap: 1, flexWrap: 'wrap'}}
            >
                <Box sx={{minWidth: 0}}>
                    <Typography variant="body1" fontWeight={600}>
                        LLM Connection
                    </Typography>
                    <Typography variant="body2" color="text.secondary">
                        Connect an LLM provider to use AI-powered debug analysis
                    </Typography>
                </Box>
                <Chip label="Disconnected" size="small" color="error" />
            </Box>

            {error && (
                <Alert severity="error" onClose={() => setError(null)}>
                    {error}
                </Alert>
            )}

            <ToggleButtonGroup
                value={selectedProvider}
                exclusive
                onChange={(_, value) => value && setSelectedProvider(value)}
                size="small"
                fullWidth
                sx={{display: 'grid', gridTemplateColumns: {xs: '1fr', sm: 'repeat(2, 1fr)', md: 'repeat(4, 1fr)'}}}
            >
                <ToggleButton value="openrouter">OpenRouter</ToggleButton>
                <ToggleButton value="anthropic">Anthropic</ToggleButton>
                <ToggleButton value="openai">OpenAI</ToggleButton>
                <ToggleButton value="acp">ACP</ToggleButton>
            </ToggleButtonGroup>

            {selectedProvider === 'openrouter' ? (
                <Typography variant="body2" color="text.secondary">
                    Connect your OpenRouter account to access Claude, GPT-4, Llama, Mistral, and many other models
                    through a single OAuth integration.
                </Typography>
            ) : selectedProvider === 'anthropic' ? (
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
            ) : selectedProvider === 'acp' ? (
                <Box sx={{display: 'flex', flexDirection: 'column', gap: 2}}>
                    <Typography variant="body2" color="text.secondary">
                        Connect to a local AI agent via Agent Client Protocol. Uses an ACP adapter to communicate with
                        Claude Code, Gemini CLI, Codex CLI, or any ACP-compatible agent.
                    </Typography>
                    <TextField
                        size="small"
                        fullWidth
                        label="Command"
                        placeholder="npx"
                        value={acpCommand}
                        onChange={(e) => setAcpCommand(e.target.value)}
                        onKeyDown={(e) => e.key === 'Enter' && handleAcpConnect()}
                        helperText="Executable to run (must be on system PATH)"
                    />
                    <TextField
                        size="small"
                        fullWidth
                        label="Arguments"
                        placeholder="@agentclientprotocol/claude-agent-acp"
                        value={acpArgs}
                        onChange={(e) => setAcpArgs(e.target.value)}
                        onKeyDown={(e) => e.key === 'Enter' && handleAcpConnect()}
                        helperText="ACP adapter package or CLI arguments (space-separated)"
                    />
                </Box>
            ) : (
                <Box sx={{display: 'flex', flexDirection: 'column', gap: 2}}>
                    <Typography variant="body2" color="text.secondary">
                        Connect with your OpenAI API key to use GPT-4o, o1, and other models.
                    </Typography>
                    <TextField
                        size="small"
                        fullWidth
                        label="API Key"
                        type="password"
                        placeholder="sk-proj-..."
                        value={apiKey}
                        onChange={(e) => setApiKey(e.target.value)}
                        onKeyDown={(e) => e.key === 'Enter' && handleApiKeyConnect()}
                    />
                </Box>
            )}

            {selectedProvider === 'openrouter' ? (
                <Button
                    size="small"
                    variant="contained"
                    onClick={handleOpenRouterConnect}
                    sx={{alignSelf: 'flex-start'}}
                >
                    Connect with OpenRouter
                </Button>
            ) : selectedProvider === 'acp' ? (
                <Button
                    size="small"
                    variant="contained"
                    onClick={handleAcpConnect}
                    disabled={!acpCommand.trim()}
                    sx={{alignSelf: 'flex-start'}}
                >
                    Connect Agent
                </Button>
            ) : (
                <Button
                    size="small"
                    variant="contained"
                    onClick={handleApiKeyConnect}
                    disabled={!apiKey.trim()}
                    sx={{alignSelf: 'flex-start'}}
                >
                    Connect with API Key
                </Button>
            )}
        </Paper>
    );
};
