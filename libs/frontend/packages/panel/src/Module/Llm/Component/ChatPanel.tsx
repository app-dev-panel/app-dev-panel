import {
    useAddHistoryMutation,
    useChatMutation,
    useClearHistoryMutation,
    useDeleteHistoryMutation,
    useGetHistoryQuery,
    useGetStatusQuery,
} from '@app-dev-panel/panel/Module/Llm/API/Llm';
import {Markdown} from '@app-dev-panel/panel/Module/Llm/Component/Markdown';
import {SendButton} from '@app-dev-panel/panel/Module/Llm/Component/SendButton';
import DeleteOutlineIcon from '@mui/icons-material/DeleteOutline';
import ErrorOutlineIcon from '@mui/icons-material/ErrorOutline';
import ExpandMoreIcon from '@mui/icons-material/ExpandMore';
import HistoryIcon from '@mui/icons-material/History';
import ReplayIcon from '@mui/icons-material/Replay';
import {
    Accordion,
    AccordionDetails,
    AccordionSummary,
    Alert,
    Box,
    CircularProgress,
    IconButton,
    Paper,
    TextField,
    Tooltip,
    Typography,
} from '@mui/material';
import {useCallback, useRef, useState} from 'react';

type MessageStatus = 'ok' | 'error' | 'sending';
type Message = {role: 'user' | 'assistant'; content: string; status: MessageStatus; error?: string};

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

const formatTime = (ts: number): string => {
    const d = new Date(ts * 1000);
    return d.toLocaleTimeString([], {hour: '2-digit', minute: '2-digit'});
};

