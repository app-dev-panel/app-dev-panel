import {type ChatBubble} from '@app-dev-panel/sdk/API/Llm/AiChatSlice';
import {Markdown} from '@app-dev-panel/sdk/Component/Markdown';
import {MessageCopyButton} from '@app-dev-panel/sdk/Component/MessageCopyButton';
import ReplayIcon from '@mui/icons-material/Replay';
import {Alert, Box, CircularProgress, IconButton, Tooltip, Typography} from '@mui/material';
import {memo, useMemo} from 'react';

type ChatMessageListProps = {
    messages: ChatBubble[];
    variant: 'compact' | 'full';
    onRetry?: (index: number) => void;
    emptyMessage?: string;
    scrollRef?: React.RefObject<HTMLDivElement | null>;
};

const loadingSx = {display: 'flex', alignItems: 'center', gap: 1} as const;
const errorCaptionSx = {mt: 0.5, display: 'block'} as const;
const replayIconSx = {fontSize: 16} as const;

export const ChatMessageList = memo(
    ({
        messages,
        variant,
        onRetry,
        emptyMessage = 'Ask questions about your application, debug data, or get development advice.',
        scrollRef,
    }: ChatMessageListProps) => {
        const isCompact = variant === 'compact';

        const styles = useMemo(
            () => ({
                userMsg: {alignSelf: 'flex-end' as const, maxWidth: isCompact ? '85%' : '80%'},
                assistantMsg: {
                    alignSelf: 'flex-start' as const,
                    maxWidth: isCompact ? '85%' : '80%',
                    position: 'relative' as const,
                },
                userBubble: {
                    py: isCompact ? 1 : 1.5,
                    px: 1.5,
                    borderRadius: 2,
                    borderBottomRightRadius: isCompact ? 1 : undefined,
                    bgcolor: 'primary.main',
                    color: 'primary.contrastText',
                    position: 'relative' as const,
                },
                assistantBubble: {
                    py: isCompact ? 1 : 1.5,
                    px: 1.5,
                    borderRadius: 2,
                    borderBottomLeftRadius: isCompact ? 1 : undefined,
                    bgcolor: isCompact ? 'action.hover' : 'background.default',
                    color: 'text.primary',
                    position: 'relative' as const,
                },
                assistantBubbleSending: {
                    py: isCompact ? 1 : 1.5,
                    px: 1.5,
                    borderRadius: 2,
                    borderBottomLeftRadius: isCompact ? 1 : undefined,
                    bgcolor: isCompact ? 'action.hover' : 'background.default',
                    color: 'text.primary',
                    position: 'relative' as const,
                    opacity: 0.7,
                },
                errorBubble: {
                    py: isCompact ? 1 : 1.5,
                    px: 1.5,
                    borderRadius: 2,
                    bgcolor: 'error.main',
                    color: 'common.white',
                    position: 'relative' as const,
                },
            }),
            [isCompact],
        );

        const textSx = isCompact
            ? {fontSize: 12, lineHeight: 1.5, whiteSpace: 'pre-wrap', pr: 3}
            : {whiteSpace: 'pre-wrap', pr: 3};

        return (
            <>
                {messages.length === 0 && (
                    <Typography
                        variant="body2"
                        color="text.secondary"
                        sx={{textAlign: 'center', mt: isCompact ? 2 : 4}}
                    >
                        {emptyMessage}
                    </Typography>
                )}
                {messages.map((msg, i) => {
                    const isUser = msg.role === 'user';
                    const isError = msg.status === 'error';
                    const isSending = msg.status === 'sending';
                    const displayContent = isError ? msg.error || msg.content : msg.content;

                    // Full variant: errors shown as MUI Alert
                    if (isError && !isCompact) {
                        return (
                            <Box key={msg.id} sx={isUser ? styles.userMsg : styles.assistantMsg}>
                                <Alert
                                    severity="error"
                                    action={
                                        onRetry && (
                                            <Tooltip title="Retry">
                                                <IconButton
                                                    size="small"
                                                    color="error"
                                                    onClick={() => onRetry(i)}
                                                    aria-label="Retry"
                                                >
                                                    <ReplayIcon sx={replayIconSx} />
                                                </IconButton>
                                            </Tooltip>
                                        )
                                    }
                                >
                                    {msg.error || msg.content}
                                </Alert>
                                {msg.error && msg.content && msg.error !== msg.content && (
                                    <Typography variant="caption" color="text.secondary" sx={errorCaptionSx}>
                                        {msg.error}
                                    </Typography>
                                )}
                            </Box>
                        );
                    }

                    const bubbleSx = isError
                        ? styles.errorBubble
                        : isUser
                          ? styles.userBubble
                          : isSending
                            ? styles.assistantBubbleSending
                            : styles.assistantBubble;

                    const isDarkBubble = isUser || isError;

                    return (
                        <Box key={msg.id} className="message-bubble" sx={isUser ? styles.userMsg : styles.assistantMsg}>
                            <Box sx={bubbleSx}>
                                {isSending ? (
                                    <Box sx={loadingSx}>
                                        <CircularProgress size={isCompact ? 12 : 16} />
                                        <Typography
                                            variant="body2"
                                            sx={{...(isCompact && {fontSize: 12}), color: 'text.secondary'}}
                                        >
                                            Thinking...
                                        </Typography>
                                    </Box>
                                ) : (
                                    <>
                                        {!isCompact && !isUser ? (
                                            <Markdown content={displayContent} />
                                        ) : (
                                            <Typography variant={isCompact ? undefined : 'body2'} sx={textSx}>
                                                {displayContent}
                                            </Typography>
                                        )}
                                        {isError && onRetry ? (
                                            <Tooltip title="Retry">
                                                <IconButton
                                                    size="small"
                                                    onClick={() => onRetry(i)}
                                                    aria-label="Retry"
                                                    sx={{
                                                        position: 'absolute',
                                                        top: 4,
                                                        right: 4,
                                                        width: 22,
                                                        height: 22,
                                                        color: 'common.white',
                                                    }}
                                                >
                                                    <ReplayIcon sx={{fontSize: 14}} />
                                                </IconButton>
                                            </Tooltip>
                                        ) : (
                                            <MessageCopyButton
                                                text={msg.content || msg.error || ''}
                                                variant={isDarkBubble ? 'dark' : 'light'}
                                            />
                                        )}
                                    </>
                                )}
                            </Box>
                        </Box>
                    );
                })}
                <div ref={scrollRef} />
            </>
        );
    },
);
