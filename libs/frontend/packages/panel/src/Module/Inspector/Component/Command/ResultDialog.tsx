import {CodeHighlight} from '@app-dev-panel/sdk/Component/CodeHighlight';
import {Refresh} from '@mui/icons-material';
import {Alert, AlertTitle, CircularProgress} from '@mui/material';
import Box from '@mui/material/Box';
import Button from '@mui/material/Button';
import Dialog, {DialogProps} from '@mui/material/Dialog';
import DialogActions from '@mui/material/DialogActions';
import DialogContent from '@mui/material/DialogContent';
import DialogTitle from '@mui/material/DialogTitle';

type ResultDialogProps = {
    status: 'ok' | 'error' | 'fail' | 'loading';
    content: any;
    errors?: string[];
    onRerun: () => void;
    onClose: () => void;
} & Omit<DialogProps, 'content'>;

export const ResultDialog = ({open, status, content, errors, onRerun, onClose, ...rest}: ResultDialogProps) => {
    const isError = status === 'error' || status === 'fail';
    const isLoading = status === 'loading';

    return (
        <Dialog fullWidth open={open} onClose={onClose} {...rest}>
            <DialogTitle color={isError ? 'error' : isLoading ? 'text.secondary' : 'success.main'}>
                Result: {status}
            </DialogTitle>
            <DialogContent>
                {isLoading && (
                    <Box sx={{display: 'flex', justifyContent: 'center', py: 4}}>
                        <CircularProgress />
                    </Box>
                )}
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
                {!isLoading && (
                    <Box sx={{display: 'flex', flexDirection: 'row', justifyContent: 'space-between', m: 'auto'}}>
                        <CodeHighlight
                            showLineNumbers={false}
                            language={'text/plain'}
                            code={typeof content === 'string' ? content : JSON.stringify(content, null, 2)}
                        />
                    </Box>
                )}
            </DialogContent>
            <DialogActions>
                <Button variant="outlined" color="primary" onClick={onRerun} startIcon={<Refresh />}>
                    Rerun
                </Button>
                <Button variant="contained" color="secondary" onClick={onClose}>
                    Close
                </Button>
            </DialogActions>
        </Dialog>
    );
};
