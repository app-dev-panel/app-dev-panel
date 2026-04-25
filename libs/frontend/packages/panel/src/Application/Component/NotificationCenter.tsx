import {useSelector} from '@app-dev-panel/panel/store';
import {EmptyState} from '@app-dev-panel/sdk/Component/EmptyState';
import {
    clearAll,
    markAllAsRead,
    markAsRead,
    type Notification,
    removeById,
    selectNotifications,
    selectUnreadCount,
} from '@app-dev-panel/sdk/Component/Notifications';
import {Alert, AlertTitle, Box, Button, Divider, Icon, IconButton, Popover, Tooltip, Typography} from '@mui/material';
import {styled, useTheme} from '@mui/material/styles';
import React, {useCallback} from 'react';
import {useDispatch} from 'react-redux';

type NotificationCenterProps = {anchorEl: HTMLElement | null; open: boolean; onClose: () => void};

const NotificationItem = styled(Alert)(({theme}) => ({
    borderRadius: theme.shape.borderRadius,
    padding: theme.spacing(1, 1.5),
    '& .MuiAlert-message': {flex: 1, minWidth: 0, padding: 0},
    '& .MuiAlert-icon': {
        padding: 0,
        marginRight: theme.spacing(1),
        alignItems: 'flex-start',
        paddingTop: theme.spacing(0.5),
    },
    '& .MuiAlert-action': {padding: 0, marginRight: 0, alignItems: 'flex-start'},
}));

const formatTimestamp = (timestamp: number): string => {
    const now = Date.now();
    const diff = now - timestamp;
    const seconds = Math.floor(diff / 1000);
    const minutes = Math.floor(seconds / 60);
    const hours = Math.floor(minutes / 60);
    const days = Math.floor(hours / 24);

    if (seconds < 60) return 'just now';
    if (minutes < 60) return `${minutes}m ago`;
    if (hours < 24) return `${hours}h ago`;
    return `${days}d ago`;
};

const copyNotificationText = async (notification: Notification) => {
    const parts: string[] = [];
    if (notification.title) {
        parts.push(notification.title);
    }
    parts.push(notification.text);
    await navigator.clipboard.writeText(parts.join('\n'));
};

const NotificationEntry = React.memo(
    ({
        notification,
        onMarkAsRead,
        onRemove,
    }: {
        notification: Notification;
        onMarkAsRead: (id: string) => void;
        onRemove: (id: string) => void;
    }) => {
        const theme = useTheme();

        const handleCopy = useCallback(async () => {
            await copyNotificationText(notification);
        }, [notification]);

        const handleClick = useCallback(() => {
            if (!notification.read) {
                onMarkAsRead(notification.id);
            }
        }, [notification.id, notification.read, onMarkAsRead]);

        const handleRemove = useCallback(() => {
            onRemove(notification.id);
        }, [notification.id, onRemove]);

        return (
            <NotificationItem
                severity={notification.color}
                variant="outlined"
                onClick={handleClick}
                sx={{
                    cursor: notification.read ? 'default' : 'pointer',
                    opacity: notification.read ? 0.75 : 1,
                    backgroundColor: notification.read ? 'transparent' : theme.palette.action.hover,
                    transition: 'opacity 0.2s, background-color 0.2s',
                }}
                action={
                    <Box sx={{display: 'flex', gap: 0.25}}>
                        <Tooltip title="Copy text">
                            <IconButton
                                size="small"
                                onClick={(e) => {
                                    e.stopPropagation();
                                    handleCopy();
                                }}
                            >
                                <Icon sx={{fontSize: 16}}>content_copy</Icon>
                            </IconButton>
                        </Tooltip>
                        <Tooltip title="Remove">
                            <IconButton
                                size="small"
                                onClick={(e) => {
                                    e.stopPropagation();
                                    handleRemove();
                                }}
                            >
                                <Icon sx={{fontSize: 16}}>close</Icon>
                            </IconButton>
                        </Tooltip>
                    </Box>
                }
            >
                {notification.title && (
                    <AlertTitle sx={{fontSize: 13, fontWeight: 600, mb: 0.25}}>{notification.title}</AlertTitle>
                )}
                <Typography variant="body2" sx={{wordBreak: 'break-word'}}>
                    {notification.text}
                </Typography>
                <Typography variant="caption" color="text.secondary" sx={{mt: 0.5, display: 'block'}}>
                    {formatTimestamp(notification.timestamp)}
                </Typography>
            </NotificationItem>
        );
    },
);

