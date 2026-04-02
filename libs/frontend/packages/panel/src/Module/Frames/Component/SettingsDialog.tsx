import {addFrame, deleteFrame, useFramesEntries} from '@app-dev-panel/panel/Module/Frames/Context/Context';
import {Close, Remove} from '@mui/icons-material';
import CheckIcon from '@mui/icons-material/Check';
import {
    IconButton,
    List,
    ListItem,
    ListItemButton,
    ListItemSecondaryAction,
    ListItemText,
    OutlinedInput,
} from '@mui/material';
import Box from '@mui/material/Box';
import Button from '@mui/material/Button';
import Dialog from '@mui/material/Dialog';
import DialogActions from '@mui/material/DialogActions';
import DialogContent from '@mui/material/DialogContent';
import DialogTitle from '@mui/material/DialogTitle';
import * as React from 'react';
import {useDispatch} from 'react-redux';

type SettingsDialogProps = {onClose: () => void};
export const SettingsDialog = (props: SettingsDialogProps) => {
    const [selectedEntry, setSelectedEntry] = React.useState('');
    const dispatch = useDispatch();

    const frames = useFramesEntries();

    const handleClose = () => {
        props.onClose();
    };

    const onAddHandler = () => {
        dispatch(addFrame(selectedEntry));
    };

    const onDeleteHandler = (name: string) => {
        return () => dispatch(deleteFrame(name));
    };

    return (
        <Dialog fullWidth open={true} onClose={handleClose}>
            <DialogTitle sx={{display: 'flex', alignItems: 'center', justifyContent: 'space-between', pb: 1}}>
                Frames
                <IconButton size="small" onClick={handleClose} aria-label="close" sx={{color: 'text.secondary'}}>
                    <Close fontSize="small" />
                </IconButton>
            </DialogTitle>
            <DialogContent dividers>
                <List disablePadding>
                    {Object.entries(frames).map(([name, url]) => (
                        <ListItem key={name} disablePadding>
                            <ListItemButton
                                onClick={() => {
                                    setSelectedEntry(url);
                                }}
                            >
                                <ListItemText primary={url} secondary={name} />
                                <ListItemSecondaryAction>
                                    <IconButton onClick={onDeleteHandler(name)} sx={{p: 1}} aria-label="Delete frame">
                                        <Remove />
                                    </IconButton>
                                </ListItemSecondaryAction>
                            </ListItemButton>
                        </ListItem>
                    ))}
                </List>
                <Box
                    noValidate
                    component="form"
                    sx={{display: 'flex', flexDirection: 'row', mt: 2, alignItems: 'center', gap: 1}}
                    onSubmit={(e) => {
                        e.preventDefault();
                        onAddHandler();
                    }}
                >
                    <OutlinedInput
                        size="small"
                        fullWidth
                        placeholder="https://external-resource.com/"
                        value={selectedEntry}
                        onChange={(event) => setSelectedEntry(event.target.value)}
                    />
                    <IconButton onClick={onAddHandler} color="primary" aria-label="Add frame">
                        <CheckIcon />
                    </IconButton>
                </Box>
            </DialogContent>
            <DialogActions sx={{px: 3, py: 1.5}}>
                <Button variant="text" color="inherit" onClick={handleClose} sx={{color: 'text.secondary'}}>
                    Close
                </Button>
            </DialogActions>
        </Dialog>
    );
};
