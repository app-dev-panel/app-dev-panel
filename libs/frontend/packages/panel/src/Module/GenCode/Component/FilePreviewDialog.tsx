import {GenCodeFile} from '@app-dev-panel/panel/Module/GenCode/Types/FIle.types';
import {CodeHighlight} from '@app-dev-panel/sdk/Component/CodeHighlight';
import Dialog from '@mui/material/Dialog';
import DialogTitle from '@mui/material/DialogTitle';

export type FilePreviewDialogProps = {open: boolean; file: GenCodeFile; onClose: () => void};

export function FilePreviewDialog(props: FilePreviewDialogProps) {
    const {onClose, file, open} = props;

    const handleClose = () => {
        onClose();
    };

    return (
        <Dialog onClose={handleClose} open={open} fullWidth maxWidth="md">
            <DialogTitle>{file.relativePath}</DialogTitle>
            <CodeHighlight language={file.type} code={file.content} />
        </Dialog>
    );
}
