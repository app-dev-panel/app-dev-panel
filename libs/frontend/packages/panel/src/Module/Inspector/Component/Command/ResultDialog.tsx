import {CodeHighlight} from '@app-dev-panel/sdk/Component/CodeHighlight';
import {CheckCircleOutline, Close, ErrorOutline, Refresh} from '@mui/icons-material';
import {Alert, AlertTitle, Box, Chip, CircularProgress, IconButton, Typography} from '@mui/material';
import Button from '@mui/material/Button';
import Dialog, {DialogProps} from '@mui/material/Dialog';
import DialogActions from '@mui/material/DialogActions';
import DialogContent from '@mui/material/DialogContent';
import DialogTitle from '@mui/material/DialogTitle';

type ResultDialogProps = {
    status: 'ok' | 'error' | 'fail' | 'loading';
    content: any;
    errors?: string[];
    commandName?: string;
    onRerun: () => void;
    onClose: () => void;
} & Omit<DialogProps, 'content'>;

const statusConfig = {
    ok: {label: 'Success', color: 'success' as const, icon: <CheckCircleOutline sx={{fontSize: 16}} />},
    error: {label: 'Error', color: 'error' as const, icon: <ErrorOutline sx={{fontSize: 16}} />},
    fail: {label: 'Failed', color: 'error' as const, icon: <ErrorOutline sx={{fontSize: 16}} />},
    loading: {label: 'Running', color: 'info' as const, icon: undefined},
};

const hasContent = (content: any): boolean => {
    if (content === null || content === undefined) return false;
    if (typeof content === 'string' && content.trim() === '') return false;
    if (typeof content === 'string' && content === 'null') return false;
    return true;
};

const formatContent = (content: any): string => {
    if (typeof content === 'string') return content;
    return JSON.stringify(content, null, 2);
};

export const ResultDialog = ({
    open,
    status,
    content,
    errors,
    commandName,
    onRerun,
    onClose,
    ...rest
}: ResultDialogProps) => {
    const isError = status === 'error' || status === 'fail';
    const isLoading = status === 'loading';
    const config = statusConfig[status];

    return (
        <Dialog fullWidth open={open} onClose={onClose} {...rest}>
            <DialogTitle sx={{display: 'flex', alignItems: 'center', justifyContent: 'space-between', gap: 1.5, pb: 1}}>
                <Box sx={{display: 'flex', alignItems: 'center', gap: 1.5, minWidth: 0}}>
                    <Typography variant="h6" component="span" sx={{fontWeight: 600}}>
                        {commandName ?? 'Command Result'}
                    </Typography>
                    {isLoading ? (
                        <CircularProgress size={20} />
                    ) : (
                        <Chip
                            label={config.label}
                            color={config.color}
                            size="small"
                            icon={config.icon}
                            variant="outlined"
                        />
                    )}
                </Box>
                <IconButton aria-label="close" onClick={onClose} size="small" sx={{color: 'text.secondary'}}>
                    <Close fontSize="small" />
                </IconButton>
            </DialogTitle>
            <DialogContent dividers>
                {!isLoading && isError && errors && errors.length > 0 && (
                    <Alert severity="error" sx={{mb: 2}}>
                        <AlertTitle>Errors</AlertTitle>
                        <Box component="ul" sx={{m: 0, pl: 2}}>
                            {errors.map((error, index) => (
                                <li key={index}>{error}</li>
                            ))}
                        </Box>
                    </Alert>
                )}
                {isLoading && (
                    <Box sx={{display: 'flex', justifyContent: 'center', py: 4}}>
                        <CircularProgress />
                    </Box>
                )}
                {!isLoading && hasContent(content) && (
                    <Box sx={{borderRadius: 1, overflow: 'auto', '& pre': {m: '0 !important', borderRadius: 1}}}>
                        <CodeHighlight showLineNumbers={false} language={'text/plain'} code={formatContent(content)} />
                    </Box>
                )}
                {!isLoading && !hasContent(content) && !isError && (
                    <Box sx={{display: 'flex', alignItems: 'center', justifyContent: 'center', py: 4}}>
                        <Typography variant="body2" color="text.secondary">
                            No output
                        </Typography>
                    </Box>
                )}
                {!isLoading && !hasContent(content) && isError && !(errors && errors.length > 0) && (
                    <Box sx={{display: 'flex', alignItems: 'center', justifyContent: 'center', py: 4}}>
                        <Typography variant="body2" color="text.secondary">
                            No output
                        </Typography>
                    </Box>
                )}
            </DialogContent>
            <DialogActions sx={{px: 3, py: 1.5}}>
                <Button variant="text" color="inherit" onClick={onClose} sx={{color: 'text.secondary'}}>
                    Close
                </Button>
                <Button variant="outlined" color="primary" onClick={onRerun} startIcon={<Refresh />}>
                    Rerun
                </Button>
            </DialogActions>
        </Dialog>
    );
};