export const ChatPanel = () => {
    const {data: status} = useGetStatusQuery();
    const [chat, {isLoading}] = useChatMutation();
    const {data: history = []} = useGetHistoryQuery();
    const [addHistory] = useAddHistoryMutation();
    const [deleteHistory] = useDeleteHistoryMutation();
    const [clearHistory] = useClearHistoryMutation();
    const [messages, setMessages] = useState<Message[]>([]);
    const [input, setInput] = useState('');
    const messagesEndRef = useRef<HTMLDivElement>(null);

    const scrollToBottom = useCallback(() => {
        setTimeout(() => messagesEndRef.current?.scrollIntoView({behavior: 'smooth'}), 100);
    }, []);

    const sendMessages = useCallback(
        async (outgoing: Message[]) => {
            const sendingMsg = outgoing.find((m) => m.status === 'sending');
            try {
                const result = await chat({
                    messages: outgoing
                        .filter((m) => m.status !== 'error')
                        .map((m) => ({role: m.role, content: m.content})),
                }).unwrap();

                const assistantContent = result.choices?.[0]?.message?.content ?? 'No response.';
                setMessages((prev) => {
                    const updated = prev.map((m) => (m.status === 'sending' ? {...m, status: 'ok' as const} : m));
                    return [...updated, {role: 'assistant', content: assistantContent, status: 'ok'}];
                });

                if (sendingMsg) {
                    addHistory({
                        query: sendingMsg.content,
                        response: assistantContent,
                        timestamp: Math.floor(Date.now() / 1000),
                    });
                }
            } catch (err: unknown) {
                const errorMsg = extractErrorMessage(err) ?? 'Failed to get response from LLM.';
                setMessages((prev) =>
                    prev.map((m) => (m.status === 'sending' ? {...m, status: 'error' as const, error: errorMsg} : m)),
                );
                if (sendingMsg) {
                    addHistory({
                        query: sendingMsg.content,
                        response: '',
                        timestamp: Math.floor(Date.now() / 1000),
                        error: errorMsg,
                    });
                    setInput(sendingMsg.content);
                }
            }
            scrollToBottom();
        },
        [chat, scrollToBottom, addHistory],
    );

    const handleSend = useCallback(async () => {
        if (!input.trim() || isLoading) return;

        const userMessage: Message = {role: 'user', content: input.trim(), status: 'sending'};
        const newMessages = [...messages.filter((m) => m.status !== 'error'), userMessage];
        setMessages(newMessages);
        setInput('');
        scrollToBottom();

        await sendMessages(newMessages);
    }, [input, messages, isLoading, sendMessages, scrollToBottom]);

    const handleRetry = useCallback(
        async (index: number) => {
            const msg = messages[index];
            if (!msg || msg.status !== 'error') return;

            const retryMessage: Message = {role: 'user', content: msg.content, status: 'sending'};
            const cleaned = messages.filter((_, i) => i !== index);
            const newMessages = [...cleaned, retryMessage];
            setMessages(newMessages);
            setInput('');
            scrollToBottom();

            await sendMessages(newMessages);
        },
        [messages, sendMessages, scrollToBottom],
    );

    if (!status?.connected) {
        return <Alert severity="info">Connect an LLM provider first to use the chat feature.</Alert>;
    }

    return (
        <Box sx={{display: 'flex', flexDirection: 'column', gap: 2, height: '100%'}}>
            <Paper
                variant="outlined"
                sx={{
                    flex: 1,
                    minHeight: 300,
                    maxHeight: 500,
                    overflow: 'auto',
                    p: 2,
                    display: 'flex',
                    flexDirection: 'column',
                    gap: 1.5,
                }}
            >
                {messages.length === 0 && (
                    <Typography variant="body2" color="text.secondary" sx={{textAlign: 'center', mt: 4}}>
                        Ask questions about your application, debug data, or get development advice.
                    </Typography>
                )}
                {messages.map((msg, i) => (
                    <Box key={i} sx={{alignSelf: msg.role === 'user' ? 'flex-end' : 'flex-start', maxWidth: '80%'}}>
                        <Box
                            sx={{
                                p: 1.5,
                                borderRadius: 2,
                                bgcolor:
                                    msg.status === 'error'
                                        ? 'error.light'
                                        : msg.role === 'user'
                                          ? 'primary.main'
                                          : 'background.default',
                                color:
                                    msg.status === 'error'
                                        ? 'error.contrastText'
                                        : msg.role === 'user'
                                          ? 'primary.contrastText'
                                          : 'text.primary',
                                border: msg.status === 'error' ? 2 : 0,
                                borderColor: 'error.main',
                                opacity: msg.status === 'sending' ? 0.7 : 1,
                            }}
                        >
                            {msg.role === 'assistant' ? (
                                <Markdown content={msg.content} />
                            ) : (
                                <Typography variant="body2" sx={{whiteSpace: 'pre-wrap'}}>
                                    {msg.content}
                                </Typography>
                            )}
                        </Box>
                        {msg.status === 'error' && (
                            <Box
                                sx={{
                                    display: 'flex',
                                    alignItems: 'center',
                                    gap: 0.5,
                                    mt: 0.5,
                                    justifyContent: 'flex-end',
                                }}
                            >
                                <ErrorOutlineIcon sx={{fontSize: 14, color: 'error.main'}} />
                                <Typography variant="caption" color="error.main" sx={{flex: 1}}>
                                    {msg.error}
                                </Typography>
                                <Tooltip title="Retry">
                                    <IconButton size="small" color="error" onClick={() => handleRetry(i)}>
                                        <ReplayIcon sx={{fontSize: 16}} />
                                    </IconButton>
                                </Tooltip>
                            </Box>
                        )}
                    </Box>
                ))}
                {isLoading && (
                    <Box sx={{display: 'flex', alignItems: 'center', gap: 1}}>
                        <CircularProgress size={16} />
                        <Typography variant="body2" color="text.secondary">
                            Thinking...
                        </Typography>
                    </Box>
                )}
                <div ref={messagesEndRef} />
            </Paper>
            <Box sx={{display: 'flex', gap: 1}}>
                <TextField
                    fullWidth
                    size="small"
                    placeholder="Ask about your application..."
                    value={input}
                    onChange={(e) => setInput(e.target.value)}
                    onKeyDown={(e) => {
                        if (e.key === 'Enter' && !e.shiftKey) {
                            e.preventDefault();
                            handleSend();
                        }
                    }}
                    multiline
                    maxRows={3}
                />
                <SendButton label="Send" onClick={handleSend} disabled={!input.trim()} loading={isLoading} />
            </Box>

            {/* History */}
            {history.length > 0 && (
                <Accordion
                    disableGutters
                    sx={{
                        '&:before': {display: 'none'},
                        border: 1,
                        borderColor: 'divider',
                        borderRadius: '8px !important',
                        overflow: 'hidden',
                    }}
                >
                    <AccordionSummary expandIcon={<ExpandMoreIcon />}>
                        <Box sx={{display: 'flex', alignItems: 'center', gap: 1, flex: 1}}>
                            <HistoryIcon sx={{fontSize: 18, color: 'text.secondary'}} />
                            <Typography variant="subtitle2">History</Typography>
                            <Typography variant="caption" color="text.secondary">
                                ({history.length})
                            </Typography>
                            <Box sx={{flex: 1}} />
                            <Tooltip title="Clear history">
                                <IconButton
                                    size="small"
                                    onClick={(e) => {
                                        e.stopPropagation();
                                        clearHistory();
                                    }}
                                    sx={{mr: 1}}
                                >
                                    <DeleteOutlineIcon sx={{fontSize: 16}} />
                                </IconButton>
                            </Tooltip>
                        </Box>
                    </AccordionSummary>
                    <AccordionDetails sx={{p: 0}}>
                        {history.map((entry, i) => (
                            <Accordion
                                key={`${entry.timestamp}-${i}`}
                                disableGutters
                                sx={{
                                    '&:before': {display: 'none'},
                                    boxShadow: 'none',
                                    borderTop: 1,
                                    borderColor: 'divider',
                                }}
                            >
                                <AccordionSummary expandIcon={<ExpandMoreIcon />} sx={{minHeight: 40}}>
                                    <Box sx={{display: 'flex', alignItems: 'center', gap: 1, flex: 1, minWidth: 0}}>
                                        {entry.error && (
                                            <ErrorOutlineIcon sx={{fontSize: 14, color: 'error.main', flexShrink: 0}} />
                                        )}
                                        <Typography
                                            variant="body2"
                                            sx={{
                                                overflow: 'hidden',
                                                textOverflow: 'ellipsis',
                                                whiteSpace: 'nowrap',
                                                flex: 1,
                                                color: entry.error ? 'error.main' : 'text.primary',
                                            }}
                                        >
                                            {entry.query}
                                        </Typography>
                                        <Typography variant="caption" color="text.disabled" sx={{flexShrink: 0, mr: 1}}>
                                            {formatTime(entry.timestamp)}
                                        </Typography>
                                        <Tooltip title="Delete">
                                            <IconButton
                                                size="small"
                                                onClick={(e) => {
                                                    e.stopPropagation();
                                                    deleteHistory(i);
                                                }}
                                            >
                                                <DeleteOutlineIcon sx={{fontSize: 14}} />
                                            </IconButton>
                                        </Tooltip>
                                    </Box>
                                </AccordionSummary>
                                <AccordionDetails sx={{pt: 0}}>
                                    {entry.error ? (
                                        <Alert severity="error" sx={{mb: 1}}>
                                            {entry.error}
                                        </Alert>
                                    ) : (
                                        <Box sx={{bgcolor: 'background.default', borderRadius: 1, p: 1.5}}>
                                            <Markdown content={entry.response} />
                                        </Box>
                                    )}
                                </AccordionDetails>
                            </Accordion>
                        ))}
                    </AccordionDetails>
                </Accordion>
            )}
        </Box>
    );
};
