import ContentCopyIcon from '@mui/icons-material/ContentCopy';
import DoneIcon from '@mui/icons-material/Done';
import {IconButton, Tooltip} from '@mui/material';
import React, {memo, useCallback, useState} from 'react';

type MessageCopyButtonProps = {text: string; variant?: 'light' | 'dark'};

export const MessageCopyButton = memo(({text, variant = 'light'}: MessageCopyButtonProps) => {
    const [copied, setCopied] = useState(false);
    const handleCopy = useCallback(
        (e: React.MouseEvent) => {
            e.stopPropagation();
            navigator.clipboard.writeText(text).then(() => {
                setCopied(true);
                setTimeout(() => setCopied(false), 1500);
            });
        },
        [text],
    );

    const isDark = variant === 'dark';
    return (
        <Tooltip title={copied ? 'Copied!' : 'Copy'} placement="top">
            <IconButton
                size="small"
                onClick={handleCopy}
                aria-label="Copy message"
                sx={{
                    position: 'absolute',
                    top: 4,
                    right: 4,
                    width: 22,
                    height: 22,
                    opacity: 0,
                    transition: 'opacity 0.15s',
                    bgcolor: isDark ? 'rgba(255,255,255,0.15)' : 'rgba(0,0,0,0.06)',
                    color: isDark ? 'common.white' : 'text.secondary',
                    '.message-bubble:hover &': {opacity: 1},
                    '&:hover': {bgcolor: isDark ? 'rgba(255,255,255,0.25)' : 'rgba(0,0,0,0.12)'},
                }}
            >
                {copied ? <DoneIcon sx={{fontSize: 12}} /> : <ContentCopyIcon sx={{fontSize: 12}} />}
            </IconButton>
        </Tooltip>
    );
});
