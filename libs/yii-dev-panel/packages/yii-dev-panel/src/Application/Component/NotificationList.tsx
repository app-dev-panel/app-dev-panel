import {Alert, AlertTitle, Button, List, ListItem, ListItemSecondaryAction} from '@mui/material';
import Slide from '@mui/material/Slide';
import {removeNotification} from '@yiisoft/yii-dev-panel-sdk/Component/Notifications';
import {useSelector} from '@yiisoft/yii-dev-panel/store';
import * as React from 'react';
import {useDispatch} from 'react-redux';

const TransitionUp = (props) => <Slide {...props} direction="up" />;

export const NotificationList = React.memo(() => {
    const notifications = useSelector((state) => state.notifications.notifications);
    const dispatch = useDispatch();

    const handleCloseSnackbar = (notificationIndex: number) => (event: React.SyntheticEvent, reason?: string) => {
        if (reason === 'clickaway') {
            return;
        }

        dispatch(removeNotification(notificationIndex));
    };

    return (
        <List>
            <ListItem>
                <ListItemSecondaryAction>
                    <Button>Clear</Button>
                </ListItemSecondaryAction>
            </ListItem>
            {notifications.slice(5).map((notification, index) => (
                <Alert key={notification.text + index} severity={notification.color} sx={{width: '100%'}}>
                    {notification.title && notification.title.length > 0 && (
                        <AlertTitle>{notification.title}</AlertTitle>
                    )}
                    {notification.text}
                </Alert>
            ))}
        </List>
    );
});
