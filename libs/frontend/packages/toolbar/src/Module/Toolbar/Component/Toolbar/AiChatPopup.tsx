import {DebugEntry} from '@app-dev-panel/sdk/API/Debug/Debug';
import {clearPrefillMessage} from '@app-dev-panel/sdk/API/Llm/AiChatSlice';
import {
    type ChatMessage,
    useAddHistoryMutation,
    useChatMutation,
    useGetStatusQuery,
} from '@app-dev-panel/sdk/API/Llm/Llm';
import {DuckIcon} from '@app-dev-panel/sdk/Component/SvgIcon/DuckIcon';
import {isDebugEntryAboutConsole, isDebugEntryAboutWeb} from '@app-dev-panel/sdk/Helper/debugEntry';
import CloseIcon from '@mui/icons-material/Close';
import SendIcon from '@mui/icons-material/Send';
import SmartToyIcon from '@mui/icons-material/SmartToy';
import {Box, Chip, CircularProgress, IconButton, Link, Paper, Portal, TextField, Typography} from '@mui/material';
import {useCallback, useEffect, useRef, useState} from 'react';
import {useDispatch, useSelector} from 'react-redux';

type Message = {role: 'duck' | 'user'; content: string; status?: 'ok' | 'sending' | 'error'; error?: string};

const formatSummary = (entry: DebugEntry): string => {
    const parts: string[] = [];
    if (isDebugEntryAboutWeb(entry)) {
        parts.push(`${entry.request?.method} ${entry.request?.path} \u2192 ${entry.response?.statusCode}`);
    }
    if (isDebugEntryAboutConsole(entry)) {
        parts.push(`CLI: ${entry.command?.input} \u2192 exit ${entry.command?.exitCode}`);
    }
    const timing = entry.web || entry.console;
    if (timing) {
        const ms = (timing.request.processingTime * 1000).toFixed(0);
        const mem = (timing.memory.peakUsage / (1024 * 1024)).toFixed(1);
        parts.push(`${ms}ms, ${mem}MB`);
    }
    if (entry.db) parts.push(`DB: ${entry.db.queries.total} queries`);
    if (entry.exception) parts.push(`Exception: ${entry.exception.class}`);
    if (entry.deprecation?.total) parts.push(`${entry.deprecation.total} deprecations`);
    return parts.join(' | ');
};

const buildContextPrompt = (entry: DebugEntry): string => {
    const parts: string[] = ['Analyze this debug entry:'];
    if (isDebugEntryAboutWeb(entry)) {
        parts.push(`Request: ${entry.request?.method} ${entry.request?.path} \u2192 ${entry.response?.statusCode}`);
    }
    if (isDebugEntryAboutConsole(entry)) {
        parts.push(`Command: ${entry.command?.input} \u2192 exit ${entry.command?.exitCode}`);
    }
    const timing = entry.web || entry.console;
    if (timing) {
        parts.push(`Time: ${(timing.request.processingTime * 1000).toFixed(0)}ms`);
        parts.push(`Memory: ${(timing.memory.peakUsage / (1024 * 1024)).toFixed(1)}MB`);
    }
    if (entry.db) {
        parts.push(`Database: ${entry.db.queries.total} queries`);
        if (entry.db.queries.total > 0) {
            const slowest = entry.db.queries.items
                ?.slice(0, 5)
                .map((q: {sql?: string; duration?: number}) => `  - ${q.sql ?? '?'} (${q.duration ?? '?'}ms)`)
                .join('\n');
            if (slowest) parts.push(`Slowest queries:\n${slowest}`);
        }
    }
    if (entry.exception) {
        parts.push(`Exception: ${entry.exception.class}: ${entry.exception.message}`);
    }
    if (entry.log?.total) parts.push(`Logs: ${entry.log.total} entries`);
    if (entry.deprecation?.total) parts.push(`Deprecations: ${entry.deprecation.total}`);
    return parts.join('\n');
};

const SUGGESTIONS_CONNECTED = ['Analyze request', 'Performance tips', 'Explain errors', 'Suggest fixes'];
const SUGGESTIONS_DISCONNECTED = ['Show queries', 'Performance tips', 'Show logs', 'Explain route'];

const DEFAULT_POS = {x: -1, y: -1, w: 360, h: 480};

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

type AiChatPopupProps = {
    open: boolean;
    onClose: () => void;
    entry: DebugEntry | null;
    toolbarPosition?: 'float' | 'bottom' | 'right' | 'left';
};

