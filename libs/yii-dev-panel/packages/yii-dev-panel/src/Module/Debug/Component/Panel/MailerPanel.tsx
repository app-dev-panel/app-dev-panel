import HtmlIcon from '@mui/icons-material/Html';
import RawOnIcon from '@mui/icons-material/RawOn';
import {
    Alert,
    AlertTitle,
    Button,
    IconButton,
    List,
    ListItem,
    ListItemSecondaryAction,
    ListItemText,
} from '@mui/material';
import Avatar from '@mui/material/Avatar';
import Dialog from '@mui/material/Dialog';
import DialogActions from '@mui/material/DialogActions';
import DialogContent from '@mui/material/DialogContent';
import DialogContentText from '@mui/material/DialogContentText';
import DialogTitle from '@mui/material/DialogTitle';
import ListItemAvatar from '@mui/material/ListItemAvatar';
import Typography from '@mui/material/Typography';
import {nl2br} from '@yiisoft/yii-dev-panel-sdk/Helper/nl2br';
import React, {useCallback, useState} from 'react';
import Box from '@mui/material/Box';

type PreviewType = 'html' | 'raw';
type PreviewDialogProps = {
    onClose: () => void;
    open: boolean;
    previewType: PreviewType;
    message: MailMessageType;
};
const PreviewDialog = ({message, open, onClose, previewType}: PreviewDialogProps) => {
    if (!message) {
        return null;
    }
    console.log('message', message);
    return (
        <Dialog
            open={open}
            onClose={onClose}
            fullScreen
            aria-labelledby="alert-dialog-title"
            aria-describedby="alert-dialog-description"
        >
            <DialogTitle>{message.subject}</DialogTitle>
            <DialogContent>
                {previewType === 'html' ? (
                    <DialogContentText dangerouslySetInnerHTML={{__html: message.htmlBody}}></DialogContentText>
                ) : (
                    <DialogContentText>{nl2br(message.raw)}</DialogContentText>
                )}
            </DialogContent>
            <DialogActions>
                <Button onClick={onClose} autoFocus>
                    Close
                </Button>
            </DialogActions>
        </Dialog>
    );
};
type MailMessageType = {
    from: Record<string, string>;
    to: Record<string, string>;
    subject: string;
    date: string;
    textBody: string;
    htmlBody: string;
    raw: string;
    charset: string;
    replyTo: Record<string, string>;
    cc: Record<string, string>;
    bcc: Record<string, string>;
};
type MailerPanelProps = {
    data: {
        messages: MailMessageType[];
    };
};

function serializeSender(sender: Record<string, string>): string {
    return Object.entries(sender)
        .map(([key, value]) => `${key} <${value}>`)
        .join(', ');
}

export const MailerPanel = ({data}: MailerPanelProps) => {
    const [dialogOpen, setDialogOpen] = useState(false);
    const [previewType, selectPreviewType] = useState<PreviewType>('html');
    const [selectedMessage, setSelectedMessage] = useState<MailMessageType | null>(null);
    const handleDialogClose = useCallback(() => {
        setDialogOpen(false);
        setSelectedMessage(null);
    }, []);
    const selectMessage = (message: MailMessageType) => {
        setSelectedMessage(message);
        setDialogOpen(true);
    };
    if (!data || data.messages.length === 0) {
        return (
            <Box m={2}>
                <Alert severity="info">
                    <AlertTitle>No dumped mails found during the process</AlertTitle>
                </Alert>
            </Box>
        );
    }
    return (
        <>
            <List>
                {data.messages.map((entry, index) => (
                    <ListItem>
                        <ListItemAvatar>
                            <Avatar>{index + 1}</Avatar>
                        </ListItemAvatar>
                        <ListItemText
                            primary={entry.subject}
                            secondary={`From: ${serializeSender(entry.to)}, to: ${serializeSender(entry.to)}`}
                        />
                        <ListItemSecondaryAction>
                            <IconButton
                                edge="end"
                                onClick={() => {
                                    selectPreviewType('html');
                                    selectMessage(entry);
                                }}
                            >
                                <HtmlIcon />
                            </IconButton>
                            <IconButton
                                edge="end"
                                onClick={() => {
                                    selectPreviewType('raw');
                                    selectMessage(entry);
                                }}
                            >
                                <RawOnIcon />
                            </IconButton>
                            <Typography variant="body2" color="textSecondary">
                                {entry.date}
                            </Typography>
                        </ListItemSecondaryAction>
                    </ListItem>
                ))}
            </List>
            <PreviewDialog
                open={dialogOpen}
                onClose={handleDialogClose}
                message={selectedMessage}
                previewType={previewType}
            />
        </>
    );
};
