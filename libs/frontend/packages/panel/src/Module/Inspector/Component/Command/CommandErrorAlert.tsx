import {Close, Refresh} from '@mui/icons-material';
import {Alert, AlertTitle, Box, Button, IconButton} from '@mui/material';

type CommandErrorAlertProps = {title?: string; errors: string[]; onRetry?: () => void; onDismiss?: () => void};

export const CommandErrorAlert = ({title = 'Command failed', errors, onRetry, onDismiss}: CommandErrorAlertProps) => {
    return (
        <Box sx={{mt: 2}}>
            <Alert
                severity="error"
                action={
                    onDismiss ? (
                        <IconButton color="inherit" size="small" onClick={onDismiss} aria-label="Dismiss error">
                            <Close fontSize="small" />
                        </IconButton>
                    ) : undefined
                }
            >
                <AlertTitle>{title}</AlertTitle>
                {errors.length > 0 && (
                    <Box component="ul" sx={{m: 0, pl: 2}}>
                        {errors.map((error, index) => (
                            <li key={index}>{error}</li>
                        ))}
                    </Box>
                )}
                {onRetry && (
                    <Button
                        color="error"
                        variant="outlined"
                        size="small"
                        onClick={onRetry}
                        startIcon={<Refresh />}
                        sx={{mt: 1}}
                    >
                        Retry
                    </Button>
                )}
            </Alert>
        </Box>
    );
};
