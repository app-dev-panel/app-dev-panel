import {Alert, AlertTitle, Snackbar} from '@mui/material';
import Slide from '@mui/material/Slide';
import {removeNotification} from '@yiisoft/yii-dev-panel-sdk/Component/Notifications';
import {useSelector} from '@yiisoft/yii-dev-panel/store';
import * as React from 'react';
import {useDispatch} from 'react-redux';

const TransitionUp = (props) => <Slide {...props} direction="up" />;

export const NotificationSnackbar = React.memo(() => {
    const notifications = useSelector((state) => state.notifications.notifications);
    const dispatch = useDispatch();

    const handleCloseSnackbar = (notificationIndex: number) => (event: React.SyntheticEvent, reason?: string) => {
        if (reason === 'clickaway') {
            return;
        }

        dispatch(removeNotification(notificationIndex));
    };

    return (
        <>
            {notifications.map((notification, index) => (
                <Snackbar
                    key={notification.text + index}
                    open={notification.shown}
                    onClose={handleCloseSnackbar(index)}
                    TransitionComponent={TransitionUp}
                    autoHideDuration={3000}
                    anchorOrigin={{
                        vertical: 'top',
                        horizontal: 'right',
                    }}
                    sx={{top: {xs: '85px', sm: '70px'}}}
                >
                    <Alert onClose={handleCloseSnackbar(index)} severity={notification.color} sx={{width: '100%'}}>
                        {notification.title && notification.title.length > 0 && (
                            <AlertTitle>{notification.title}</AlertTitle>
                        )}
                        {notification.text}
                    </Alert>
                </Snackbar>
            ))}
        </>
    );
});
