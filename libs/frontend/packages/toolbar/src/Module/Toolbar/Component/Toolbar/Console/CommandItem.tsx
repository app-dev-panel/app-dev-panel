import {DebugEntry} from '@app-dev-panel/sdk/API/Debug/Debug';
import {MuiColor} from '@app-dev-panel/sdk/Adapter/mui/types';
import {panelPagePath} from '@app-dev-panel/sdk/Helper/panelMountPath';
import {DataObject, Input, Repeat, Terminal} from '@mui/icons-material';
import {Chip, ListItemIcon, ListItemText, Menu, MenuItem, Tooltip, Typography} from '@mui/material';
import React, {useState} from 'react';

const chipColor = (exitCode: number): Exclude<MuiColor, 'inherit'> => {
    return exitCode === 0 ? 'success' : 'error';
};

type CommandItemProps = {data: DebugEntry};

export const CommandItem = ({data}: CommandItemProps) => {
    if (!data.command) {
        return null;
    }
    const [anchorEl, setAnchorEl] = useState<null | HTMLElement>(null);
    const open = Boolean(anchorEl);
    const handleClick = (event: React.MouseEvent<HTMLElement>) => {
        if (event.ctrlKey || event.metaKey) {
            window.open(panelPagePath(`/?debugEntry=${data.id}`), '_blank', 'noopener,noreferrer');
            event.stopPropagation();
            event.preventDefault();
            return;
        }
        setAnchorEl(event.currentTarget);
    };
    const handleClose = () => setAnchorEl(null);

    return (
        <>
            <Tooltip title="Click for command details" arrow>
                <Chip
                    icon={<Terminal sx={{fontSize: '16px !important'}} />}
                    label={data.command.name}
                    size="small"
                    color={chipColor(data.command.exitCode)}
                    variant="filled"
                    onClick={handleClick}
                    sx={{fontWeight: 600, fontSize: 12, height: 32, borderRadius: 1}}
                />
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
