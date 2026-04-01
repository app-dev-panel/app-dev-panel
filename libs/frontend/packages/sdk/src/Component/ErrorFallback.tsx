import {ContentCopy} from '@mui/icons-material';
import {Accordion, AccordionDetails, Alert, AlertTitle, Button, IconButton, Tooltip} from '@mui/material';
import Box from '@mui/material/Box';
import clipboardCopy from 'clipboard-copy';
import {useCallback, useState} from 'react';
import {FallbackProps} from 'react-error-boundary';

export const ErrorFallback = ({error, resetErrorBoundary}: FallbackProps) => {
    const err = error instanceof Error ? error : new Error(String(error));
    const [copied, setCopied] = useState(false);
    const handleCopy = useCallback(() => {
        const text = [err.message, err.stack].filter(Boolean).join('\n\n');
        clipboardCopy(text).then(() => {
            setCopied(true);
            setTimeout(() => setCopied(false), 2000);
        });
    }, [err]);

    return (
        <Box mt={2}>
            <Alert
                severity="error"
                sx={{position: 'relative'}}
                action={
                    <Tooltip title={copied ? 'Copied!' : 'Copy error'}>
                        <IconButton color="error" size="small" onClick={handleCopy} aria-label="Copy error">
                            <ContentCopy fontSize="small" />
                        </IconButton>
                    </Tooltip>
                }
            >
                <AlertTitle>Something went wrong:</AlertTitle>
                <pre>{err.message}</pre>
                <Accordion>
                    <AccordionDetails>
                        <pre>{err.stack?.toString()}</pre>
                    </AccordionDetails>
                </Accordion>
                <Button color="error" variant="outlined" onClick={resetErrorBoundary}>
                    Try again
                </Button>
            </Alert>
        </Box>
    );
};
