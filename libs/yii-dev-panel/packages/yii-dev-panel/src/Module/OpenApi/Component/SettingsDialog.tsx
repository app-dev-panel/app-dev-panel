import {Remove} from '@mui/icons-material';
import CheckIcon from '@mui/icons-material/Check';
import {
    FormHelperText,
    IconButton,
    InputBase,
    List,
    ListItem,
    ListItemButton,
    ListItemSecondaryAction,
    ListItemText,
} from '@mui/material';
import Box from '@mui/material/Box';
import Button from '@mui/material/Button';
import Dialog from '@mui/material/Dialog';
import DialogActions from '@mui/material/DialogActions';
import DialogContent from '@mui/material/DialogContent';
import DialogContentText from '@mui/material/DialogContentText';
import DialogTitle from '@mui/material/DialogTitle';
import {addApiEntry, deleteApiEntry, useOpenApiEntries} from '@yiisoft/yii-dev-panel/Module/OpenApi/Context/Context';
import * as React from 'react';
import {useDispatch} from 'react-redux';
import {useSelector} from '@yiisoft/yii-dev-panel/store';

// TODO: split saving and cancelling
type SettingsDialogProps = {
    onClose: () => void;
};
export const SettingsDialog = (props: SettingsDialogProps) => {
    const baseUrl = useSelector((state) => state.application.baseUrl) as string;
    const [selectedEntry, setSelectedEntry] = React.useState(baseUrl + '/docs/openapi.json');
    const dispatch = useDispatch();

    const apiEntries = useOpenApiEntries();

    // const handleSave = () => {
    //     console.log('save');
    //     props.onClose();
    // };

    const handleClose = () => {
        props.onClose();
    };

    const onAddHandler = () => {
        dispatch(addApiEntry(selectedEntry));
    };

    const onDeleteHandler = (name: string) => {
        return () => dispatch(deleteApiEntry(name));
    };

    return (
        <Dialog fullWidth={true} open={true} onClose={handleClose}>
            <DialogTitle>Open API entries</DialogTitle>
            <DialogContent>
                <DialogContentText>Create, edit or delete Open API entries.</DialogContentText>

                <List>
                    {Object.entries(apiEntries).map(([name, url], index) => (
                        <ListItem key={index}>
                            <ListItemButton
                                onClick={() => {
                                    setSelectedEntry(url);
                                }}
                            >
                                <ListItemText primary={url} secondary={name} />
                                <ListItemSecondaryAction>
                                    <IconButton onClick={onDeleteHandler(name)} sx={{p: 2}}>
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
                    sx={{
                        display: 'flex',
                        flexDirection: 'row',
                        p: [0.5, 1],
                        alignItems: 'center',
                    }}
                    onSubmit={(e) => {
                        e.preventDefault();
                        onAddHandler();
                    }}
                >
                    <InputBase
                        sx={{ml: 1, flex: 1}}
                        placeholder={'http://localhost/docs/openapi.json'}
                        value={selectedEntry}
                        onChange={(event) => setSelectedEntry(event.target.value)}
                    />
                    <IconButton onClick={onAddHandler} sx={{p: 2}}>
                        <CheckIcon />
                    </IconButton>
                </Box>
                <FormHelperText variant="outlined">
                    Please make sure you entered the full path to the Open API json schema. For example:
                    http://localhost/docs/openapi.json
                </FormHelperText>
            </DialogContent>
            <DialogActions>
                {/*<Button onClick={handleSave} color="success">*/}
                {/*    Save*/}
                {/*</Button>*/}
                <Button onClick={handleClose} color="primary">
                    Close
                </Button>
            </DialogActions>
        </Dialog>
    );
};
