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
import {
    type ChatBubble,
    addMessage,
    clearPrefillMessage,
    removeErrorMessages,
    updateLastSending,
} from '@app-dev-panel/sdk/API/Llm/AiChatSlice';
import {MessageCopyButton} from '@app-dev-panel/sdk/Component/MessageCopyButton';
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
import React, {memo, useCallback, useEffect, useMemo, useRef, useState} from 'react';
import {useDispatch, useSelector} from 'react-redux';

type MessageStatus = 'ok' | 'error' | 'sending';
type Message = {role: 'user' | 'assistant'; content: string; status: MessageStatus; error?: string};

const containerSx = {display: 'flex', flexDirection: 'column', gap: 2, height: '100%'} as const;
const paperSx = {
    flex: 1,
    minHeight: 300,
    maxHeight: 500,
    overflow: 'auto',
    p: 2,
    display: 'flex',
    flexDirection: 'column',
    gap: 1.5,
} as const;
const emptyMsgSx = {textAlign: 'center', mt: 4} as const;
const userMsgSx = {alignSelf: 'flex-end', maxWidth: '80%'} as const;
const assistantMsgSx = {alignSelf: 'flex-start', maxWidth: '80%', position: 'relative'} as const;
const userBubbleSx = {
    p: 1.5,
    borderRadius: 2,
    bgcolor: 'primary.main',
    color: 'primary.contrastText',
    position: 'relative',
} as const;
const assistantBubbleSx = {
    p: 1.5,
    borderRadius: 2,
    bgcolor: 'background.default',
    color: 'text.primary',
    position: 'relative',
} as const;
const sendingOpacity = {opacity: 0.7} as const;
const loadingSx = {display: 'flex', alignItems: 'center', gap: 1} as const;
const inputRowSx = {display: 'flex', gap: 1} as const;
const errorCaptionSx = {mt: 0.5, display: 'block'} as const;
const replayIconSx = {fontSize: 16} as const;
const historySx = {
    '&:before': {display: 'none'},
    border: 1,
    borderColor: 'divider',
    borderRadius: '8px !important',
    overflow: 'hidden',
} as const;
const historyHeaderSx = {display: 'flex', alignItems: 'center', gap: 1, flex: 1} as const;
const historyIconSx = {fontSize: 18, color: 'text.secondary'} as const;
const clearBtnSx = {mr: 1} as const;
const deleteIconSx = {fontSize: 14} as const;
const historyItemSx = {'&:before': {display: 'none'}, boxShadow: 'none', borderTop: 1, borderColor: 'divider'} as const;
const historyItemHeaderSx = {display: 'flex', alignItems: 'center', gap: 1, flex: 1, minWidth: 0} as const;
const historyErrorIconSx = {fontSize: 14, color: 'error.main', flexShrink: 0} as const;
const historyResponseSx = {bgcolor: 'background.default', borderRadius: 1, p: 1.5} as const;

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

type ChatInputProps = {
    onSend: (text: string) => void;
    disabled: boolean;
    loading: boolean;
    prefillMessage: string | null;
    onClearPrefill: () => void;
};

const ChatInput = memo(({onSend, disabled, loading, prefillMessage, onClearPrefill}: ChatInputProps) => {
    const [input, setInput] = useState('');

    useEffect(() => {
        if (prefillMessage) {
            setInput(prefillMessage);
            onClearPrefill();
        }
    }, [prefillMessage, onClearPrefill]);

    const handleSend = useCallback(() => {
        if (!input.trim() || disabled) return;
        onSend(input.trim());
        setInput('');
    }, [input, disabled, onSend]);

    const handleKeyDown = useCallback(
        (e: React.KeyboardEvent) => {
            if (e.key === 'Enter' && !e.shiftKey) {
                e.preventDefault();
                handleSend();
            }
        },
        [handleSend],
    );

    const handleInputChange = useCallback((e: React.ChangeEvent<HTMLInputElement>) => setInput(e.target.value), []);

    return (
        <Box sx={inputRowSx}>
            <TextField
                fullWidth
                size="small"
                placeholder="Ask about your application..."
                value={input}
                onChange={handleInputChange}
                onKeyDown={handleKeyDown}
                multiline
                maxRows={3}
            />
            <SendButton label="Send" onClick={handleSend} disabled={!input.trim()} loading={loading} />
        </Box>
    );
});

