import {useChatMutation, useGetStatusQuery} from '@app-dev-panel/panel/Module/Llm/API/Llm';
import {Markdown} from '@app-dev-panel/panel/Module/Llm/Component/Markdown';
import {SendButton} from '@app-dev-panel/panel/Module/Llm/Component/SendButton';
import {Alert, Box, CircularProgress, Paper, TextField, Typography} from '@mui/material';
import {useCallback, useRef, useState} from 'react';

type Message = {role: 'user' | 'assistant'; content: string};

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

export const ChatPanel = () => {
    const {data: status} = useGetStatusQuery();
    const [chat, {isLoading}] = useChatMutation();
    const [messages, setMessages] = useState<Message[]>([]);
    const [input, setInput] = useState('');
    const [error, setError] = useState<string | null>(null);
    const messagesEndRef = useRef<HTMLDivElement>(null);

    const handleSend = useCallback(async () => {
        if (!input.trim() || isLoading) return;

        const userMessage: Message = {role: 'user', content: input.trim()};
        const newMessages = [...messages, userMessage];
        setMessages(newMessages);
        setInput('');
        setError(null);

        try {
            const result = await chat({
                messages: newMessages.map((m) => ({role: m.role, content: m.content})),
            }).unwrap();

            const assistantContent = result.choices?.[0]?.message?.content ?? 'No response.';
            setMessages((prev) => [...prev, {role: 'assistant', content: assistantContent}]);
        } catch (err: unknown) {
            const message = extractErrorMessage(err) ?? 'Failed to get response from LLM.';
            setError(message);
        }

        setTimeout(() => messagesEndRef.current?.scrollIntoView({behavior: 'smooth'}), 100);
    }, [input, messages, chat, isLoading]);

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
                    <Box
                        key={i}
                        sx={{
                            alignSelf: msg.role === 'user' ? 'flex-end' : 'flex-start',
                            maxWidth: '80%',
                            p: 1.5,
                            borderRadius: 2,
                            bgcolor: msg.role === 'user' ? 'primary.main' : 'background.default',
                            color: msg.role === 'user' ? 'primary.contrastText' : 'text.primary',
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
            {error && (
                <Alert severity="error" onClose={() => setError(null)}>
                    {error}
                </Alert>
            )}
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
        </Box>
    );
};
