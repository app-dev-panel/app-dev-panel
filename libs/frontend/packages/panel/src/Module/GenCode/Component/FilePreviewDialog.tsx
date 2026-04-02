import {GenCodeFile} from '@app-dev-panel/panel/Module/GenCode/Types/FIle.types';
import {CodeHighlight} from '@app-dev-panel/sdk/Component/CodeHighlight';
import {Close} from '@mui/icons-material';
import {IconButton} from '@mui/material';
import Dialog from '@mui/material/Dialog';
import DialogContent from '@mui/material/DialogContent';
import DialogTitle from '@mui/material/DialogTitle';

export type FilePreviewDialogProps = {open: boolean; file: GenCodeFile; onClose: () => void};

export function FilePreviewDialog({onClose, file, open}: FilePreviewDialogProps) {
    return (
        <Dialog onClose={onClose} open={open} fullWidth maxWidth="md">
            <DialogTitle sx={{display: 'flex', alignItems: 'center', justifyContent: 'space-between', pb: 1}}>
                {file.relativePath}
                <IconButton size="small" onClick={onClose} aria-label="close" sx={{color: 'text.secondary'}}>
                    <Close fontSize="small" />
                </IconButton>
            </DialogTitle>
            <DialogContent dividers sx={{p: 0, '& pre': {m: '0 !important'}}}>
                <CodeHighlight language={file.type} code={file.content} />
            </DialogContent>
        </Dialog>
    );
}