export const ChatPanel = () => {
    const {data: status} = useGetStatusQuery();
    const [chat, {isLoading}] = useChatMutation();
    const {data: history = []} = useGetHistoryQuery();
    const [addHistory] = useAddHistoryMutation();
    const [deleteHistory] = useDeleteHistoryMutation();
    const [clearHistory] = useClearHistoryMutation();
    const messages = useSelector(
        (state: {aiChat?: {messages: ChatBubble[]}}) => state.aiChat?.messages ?? [],
    ) as Message[];
    const messagesEndRef = useRef<HTMLDivElement>(null);
    const dispatch = useDispatch();
    const prefillMessage = useSelector(
        (state: {aiChat?: {prefillMessage: string | null}}) => state.aiChat?.prefillMessage,
    );

    const scrollTimerRef = useRef<ReturnType<typeof setTimeout>>(null);
    const scrollToBottom = useCallback(() => {
        clearTimeout(scrollTimerRef.current);
        scrollTimerRef.current = setTimeout(() => messagesEndRef.current?.scrollIntoView({behavior: 'smooth'}), 100);
    }, []);

    const messagesRef = useRef(messages);
    messagesRef.current = messages;

    const handleSend = useCallback(
        async (userText: string) => {
            const userMessage: ChatBubble = {role: 'user', content: userText, status: 'ok'};
            dispatch(removeErrorMessages());
            dispatch(addMessage(userMessage));
            dispatch(addMessage({role: 'assistant', content: '', status: 'sending'}));
            scrollToBottom();

            const outgoing = [...messagesRef.current.filter((m) => m.status !== 'error'), userMessage];
            try {
                const result = await chat({
                    messages: outgoing
                        .filter((m) => m.status !== 'error')
                        .map((m) => ({role: m.role, content: m.content})),
                }).unwrap();

                const assistantContent = result.choices?.[0]?.message?.content ?? 'No response.';
                dispatch(updateLastSending({status: 'ok', content: assistantContent}));
                addHistory({query: userText, response: assistantContent, timestamp: Math.floor(Date.now() / 1000)});
            } catch (err: unknown) {
                const errorMsg = extractErrorMessage(err) ?? 'Failed to get response from LLM.';
                dispatch(updateLastSending({status: 'error', content: errorMsg, error: errorMsg}));
                addHistory({query: userText, response: '', timestamp: Math.floor(Date.now() / 1000), error: errorMsg});
            }
            scrollToBottom();
        },
        [chat, scrollToBottom, addHistory, dispatch],
    );

    const handleRetry = useCallback(
        async (index: number) => {
            const msg = messagesRef.current[index];
            if (!msg || msg.status !== 'error') return;

            const userText = msg.content;
            const retryMessage: ChatBubble = {role: 'user', content: userText, status: 'ok'};
            dispatch(removeErrorMessages());
            dispatch(addMessage(retryMessage));
            dispatch(addMessage({role: 'assistant', content: '', status: 'sending'}));
            scrollToBottom();

            const outgoing = [...messagesRef.current.filter((_, i) => i !== index), retryMessage];
            try {
                const result = await chat({
                    messages: outgoing
                        .filter((m) => m.status !== 'error')
                        .map((m) => ({role: m.role, content: m.content})),
                }).unwrap();

                const assistantContent = result.choices?.[0]?.message?.content ?? 'No response.';
                dispatch(updateLastSending({status: 'ok', content: assistantContent}));
                addHistory({query: userText, response: assistantContent, timestamp: Math.floor(Date.now() / 1000)});
            } catch (err: unknown) {
                const errorMsg = extractErrorMessage(err) ?? 'Failed to get response from LLM.';
                dispatch(updateLastSending({status: 'error', content: errorMsg, error: errorMsg}));
                addHistory({query: userText, response: '', timestamp: Math.floor(Date.now() / 1000), error: errorMsg});
            }
            scrollToBottom();
        },
        [chat, scrollToBottom, addHistory, dispatch],
    );

    const handleClearPrefill = useCallback(() => dispatch(clearPrefillMessage()), [dispatch]);

    const handleClearHistory = useCallback(
        (e: React.MouseEvent) => {
            e.stopPropagation();
            clearHistory();
        },
        [clearHistory],
    );

    const bubbleSx = useMemo(
        () => ({
            user: userBubbleSx,
            userSending: {...userBubbleSx, ...sendingOpacity},
            assistant: assistantBubbleSx,
            assistantSending: {...assistantBubbleSx, ...sendingOpacity},
        }),
        [],
    );

    if (!status?.connected) {
        return <Alert severity="info">Connect an LLM provider first to use the chat feature.</Alert>;
    }

    return (
        <Box sx={containerSx}>
            <Paper variant="outlined" sx={paperSx}>
                {messages.length === 0 && (
                    <Typography variant="body2" color="text.secondary" sx={emptyMsgSx}>
                        Ask questions about your application, debug data, or get development advice.
                    </Typography>
                )}
                {messages.map((msg, i) => (
                    <Box key={i} sx={msg.role === 'user' ? userMsgSx : assistantMsgSx}>
                        {msg.status === 'error' ? (
                            <>
                                <Alert
                                    severity="error"
                                    action={
                                        <Tooltip title="Retry">
                                            <IconButton
                                                size="small"
                                                color="error"
                                                onClick={() => handleRetry(i)}
                                                aria-label="Retry"
                                            >
                                                <ReplayIcon sx={replayIconSx} />
                                            </IconButton>
                                        </Tooltip>
                                    }
                                >
                                    {msg.content}
                                </Alert>
                                {msg.error && (
                                    <Typography variant="caption" color="text.secondary" sx={errorCaptionSx}>
                                        {msg.error}
                                    </Typography>
                                )}
                            </>
                        ) : (
                            <Box
                                className="message-bubble"
                                sx={
                                    msg.role === 'user'
                                        ? bubbleSx.user
                                        : msg.status === 'sending'
                                          ? bubbleSx.assistantSending
                                          : bubbleSx.assistant
                                }
                            >
                                {msg.status === 'sending' ? (
                                    <Box sx={loadingSx}>
                                        <CircularProgress size={16} />
                                        <Typography variant="body2" color="text.secondary">
                                            Thinking...
                                        </Typography>
                                    </Box>
                                ) : msg.role === 'assistant' ? (
                                    <>
                                        <Markdown content={msg.content} />
                                        <MessageCopyButton text={msg.content} />
                                    </>
                                ) : (
                                    <>
                                        <Typography variant="body2" sx={{whiteSpace: 'pre-wrap', pr: 3}}>
                                            {msg.content}
                                        </Typography>
                                        <MessageCopyButton text={msg.content} variant="dark" />
                                    </>
                                )}
                            </Box>
                        )}
                    </Box>
                ))}
                <div ref={messagesEndRef} />
            </Paper>
            <ChatInput
                onSend={handleSend}
                disabled={isLoading}
                loading={isLoading}
                prefillMessage={prefillMessage ?? null}
                onClearPrefill={handleClearPrefill}
            />

            {/* History */}
            {history.length > 0 && (
                <Accordion disableGutters sx={historySx}>
                    <AccordionSummary expandIcon={<ExpandMoreIcon />}>
                        <Box sx={historyHeaderSx}>
                            <HistoryIcon sx={historyIconSx} />
                            <Typography variant="subtitle2">History</Typography>
                            <Typography variant="caption" color="text.secondary">
                                ({history.length})
                            </Typography>
                            <Box sx={{flex: 1}} />
                            <Tooltip title="Clear history">
                                <IconButton
                                    size="small"
                                    onClick={handleClearHistory}
                                    sx={clearBtnSx}
                                    aria-label="Clear history"
                                >
                                    <DeleteOutlineIcon sx={replayIconSx} />
                                </IconButton>
                            </Tooltip>
                        </Box>
                    </AccordionSummary>
                    <AccordionDetails sx={{p: 0}}>
                        {history.map((entry, i) => (
                            <Accordion key={`${entry.timestamp}-${i}`} disableGutters sx={historyItemSx}>
                                <AccordionSummary expandIcon={<ExpandMoreIcon />} sx={{minHeight: 40}}>
                                    <Box sx={historyItemHeaderSx}>
                                        {entry.error && <ErrorOutlineIcon sx={historyErrorIconSx} />}
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
                                                <DeleteOutlineIcon sx={deleteIconSx} />
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
                                        <Box sx={historyResponseSx}>
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
