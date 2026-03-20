import {useSelector} from '@app-dev-panel/panel/store';
import {clearAll, removeById, selectNotifications} from '@app-dev-panel/sdk/Component/Notifications';
import {Alert, AlertTitle, Box, Button, List, ListItem, ListItemSecondaryAction} from '@mui/material';
import * as React from 'react';
import {useDispatch} from 'react-redux';

export const NotificationList = React.memo(() => {
    const notifications = useSelector(selectNotifications);
    const dispatch = useDispatch();

    const handleClear = React.useCallback(() => {
        dispatch(clearAll());
    }, [dispatch]);

    const handleRemove = React.useCallback(
        (id: string) => {
            dispatch(removeById(id));
        },
        [dispatch],
    );

    if (notifications.length === 0) {
        return null;
    }

    return (
        <List>
            <ListItem>
                <ListItemSecondaryAction>
                    <Button onClick={handleClear}>Clear</Button>
                </ListItemSecondaryAction>
            </ListItem>
            {notifications.map((notification) => (
                <Alert
                    key={notification.id}
                    severity={notification.color}
                    sx={{width: '100%', mb: 0.5}}
                    onClose={() => handleRemove(notification.id)}
                >
                    {notification.title && notification.title.length > 0 && (
                        <AlertTitle>{notification.title}</AlertTitle>
                    )}
                    <Box component="span" sx={{wordBreak: 'break-word'}}>
                        {notification.text}
                    </Box>
                </Alert>
            ))}
        </List>
    );
});
