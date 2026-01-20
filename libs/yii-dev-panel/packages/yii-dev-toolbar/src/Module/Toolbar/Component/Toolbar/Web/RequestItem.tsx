import {DataObject, DynamicFeed, Repeat, Route} from '@mui/icons-material';
import {Button, ListItemIcon, ListItemText, Menu, MenuItem, Tooltip, Typography} from '@mui/material';
import {DebugEntry} from '@yiisoft/yii-dev-panel-sdk/API/Debug/Debug';
import {buttonColorHttp} from '@yiisoft/yii-dev-panel-sdk/Helper/buttonColor';
import {serializeCallable} from '@yiisoft/yii-dev-panel-sdk/Helper/callableSerializer';
import {NestedMenuItem} from 'mui-nested-menu';
import React, {useState} from 'react';

type RequestItemProps = {
    data: DebugEntry;
};
export const RequestItem = ({data}: RequestItemProps) => {
    const [anchorEl, setAnchorEl] = useState<null | HTMLElement>(null);
    const open = Boolean(anchorEl);
    const handleClick = (event: React.MouseEvent<HTMLElement>) => setAnchorEl(event.currentTarget);
    const handleClose = () => setAnchorEl(null);

    return (
        <>
            <Tooltip title="Click to see more options" arrow>
                <Button
                    color={buttonColorHttp(data.response.statusCode)}
                    variant="contained"
                    onClick={handleClick}
                    sx={{
                        whiteSpace: 'nowrap',
                        textTransform: 'none',
                        borderRadius: 0,
                    }}
                >
                    {data.request.method}&nbsp;
                    {data.request.path}&nbsp;
                    {String(data.response.statusCode)}
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
                {data.router?.middlewares && (
                    <NestedMenuItem
                        onClick={handleClose}
                        sx={{
                            padding: '6px 16px',
                        }}
                        leftIcon={
                            <DynamicFeed
                                fontSize="small"
                                sx={{
                                    color: 'text.secondary',
                                    mr: 1,
                                }}
                            />
                        }
                        label="Middlewares"
                        parentMenuOpen={open}
                    >
                        {data.router.middlewares.map((middleware, index) => (
                            <MenuItem key={index} onClick={handleClose}>
                                <ListItemText color="text.secondary">
                                    {index + 1}. {serializeCallable(middleware)}
                                </ListItemText>
                            </MenuItem>
                        ))}
                    </NestedMenuItem>
                )}
                {data.router?.action && (
                    <MenuItem onClick={handleClose}>
                        <ListItemIcon>
                            <DataObject fontSize="small" />
                        </ListItemIcon>
                        <ListItemText>Action</ListItemText>
                        <Typography variant="body2" color="text.secondary" ml={2}>
                            {serializeCallable(data.router.action)}
                        </Typography>
                    </MenuItem>
                )}
                {data.router?.name && (
                    <MenuItem onClick={handleClose}>
                        <ListItemIcon>
                            <Route fontSize="small" />
                        </ListItemIcon>
                        <ListItemText>Route</ListItemText>
                        <Typography variant="body2" color="text.secondary" ml={2}>
                            {data.router.name}
                        </Typography>
                    </MenuItem>
                )}
            </Menu>
        </>
    );
};
