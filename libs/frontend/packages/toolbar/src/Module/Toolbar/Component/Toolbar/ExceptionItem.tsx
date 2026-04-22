import {DebugEntry} from '@app-dev-panel/sdk/API/Debug/Debug';
import {parseFilePath, parseFilename} from '@app-dev-panel/sdk/Helper/filePathParser';
import {useEditorUrl} from '@app-dev-panel/sdk/Helper/useEditorUrl';
import CodeIcon from '@mui/icons-material/Code';
import ErrorIcon from '@mui/icons-material/Error';
import FmdGoodIcon from '@mui/icons-material/FmdGood';
import NumbersIcon from '@mui/icons-material/Numbers';
import OpenInFullIcon from '@mui/icons-material/OpenInFull';
import {Box, Chip, Divider, ListItemIcon, ListItemText, Menu, MenuItem, Tooltip, Typography} from '@mui/material';
import React, {useState} from 'react';
import {ExceptionModal} from './ExceptionModal';

type ExceptionItemProps = {data: DebugEntry};

export const ExceptionItem = ({data}: ExceptionItemProps) => {
    const [anchorEl, setAnchorEl] = useState<null | HTMLElement>(null);
    const [modalOpen, setModalOpen] = useState(false);
    const open = Boolean(anchorEl);
    const getEditorUrl = useEditorUrl();

    if (!data.exception?.class) {
        return null;
    }

    const exception = data.exception;
    const shortClass = exception.class.split('\\').pop() || exception.class;
    const tooltip = exception.message ? `${exception.class}: ${exception.message}` : exception.class;
    const cleanFile = parseFilePath(exception.file);
    const lineNumber = +exception.line;
    const sourceEditorUrl = getEditorUrl(cleanFile, lineNumber);
    const sourceFallbackUrl = `/inspector/files?path=${encodeURIComponent(cleanFile)}#L${exception.line}`;
    const classExplorerUrl = `/inspector/files?class=${encodeURIComponent(parseFilePath(exception.class))}`;

    const handleOpen = (event: React.MouseEvent<HTMLElement>) => {
        event.stopPropagation();
        event.preventDefault();
        setAnchorEl(event.currentTarget);
    };
    const handleClose = () => setAnchorEl(null);
    const handleShowDetails = () => {
        handleClose();
        setModalOpen(true);
    };
    const stopDrag = (event: React.MouseEvent | React.PointerEvent) => {
        event.stopPropagation();
    };

    return (
        <>
            <Tooltip title={tooltip} arrow>
                <Chip
                    icon={<ErrorIcon sx={{fontSize: '16px !important'}} />}
                    label={shortClass}
                    size="small"
                    color="error"
                    variant="filled"
                    onClick={handleOpen}
                    onMouseDown={stopDrag}
                    onPointerDown={stopDrag}
                    sx={{height: 32, borderRadius: 1, fontSize: 12, cursor: 'pointer'}}
                />
            </Tooltip>
            <Menu
                anchorEl={anchorEl}
                open={open}
                onClose={handleClose}
                anchorOrigin={{vertical: 'top', horizontal: 'left'}}
                transformOrigin={{vertical: 'bottom', horizontal: 'left'}}
                slotProps={{
                    paper: {
                        onMouseDown: stopDrag,
                        onPointerDown: stopDrag,
                        onClick: (e: React.MouseEvent) => e.stopPropagation(),
                        sx: {maxWidth: 560, minWidth: 320},
                    },
                }}
            >
                <Box sx={{px: 2, py: 1.25}}>
                    <Typography
                        variant="caption"
                        sx={{display: 'block', color: 'text.disabled', fontWeight: 600, letterSpacing: 0.5}}
                    >
                        EXCEPTION
                    </Typography>
                    <Typography
                        sx={{
                            fontFamily: "'JetBrains Mono', monospace",
                            fontSize: 12,
                            fontWeight: 600,
                            color: 'error.main',
                            wordBreak: 'break-all',
                            mt: 0.5,
                        }}
                    >
                        {exception.class}
                    </Typography>
                    {exception.message && (
                        <Typography
                            sx={{
                                fontSize: 12,
                                color: 'text.primary',
                                mt: 0.75,
                                whiteSpace: 'pre-wrap',
                                wordBreak: 'break-word',
                            }}
                        >
                            {exception.message}
                        </Typography>
                    )}
                </Box>
                <Divider />
                <MenuItem
                    component="a"
                    href={sourceEditorUrl ?? sourceFallbackUrl}
                    target={sourceEditorUrl ? undefined : '_top'}
                    onClick={handleClose}
                >
                    <ListItemIcon>
                        <FmdGoodIcon fontSize="small" />
                    </ListItemIcon>
                    <ListItemText primary="Source" />
                    <Typography
                        variant="body2"
                        sx={{color: 'text.secondary', ml: 2, fontFamily: "'JetBrains Mono', monospace", fontSize: 11}}
                    >
                        {parseFilename(cleanFile)}:{exception.line}
                    </Typography>
                </MenuItem>
                {exception.code != null && String(exception.code) !== '' && String(exception.code) !== '0' && (
                    <MenuItem disableRipple sx={{cursor: 'default', '&:hover': {backgroundColor: 'transparent'}}}>
                        <ListItemIcon>
                            <NumbersIcon fontSize="small" />
                        </ListItemIcon>
                        <ListItemText primary="Code" />
                        <Typography variant="body2" color="text.secondary" ml={2}>
                            {exception.code}
                        </Typography>
                    </MenuItem>
                )}
                <MenuItem onClick={handleShowDetails}>
                    <ListItemIcon>
                        <OpenInFullIcon fontSize="small" />
                    </ListItemIcon>
                    <ListItemText primary="Show full details" />
                </MenuItem>
                <MenuItem component="a" href={classExplorerUrl} target="_top" onClick={handleClose}>
                    <ListItemIcon>
                        <CodeIcon fontSize="small" />
                    </ListItemIcon>
                    <ListItemText primary="Open in panel" />
                </MenuItem>
            </Menu>
            <ExceptionModal
                open={modalOpen}
                onClose={() => setModalOpen(false)}
                debugEntryId={data.id}
                summary={exception}
            />
        </>
    );
};