export const NotificationCenter = React.memo(({anchorEl, open, onClose}: NotificationCenterProps) => {
    const notifications = useSelector(selectNotifications);
    const unreadCount = useSelector(selectUnreadCount);
    const dispatch = useDispatch();

    const handleMarkAllAsRead = useCallback(() => {
        dispatch(markAllAsRead());
    }, [dispatch]);

    const handleClearAll = useCallback(() => {
        dispatch(clearAll());
    }, [dispatch]);

    const handleMarkAsRead = useCallback(
        (id: string) => {
            dispatch(markAsRead(id));
        },
        [dispatch],
    );

    const handleRemove = useCallback(
        (id: string) => {
            dispatch(removeById(id));
        },
        [dispatch],
    );

    return (
        <Popover
            open={open}
            anchorEl={anchorEl}
            onClose={onClose}
            anchorOrigin={{vertical: 'bottom', horizontal: 'right'}}
            transformOrigin={{vertical: 'top', horizontal: 'right'}}
            slotProps={{
                paper: {
                    sx: {
                        width: 400,
                        maxHeight: 520,
                        display: 'flex',
                        flexDirection: 'column',
                        mt: 0.5,
                        borderRadius: 1.5,
                        border: 1,
                        borderColor: 'divider',
                        boxShadow: '0 8px 32px rgba(0,0,0,0.12), 0 2px 8px rgba(0,0,0,0.08)',
                        backdropFilter: 'blur(12px)',
                        backgroundColor: (t) =>
                            t.palette.mode === 'dark' ? 'rgba(30, 41, 59, 0.92)' : 'rgba(255, 255, 255, 0.92)',
                    },
                },
            }}
        >
            <Box
                sx={{
                    display: 'flex',
                    alignItems: 'center',
                    justifyContent: 'space-between',
                    px: 2,
                    py: 1.5,
                    flexShrink: 0,
                }}
            >
                <Typography variant="body1" sx={{fontWeight: 600}}>
                    Notifications{unreadCount > 0 ? ` (${unreadCount})` : ''}
                </Typography>
                <Box sx={{display: 'flex', gap: 0.5}}>
                    {unreadCount > 0 && (
                        <Button size="small" onClick={handleMarkAllAsRead} sx={{textTransform: 'none', fontSize: 12}}>
                            Mark all read
                        </Button>
                    )}
                    {notifications.length > 0 && (
                        <Button
                            size="small"
                            onClick={handleClearAll}
                            color="inherit"
                            sx={{textTransform: 'none', fontSize: 12}}
                        >
                            Clear all
                        </Button>
                    )}
                </Box>
            </Box>
            <Divider />
            <Box sx={{overflowY: 'auto', flex: 1}}>
                {notifications.length === 0 ? (
                    <EmptyState
                        icon="notifications_none"
                        title="No notifications"
                        description="API errors and backend updates will appear here"
                    />
                ) : (
                    <Box sx={{display: 'flex', flexDirection: 'column', gap: 0.5, p: 1}}>
                        {notifications.map((notification) => (
                            <NotificationEntry
                                key={notification.id}
                                notification={notification}
                                onMarkAsRead={handleMarkAsRead}
                                onRemove={handleRemove}
                            />
                        ))}
                    </Box>
                )}
            </Box>
        </Popover>
    );
});
