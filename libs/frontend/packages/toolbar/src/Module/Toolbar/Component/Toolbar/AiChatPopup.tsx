import {DebugEntry} from '@app-dev-panel/sdk/API/Debug/Debug';
import {DuckIcon} from '@app-dev-panel/sdk/Component/SvgIcon/DuckIcon';
import {isDebugEntryAboutConsole, isDebugEntryAboutWeb} from '@app-dev-panel/sdk/Helper/debugEntry';
import CloseIcon from '@mui/icons-material/Close';
import SendIcon from '@mui/icons-material/Send';
import {Box, Chip, IconButton, Paper, Portal, TextField, Typography} from '@mui/material';
import {useCallback, useEffect, useRef, useState} from 'react';

type Message = {role: 'duck' | 'user'; content: string};

const formatSummary = (entry: DebugEntry): string => {
    const parts: string[] = [];
    if (isDebugEntryAboutWeb(entry)) {
        parts.push(`${entry.request?.method} ${entry.request?.path} → ${entry.response?.statusCode}`);
    }
    if (isDebugEntryAboutConsole(entry)) {
        parts.push(`CLI: ${entry.command?.input} → exit ${entry.command?.exitCode}`);
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

const SUGGESTIONS = ['Show queries', 'Performance tips', 'Show logs', 'Explain route'];

type AiChatPopupProps = {open: boolean; onClose: () => void; entry: DebugEntry | null};

export const AiChatPopup = ({open, onClose, entry}: AiChatPopupProps) => {
    const [messages, setMessages] = useState<Message[]>([]);
    const [input, setInput] = useState('');
    const messagesEndRef = useRef<HTMLDivElement>(null);
    const prevEntryId = useRef<string | null>(null);

    useEffect(() => {
        if (entry && entry.id !== prevEntryId.current) {
            prevEntryId.current = entry.id;
            setMessages([{role: 'duck', content: formatSummary(entry)}]);
        }
    }, [entry]);

    useEffect(() => {
        messagesEndRef.current?.scrollIntoView({behavior: 'smooth'});
    }, [messages]);

    const sendMessage = useCallback((text: string) => {
        if (!text.trim()) return;
        setMessages((prev) => [...prev, {role: 'user', content: text.trim()}]);
        setInput('');
        setTimeout(() => {
            setMessages((prev) => [
                ...prev,
                {
                    role: 'duck',
                    content:
                        'AI analysis is not yet connected. This chat will use the MCP server to provide debug insights in a future update.',
                },
            ]);
        }, 600);
    }, []);

    if (!open) return null;

    return (
        <Portal>
            <Paper
                elevation={6}
                sx={{
                    position: 'fixed',
                    bottom: 56,
                    right: 12,
                    width: 320,
                    height: 420,
                    zIndex: 1400,
                    borderRadius: 3,
                    border: 1,
                    borderColor: 'divider',
                    display: 'flex',
                    flexDirection: 'column',
                    overflow: 'hidden',
                }}
            >
                {/* Header */}
                <Box
                    sx={{
                        display: 'flex',
                        alignItems: 'center',
                        gap: 1,
                        px: 1.5,
                        py: 1,
                        borderBottom: 1,
                        borderColor: 'divider',
                        flexShrink: 0,
                    }}
                >
                    <DuckIcon sx={{fontSize: 22}} />
                    <Typography sx={{fontSize: 13, fontWeight: 600, flex: 1}}>Debug Duck</Typography>
                    <Box sx={{width: 6, height: 6, borderRadius: '50%', bgcolor: 'success.main'}} />
                    <IconButton size="small" onClick={onClose} sx={{color: 'text.secondary'}}>
                        <CloseIcon sx={{fontSize: 16}} />
                    </IconButton>
                </Box>

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
                                bgcolor: msg.role === 'duck' ? 'action.hover' : 'primary.main',
                                color: msg.role === 'user' ? 'primary.contrastText' : 'text.primary',
                            }}
                        >
                            <Typography sx={{fontSize: 12, lineHeight: 1.5, whiteSpace: 'pre-wrap'}}>
                                {msg.content}
                            </Typography>
                        </Box>
                    ))}
                    <div ref={messagesEndRef} />
                </Box>

                {/* Suggestions */}
                <Box sx={{px: 1.5, pb: 0.5, display: 'flex', gap: 0.5, flexWrap: 'wrap', flexShrink: 0}}>
                    {SUGGESTIONS.map((s) => (
                        <Chip
                            key={s}
                            label={s}
                            size="small"
                            variant="outlined"
                            onClick={() => sendMessage(s)}
                            sx={{height: 22, fontSize: 10, cursor: 'pointer', '& .MuiChip-label': {px: 1}}}
                        />
                    ))}
                </Box>

                {/* Input */}
                <Box sx={{display: 'flex', gap: 0.5, p: 1, borderTop: 1, borderColor: 'divider', flexShrink: 0}}>
                    <TextField
                        size="small"
                        fullWidth
                        placeholder="Ask the duck..."
                        value={input}
                        onChange={(e) => setInput(e.target.value)}
                        onKeyDown={(e) => {
                            if (e.key === 'Enter' && !e.shiftKey) {
                                e.preventDefault();
                                sendMessage(input);
                            }
                        }}
                        slotProps={{input: {sx: {fontSize: 12, py: 0.75, borderRadius: 4}}}}
                    />
                    <IconButton
                        size="small"
                        onClick={() => sendMessage(input)}
                        disabled={!input.trim()}
                        sx={{color: 'primary.main'}}
                    >
                        <SendIcon sx={{fontSize: 18}} />
                    </IconButton>
                </Box>
            </Paper>
        </Portal>
    );
};