export const AiChatPopup = ({open, onClose, entry, toolbarPosition = 'bottom'}: AiChatPopupProps) => {
    const {data: status, isLoading: statusLoading} = useGetStatusQuery(undefined, {skip: !open});
    const [chat, {isLoading: chatLoading}] = useChatMutation();
    const [addHistory] = useAddHistoryMutation();
    const reduxDispatch = useDispatch();
    const prefillMessage = useSelector(
        (state: {aiChat?: {prefillMessage: string | null}}) => state.aiChat?.prefillMessage,
    );

    const connected = status?.connected ?? false;
    const suggestions = connected ? SUGGESTIONS_CONNECTED : SUGGESTIONS_DISCONNECTED;

    const [messages, setMessages] = useState<Message[]>([]);
    const [chatHistory, setChatHistory] = useState<ChatMessage[]>([]);
    const [input, setInput] = useState('');
    const [pos, setPos] = useState(DEFAULT_POS);
    const messagesEndRef = useRef<HTMLDivElement>(null);
    const prevEntryId = useRef<string | null>(null);
    const chatRef = useRef<HTMLDivElement>(null);

    // Drag state
    const dragRef = useRef<{startX: number; startY: number; startLeft: number; startTop: number} | null>(null);
    // Resize state
    const resizeRef = useRef<{startX: number; startY: number; startW: number; startH: number} | null>(null);

    // Compute initial position based on toolbar position
    useEffect(() => {
        if (open && pos.x === -1) {
            if (toolbarPosition === 'bottom')
                setPos((p) => ({...p, x: window.innerWidth - 372, y: window.innerHeight - 540}));
            else if (toolbarPosition === 'right') setPos((p) => ({...p, x: window.innerWidth - 640, y: 60}));
            else if (toolbarPosition === 'left') setPos((p) => ({...p, x: 272, y: 60}));
            else setPos((p) => ({...p, x: window.innerWidth - 372, y: window.innerHeight - 540}));
        }
    }, [open, toolbarPosition]);

    useEffect(() => {
        if (entry && entry.id !== prevEntryId.current) {
            prevEntryId.current = entry.id;
            setMessages([{role: 'duck', content: formatSummary(entry), status: 'ok'}]);
            setChatHistory([]);
        }
    }, [entry]);

    useEffect(() => {
        if (open && prefillMessage) {
            setInput(prefillMessage);
            reduxDispatch(clearPrefillMessage());
        }
    }, [open, prefillMessage, reduxDispatch]);

    useEffect(() => {
        messagesEndRef.current?.scrollIntoView({behavior: 'smooth'});
    }, [messages]);

    // Global mouse listeners for drag + resize
    useEffect(() => {
        const onMouseMove = (e: MouseEvent) => {
            const drag = dragRef.current;
            if (drag) {
                const x = drag.startLeft + (e.clientX - drag.startX);
                const y = drag.startTop + (e.clientY - drag.startY);
                setPos((p) => ({...p, x, y}));
            }
            const resize = resizeRef.current;
            if (resize) {
                const w = Math.max(280, resize.startW + (e.clientX - resize.startX));
                const h = Math.max(320, resize.startH + (e.clientY - resize.startY));
                setPos((p) => ({...p, w, h}));
            }
        };
        const onMouseUp = () => {
            dragRef.current = null;
            resizeRef.current = null;
        };
        document.addEventListener('mousemove', onMouseMove);
        document.addEventListener('mouseup', onMouseUp);
        return () => {
            document.removeEventListener('mousemove', onMouseMove);
            document.removeEventListener('mouseup', onMouseUp);
        };
    }, []);

    const onHeaderMouseDown = useCallback((e: React.MouseEvent) => {
        if ((e.target as HTMLElement).closest('button')) return;
        e.preventDefault();
        const rect = chatRef.current?.getBoundingClientRect();
        if (!rect) return;
        dragRef.current = {startX: e.clientX, startY: e.clientY, startLeft: rect.left, startTop: rect.top};
        setPos((p) => ({...p, x: rect.left, y: rect.top}));
    }, []);

    const onResizeMouseDown = useCallback((e: React.MouseEvent) => {
        e.preventDefault();
        e.stopPropagation();
        const rect = chatRef.current?.getBoundingClientRect();
        if (!rect) return;
        resizeRef.current = {startX: e.clientX, startY: e.clientY, startW: rect.width, startH: rect.height};
    }, []);

    const sendMessage = useCallback(
        async (text: string) => {
            if (!text.trim()) return;
            const userText = text.trim();

            setMessages((prev) => [...prev, {role: 'user', content: userText, status: 'ok'}]);
            setInput('');

            if (!connected) {
                setMessages((prev) => [
                    ...prev,
                    {
                        role: 'duck',
                        content:
                            'AI is not connected. Configure your LLM provider in the main panel (LLM section) to enable AI chat.',
                        status: 'ok',
                    },
                ]);
                return;
            }

            // Build messages with debug context
            const contextPrefix = entry ? buildContextPrompt(entry) : '';
            const newUserMessage: ChatMessage = {
                role: 'user',
                content: contextPrefix ? `${contextPrefix}\n\nUser question: ${userText}` : userText,
            };

            // For display, use just the user text; for API, include context in first message
            const isFirstMessage = chatHistory.length === 0;
            const apiMessages: ChatMessage[] = isFirstMessage
                ? [newUserMessage]
                : [...chatHistory, {role: 'user', content: userText}];

            // Add sending indicator
            setMessages((prev) => [...prev, {role: 'duck', content: '', status: 'sending'}]);

            try {
                const result = await chat({messages: apiMessages}).unwrap();
                const assistantContent = result.choices?.[0]?.message?.content ?? 'No response.';

                // Update chat history for multi-turn
                const updatedHistory: ChatMessage[] = [...apiMessages, {role: 'assistant', content: assistantContent}];
                setChatHistory(updatedHistory);

                setMessages((prev) => {
                    const filtered = prev.filter((m) => m.status !== 'sending');
                    return [...filtered, {role: 'duck', content: assistantContent, status: 'ok'}];
                });

                addHistory({query: userText, response: assistantContent, timestamp: Math.floor(Date.now() / 1000)});
            } catch (err: unknown) {
                const errorMsg = extractErrorMessage(err) ?? 'Failed to get response from LLM.';
                setMessages((prev) => {
                    const filtered = prev.filter((m) => m.status !== 'sending');
                    return [...filtered, {role: 'duck', content: errorMsg, status: 'error', error: errorMsg}];
                });
            }
        },
        [connected, entry, chatHistory, chat, addHistory],
    );

    if (!open) return null;

    const statusDotColor = statusLoading ? 'text.disabled' : connected ? 'success.main' : 'error.main';

    return (
        <Portal>
            <Paper
                ref={chatRef}
                elevation={6}
                sx={{
                    position: 'fixed',
                    left: pos.x,
                    top: pos.y,
                    width: pos.w,
                    height: pos.h,
                    zIndex: 1400,
                    borderRadius: 3,
                    border: 1,
                    borderColor: 'divider',
                    display: 'flex',
                    flexDirection: 'column',
                    overflow: 'hidden',
                }}
            >
                {/* Header - draggable */}
                <Box
                    onMouseDown={onHeaderMouseDown}
                    sx={{
                        display: 'flex',
                        alignItems: 'center',
                        gap: 1,
                        px: 1.5,
                        py: 1,
                        borderBottom: 1,
                        borderColor: 'divider',
                        flexShrink: 0,
                        cursor: 'grab',
                        userSelect: 'none',
                        '&:active': {cursor: 'grabbing'},
                    }}
                >
                    <DuckIcon sx={{fontSize: 22}} />
                    <Typography sx={{fontSize: 13, fontWeight: 600, flex: 1}}>Debug Duck</Typography>
                    {connected && status?.model && (
                        <Typography sx={{fontSize: 10, color: 'text.disabled', maxWidth: 100}} noWrap>
                            {status.model}
                        </Typography>
                    )}
                    <Box sx={{width: 6, height: 6, borderRadius: '50%', bgcolor: statusDotColor}} />
                    <IconButton size="small" onClick={onClose} sx={{color: 'text.secondary'}}>
                        <CloseIcon sx={{fontSize: 16}} />
                    </IconButton>
                </Box>

                {/* Not connected banner */}
                {!statusLoading && !connected && (
                    <Box
                        sx={{
                            px: 1.5,
                            py: 1,
                            bgcolor: 'warning.light',
                            display: 'flex',
                            alignItems: 'center',
                            gap: 1,
                            flexShrink: 0,
                        }}
                    >
                        <SmartToyIcon sx={{fontSize: 14, color: 'warning.dark'}} />
                        <Typography sx={{fontSize: 11, color: 'warning.dark'}}>
                            AI not connected.{' '}
                            <Link
                                href="#"
                                onClick={(e: React.MouseEvent) => {
                                    e.preventDefault();
                                    window.open(
                                        (window as unknown as {__adp_panel_url?: string}).__adp_panel_url || '/debug',
                                        '_blank',
                                    );
                                }}
                                sx={{fontSize: 11, fontWeight: 600, color: 'warning.dark'}}
                            >
                                Configure in panel
                            </Link>
                        </Typography>
                    </Box>
                )}

                {/* Messages */}
                <Box
                    sx={{flex: 1, overflowY: 'auto', px: 1.5, py: 1, display: 'flex', flexDirection: 'column', gap: 1}}
                >
                    {messages.map((msg, i) => (
                        <Box
                            key={i}
                            sx={{
                                maxWidth: '85%',
                                alignSelf: msg.role === 'user' ? 'flex-end' : 'flex-start',
                                px: 1.5,
                                py: 1,
                                borderRadius: 2,
                                borderBottomLeftRadius: msg.role === 'duck' ? 1 : undefined,
                                borderBottomRightRadius: msg.role === 'user' ? 1 : undefined,
                                bgcolor:
                                    msg.status === 'error'
                                        ? 'error.light'
                                        : msg.role === 'duck'
                                          ? 'action.hover'
                                          : 'primary.main',
                                color:
                                    msg.status === 'error'
                                        ? 'error.contrastText'
                                        : msg.role === 'user'
                                          ? 'primary.contrastText'
                                          : 'text.primary',
                            }}
                        >
                            {msg.status === 'sending' ? (
                                <Box sx={{display: 'flex', alignItems: 'center', gap: 1}}>
                                    <CircularProgress size={12} />
                                    <Typography sx={{fontSize: 12, color: 'text.secondary'}}>Thinking...</Typography>
                                </Box>
                            ) : (
                                <Typography sx={{fontSize: 12, lineHeight: 1.5, whiteSpace: 'pre-wrap'}}>
                                    {msg.content}
                                </Typography>
                            )}
                        </Box>
                    ))}
                    <div ref={messagesEndRef} />
                </Box>

                {/* Suggestions */}
                <Box sx={{px: 1.5, pb: 0.5, display: 'flex', gap: 0.5, flexWrap: 'wrap', flexShrink: 0}}>
                    {suggestions.map((s) => (
                        <Chip
                            key={s}
                            label={s}
                            size="small"
                            variant="outlined"
                            onClick={() => sendMessage(s)}
                            disabled={chatLoading}
                            sx={{height: 22, fontSize: 10, cursor: 'pointer', '& .MuiChip-label': {px: 1}}}
                        />
                    ))}
                </Box>

                {/* Input */}
                <Box sx={{display: 'flex', gap: 0.5, p: 1, borderTop: 1, borderColor: 'divider', flexShrink: 0}}>
                    <TextField
                        size="small"
                        fullWidth
                        placeholder={connected ? 'Ask about this request...' : 'Ask the duck...'}
                        value={input}
                        onChange={(e) => setInput(e.target.value)}
                        onKeyDown={(e) => {
                            if (e.key === 'Enter' && !e.shiftKey) {
                                e.preventDefault();
                                sendMessage(input);
                            }
                        }}
                        disabled={chatLoading}
                        slotProps={{input: {sx: {fontSize: 12, py: 0.75, borderRadius: 4}}}}
                    />
                    <IconButton
                        size="small"
                        onClick={() => sendMessage(input)}
                        disabled={!input.trim() || chatLoading}
                        sx={{color: 'primary.main'}}
                    >
                        {chatLoading ? <CircularProgress size={18} /> : <SendIcon sx={{fontSize: 18}} />}
                    </IconButton>
                </Box>

                {/* Resize handle - bottom-right */}
                <Box
                    onMouseDown={onResizeMouseDown}
                    sx={{
                        position: 'absolute',
                        bottom: 0,
                        right: 0,
                        width: 18,
                        height: 18,
                        cursor: 'se-resize',
                        display: 'flex',
                        alignItems: 'center',
                        justifyContent: 'center',
                        opacity: 0.3,
                        '&:hover': {opacity: 0.7},
                    }}
                >
                    <svg width="10" height="10" viewBox="0 0 10 10">
                        <line x1="9" y1="1" x2="1" y2="9" stroke="currentColor" strokeWidth="1.2" />
                        <line x1="9" y1="4" x2="4" y2="9" stroke="currentColor" strokeWidth="1.2" />
                        <line x1="9" y1="7" x2="7" y2="9" stroke="currentColor" strokeWidth="1.2" />
                    </svg>
                </Box>
            </Paper>
        </Portal>
    );
};
