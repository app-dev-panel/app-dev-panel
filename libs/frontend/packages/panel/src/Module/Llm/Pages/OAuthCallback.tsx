import {useOauthExchangeMutation} from '@app-dev-panel/panel/Module/Llm/API/Llm';
import {Alert, Box, CircularProgress, Typography} from '@mui/material';
import {useEffect, useState} from 'react';

export const OAuthCallback = () => {
    const [exchange] = useOauthExchangeMutation();
    const [status, setStatus] = useState<'loading' | 'success' | 'error'>('loading');
    const [error, setError] = useState<string | null>(null);

    useEffect(() => {
        const params = new URLSearchParams(window.location.search);
        const code = params.get('code');
        const codeVerifier = localStorage.getItem('llm_code_verifier');

        if (!code) {
            setStatus('error');
            setError('No authorization code received.');
            return;
        }

        if (!codeVerifier) {
            setStatus('error');
            setError('Code verifier not found. Please retry the connection.');
            return;
        }

        exchange({code, codeVerifier})
            .unwrap()
            .then((result) => {
                localStorage.removeItem('llm_code_verifier');
                if (result.connected) {
                    setStatus('success');
                    setTimeout(() => window.close(), 2000);
                } else {
                    setStatus('error');
                    setError(result.error ?? 'Failed to exchange code.');
                }
            })
            .catch((err: unknown) => {
                localStorage.removeItem('llm_code_verifier');
                const apiError = err as {data?: {data?: {error?: string}}};
                const message = apiError?.data?.data?.error ?? 'Failed to exchange authorization code.';
                setStatus('error');
                setError(message);
            });
    }, [exchange]);

    return (
        <Box sx={{display: 'flex', justifyContent: 'center', alignItems: 'center', minHeight: '50vh'}}>
            {status === 'loading' && (
                <Box sx={{textAlign: 'center'}}>
                    <CircularProgress sx={{mb: 2}} />
                    <Typography>Completing connection...</Typography>
                </Box>
            )}
            {status === 'success' && (
                <Alert severity="success">Successfully connected! This window will close automatically.</Alert>
            )}
            {status === 'error' && <Alert severity="error">{error}</Alert>}
        </Box>
    );
};
