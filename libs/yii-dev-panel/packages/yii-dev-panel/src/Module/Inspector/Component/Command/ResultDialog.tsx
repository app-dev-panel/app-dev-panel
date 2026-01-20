import {Refresh} from '@mui/icons-material';
import Box from '@mui/material/Box';
import Button from '@mui/material/Button';
import Dialog, {DialogProps} from '@mui/material/Dialog';
import DialogActions from '@mui/material/DialogActions';
import DialogContent from '@mui/material/DialogContent';
import DialogTitle from '@mui/material/DialogTitle';
import {CodeHighlight} from '@yiisoft/yii-dev-panel-sdk/Component/CodeHighlight';

type ResultDialog = {
    status: 'ok' | 'error' | 'fail' | 'loading';
    content: any;
    onRerun: () => void;
    onClose: () => void;
} & DialogProps;
// TODO: add circle loading
export const ResultDialog = ({open, status, content, onRerun, onClose, ...rest}: ResultDialog) => {
    return (
        <Dialog fullWidth open={open} onClose={onClose} {...rest}>
            <DialogTitle>Result "{status}"</DialogTitle>
            <DialogContent>
                <Box
                    noValidate
                    component="form"
                    sx={{
                        display: 'flex',
                        flexDirection: 'row',
                        justifyContent: 'space-between',
                        m: 'auto',
                    }}
                >
                    <CodeHighlight
                        showLineNumbers={false}
                        language={'text/plain'}
                        code={typeof content === 'string' ? content : JSON.stringify(content, null, 2)}
                    />
                </Box>
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
