import {DebugEntry} from '@app-dev-panel/sdk/API/Debug/Debug';
import {buttonColorHttp} from '@app-dev-panel/sdk/Helper/buttonColor';
import {serializeCallable} from '@app-dev-panel/sdk/Helper/callableSerializer';
import {panelPagePath} from '@app-dev-panel/sdk/Helper/panelMountPath';
import {usePostCurlBuildMutation} from '@app-dev-panel/toolbar/Module/Toolbar/API/inspector';
import {ContentCopy, DataObject, DynamicFeed, Repeat, Route} from '@mui/icons-material';
import {Chip, ListItemIcon, ListItemText, Menu, MenuItem, Tooltip, Typography} from '@mui/material';
import {NestedMenuItem} from 'mui-nested-menu';
import React, {useState} from 'react';

const extractActionClass = (action: unknown): string | null => {
    if (Array.isArray(action) && action.length >= 1 && typeof action[0] === 'string') {
        return action[0];
    }
    if (typeof action === 'string') {
        // `App\Foo::bar` → take class part before ::
        return action.split('::')[0] || action;
    }
    return null;
};

type RequestItemProps = {data: DebugEntry};
export const RequestItem = ({data}: RequestItemProps) => {
    const [anchorEl, setAnchorEl] = useState<null | HTMLElement>(null);
    const open = Boolean(anchorEl);
    const handleClick = (event: React.MouseEvent<HTMLElement>) => setAnchorEl(event.currentTarget);
    const handleClose = () => setAnchorEl(null);
    const [postCurlBuild] = usePostCurlBuildMutation();

    const color = buttonColorHttp(data.response.statusCode);

    const handleCopyCurl = async () => {
        handleClose();
        try {
            const result = await postCurlBuild(data.id).unwrap();
            if (result?.command) {
                await navigator.clipboard.writeText(result.command);
            }
        } catch (e) {
            console.error('[ADP Toolbar] Copy cURL failed:', e);
        }
    };

    const actionClass = extractActionClass(data.router?.action);
    const actionHref = actionClass ? panelPagePath(`/inspector/files?class=${encodeURIComponent(actionClass)}`) : null;

    return (
        <>
            <Tooltip title="Click for request details" arrow>
                <Chip
                    label={`${data.request.method} ${data.request.path} ${data.response.statusCode}`}
                    size="small"
                    color={color}
                    variant="filled"
                    onClick={handleClick}
                    sx={{
                        fontWeight: 600,
                        fontSize: 12,
                        fontFamily: "'JetBrains Mono', monospace",
                        height: 32,
                        borderRadius: 1,
                    }}
                />
            </Tooltip>
            <Menu
                anchorEl={anchorEl}
                open={open}
                onClose={handleClose}
                anchorOrigin={{vertical: 'top', horizontal: 'left'}}
                transformOrigin={{vertical: 'bottom', horizontal: 'left'}}
                slotProps={{paper: {sx: {minWidth: 280}}}}
            >
                <MenuItem onClick={handleClose}>
                    <ListItemIcon>
                        <Repeat fontSize="small" />
                    </ListItemIcon>
                    Repeat
                </MenuItem>
                <MenuItem onClick={handleCopyCurl}>
                    <ListItemIcon>
                        <ContentCopy fontSize="small" />
                    </ListItemIcon>
                    Copy cURL
                </MenuItem>
                {data.router?.middlewares && data.router.middlewares.length > 0 && (
                    <NestedMenuItem
                        onClick={handleClose}
                        sx={{padding: '6px 16px'}}
                        leftIcon={<DynamicFeed fontSize="small" sx={{color: 'text.secondary', mr: 1}} />}
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
                {data.router?.action &&
                    (actionHref ? (
                        <MenuItem component="a" href={actionHref} target="_top" onClick={handleClose}>
                            <ListItemIcon>
                                <DataObject fontSize="small" />
                            </ListItemIcon>
                            <ListItemText>Action</ListItemText>
                            <Typography variant="body2" color="text.secondary" ml={2} sx={{wordBreak: 'break-all'}}>
                                {serializeCallable(data.router.action)}
                            </Typography>
                        </MenuItem>
                    ) : (
                        <MenuItem onClick={handleClose}>
                            <ListItemIcon>
                                <DataObject fontSize="small" />
                            </ListItemIcon>
                            <ListItemText>Action</ListItemText>
                            <Typography variant="body2" color="text.secondary" ml={2} sx={{wordBreak: 'break-all'}}>
                                {serializeCallable(data.router.action)}
                            </Typography>
                        </MenuItem>
                    ))}
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
