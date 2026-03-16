import {Close} from '@mui/icons-material';
import {Alert, AlertTitle, IconButton, Stack} from '@mui/material';
import {removeNotification} from '@yiisoft/yii-dev-panel-sdk/Component/Notifications';
import {useSelector} from '@yiisoft/yii-dev-panel/store';
import * as React from 'react';
import {useDispatch} from 'react-redux';

export const NotificationSnackbar = React.memo(() => {
    const notifications = useSelector((state) => state.notifications.notifications);
    const dispatch = useDispatch();
    const visibleNotifications = notifications.map((n, i) => ({...n, index: i})).filter((n) => n.shown);

    if (visibleNotifications.length === 0) {
        return null;
    }

    return (
        <Stack spacing={1} sx={{position: 'fixed', top: 70, right: 16, zIndex: 1400, maxWidth: 500, width: '100%'}}>
            {visibleNotifications.map((notification) => (
                <Alert
                    key={notification.text + notification.index}
                    severity={notification.color}
                    action={
                        <IconButton size="small" onClick={() => dispatch(removeNotification(notification.index))}>
                            <Close fontSize="small" />
                        </IconButton>
                    }
                >
                    {notification.title && notification.title.length > 0 && (
                        <AlertTitle>{notification.title}</AlertTitle>
                    )}
                    {notification.text}
                </Alert>
            ))}
        </Stack>
    );
});
