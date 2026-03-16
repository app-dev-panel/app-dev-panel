import {GiiFile} from '@app-dev-panel/panel/Module/Gii/Types/FIle.types';
import {CodeHighlight} from '@app-dev-panel/sdk/Component/CodeHighlight';
import Dialog from '@mui/material/Dialog';
import DialogTitle from '@mui/material/DialogTitle';

export type FileDiffDialogProps = {open: boolean; file: GiiFile; content: string; onClose: () => void};

export function FileDiffDialog(props: FileDiffDialogProps) {
    const {onClose, file, content, open} = props;

    const handleClose = () => {
        onClose();
    };

    return (
        <Dialog onClose={handleClose} open={open} fullWidth maxWidth="md">
            <DialogTitle>{file.relativePath}</DialogTitle>
            <CodeHighlight language="diff" code={content} />
        </Dialog>
    );
}
