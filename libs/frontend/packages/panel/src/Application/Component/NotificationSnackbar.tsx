import {useSelector} from '@app-dev-panel/panel/store';
import {dismissNotificationById, selectNotifications} from '@app-dev-panel/sdk/Component/Notifications';
import {Alert, AlertTitle, Snackbar} from '@mui/material';
import Slide from '@mui/material/Slide';
import * as React from 'react';
import {useDispatch} from 'react-redux';

const TransitionUp = (props: object) => <Slide {...props} direction="up" />;

export const NotificationSnackbar = React.memo(() => {
    const notifications = useSelector(selectNotifications);
    const dispatch = useDispatch();

    // Only show the most recent unread notification as a toast
    const latestUnread = notifications.find((n) => n.shown && !n.read);

    const handleClose = React.useCallback(
        (_event: React.SyntheticEvent | Event, reason?: string) => {
            if (reason === 'clickaway' || !latestUnread) {
                return;
            }
            dispatch(dismissNotificationById(latestUnread.id));
        },
        [dispatch, latestUnread],
    );

    if (!latestUnread) {
        return null;
    }

    return (
        <Snackbar
            key={latestUnread.id}
            open
            onClose={handleClose}
            TransitionComponent={TransitionUp}
            autoHideDuration={4000}
            anchorOrigin={{vertical: 'top', horizontal: 'right'}}
            sx={{top: {xs: '85px', sm: '70px'}}}
        >
            <Alert onClose={handleClose} severity={latestUnread.color} sx={{width: '100%', maxWidth: 360}}>
                {latestUnread.title && latestUnread.title.length > 0 && <AlertTitle>{latestUnread.title}</AlertTitle>}
                {latestUnread.text}
            </Alert>
        </Snackbar>
    );
});
