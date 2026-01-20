import Dialog from '@mui/material/Dialog';
import DialogTitle from '@mui/material/DialogTitle';
import {CodeHighlight} from '@yiisoft/yii-dev-panel-sdk/Component/CodeHighlight';
import {GiiFile} from '@yiisoft/yii-dev-panel/Module/Gii/Types/FIle.types';

export type FileDiffDialogProps = {
    open: boolean;
    file: GiiFile;
    content: string;
    onClose: () => void;
};

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
