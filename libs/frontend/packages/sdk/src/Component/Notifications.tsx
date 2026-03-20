import {AlertColor} from '@mui/material';
import {createSelector, createSlice, nanoid, PayloadAction} from '@reduxjs/toolkit';

export type Notification = {
    id: string;
    title?: string;
    text: string;
    color: AlertColor;
    read: boolean;
    timestamp: number;
    shown: boolean;
};

type NotificationsState = {notifications: Notification[]};

const initialState: NotificationsState = {notifications: []};

export const NotificationsSlice = createSlice({
    name: 'notifications',
    initialState,
    reducers: {
        addNotification: (state, action: PayloadAction<{title?: string; text: string; color: AlertColor}>) => {
            state.notifications.unshift({
                id: nanoid(),
                ...action.payload,
                read: false,
                timestamp: Date.now(),
                shown: true,
            });
        },
        removeNotification(state, action: PayloadAction<number>) {
            state.notifications[action.payload].shown = false;
        },
        dismissNotificationById(state, action: PayloadAction<string>) {
            const notification = state.notifications.find((n) => n.id === action.payload);
            if (notification) {
                notification.shown = false;
            }
        },
        markAsRead(state, action: PayloadAction<string>) {
            const notification = state.notifications.find((n) => n.id === action.payload);
            if (notification) {
                notification.read = true;
            }
        },
        markAllAsRead(state) {
            state.notifications.forEach((n) => {
                n.read = true;
            });
        },
        clearAll(state) {
            state.notifications = [];
        },
        removeById(state, action: PayloadAction<string>) {
            state.notifications = state.notifications.filter((n) => n.id !== action.payload);
        },
    },
});

export const {
    addNotification,
    removeNotification,
    dismissNotificationById,
    markAsRead,
    markAllAsRead,
    clearAll,
    removeById,
} = NotificationsSlice.actions;

const selectNotificationsState = (state: {notifications: NotificationsState}) => state.notifications;

export const selectNotifications = createSelector(selectNotificationsState, (s) => s.notifications);

export const selectUnreadCount = createSelector(
    selectNotificationsState,
    (s) => s.notifications.filter((n) => !n.read).length,
);
