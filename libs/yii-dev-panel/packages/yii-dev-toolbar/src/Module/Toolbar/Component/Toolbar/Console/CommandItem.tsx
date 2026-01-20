import {DataObject, Input, Repeat, Terminal} from '@mui/icons-material';
import {Button, ListItemIcon, ListItemText, Menu, MenuItem, Tooltip, Typography} from '@mui/material';
import {DebugEntry} from '@yiisoft/yii-dev-panel-sdk/API/Debug/Debug';
import {MuiColor} from '@yiisoft/yii-dev-panel-sdk/Adapter/mui/types';
import React, {useState} from 'react';

const buttonColor = (exitCode: number): MuiColor => {
    return exitCode === 0 ? 'success' : 'error';
};
type CommandItemProps = {
    data: DebugEntry;
};

export const CommandItem = ({data}: CommandItemProps) => {
    if (!data.command) {
        return null;
    }
    const [anchorEl, setAnchorEl] = useState<null | HTMLElement>(null);
    const open = Boolean(anchorEl);
    const handleClick = (event: React.MouseEvent<HTMLElement>) => setAnchorEl(event.currentTarget);
    const handleClose = () => setAnchorEl(null);

    return (
        <>
            <Tooltip title="Click to see more options" arrow>
                <Button
                    startIcon={<Terminal fontSize="small" />}
                    color={buttonColor(data.command.exitCode)}
                    variant="contained"
                    onClick={handleClick}
                    sx={{
                        whiteSpace: 'nowrap',
                        textTransform: 'none',
                        borderRadius: 0,
                    }}
                >
                    {data.command.name}
                </Button>
            </Tooltip>
            <Menu
                anchorEl={anchorEl}
                open={open}
                onClose={handleClose}
                anchorOrigin={{vertical: 'top', horizontal: 'left'}}
                transformOrigin={{vertical: 'bottom', horizontal: 'left'}}
            >
                <MenuItem onClick={handleClose}>
                    <ListItemIcon>
                        <Repeat fontSize="small" />
                    </ListItemIcon>
                    Repeat
                </MenuItem>
                {data.command.class && (
                    <MenuItem onClick={handleClose}>
                        <ListItemIcon>
                            <DataObject fontSize="small" />
                        </ListItemIcon>
                        <ListItemText>Class</ListItemText>
                        <Typography variant="body2" color="text.secondary" ml={2}>
                            {data.command.class}
                        </Typography>
                    </MenuItem>
                )}
                {data.command.input && (
                    <MenuItem onClick={handleClose}>
                        <ListItemIcon>
                            <Input fontSize="small" />
                        </ListItemIcon>
                        <ListItemText>Input</ListItemText>
                        <Typography variant="body2" color="text.secondary" ml={2}>
                            {data.command.input}
                        </Typography>
                    </MenuItem>
                )}
            </Menu>
        </>
    );
};
