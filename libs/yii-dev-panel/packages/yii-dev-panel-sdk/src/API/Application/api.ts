import {ApplicationSlice} from '@yiisoft/yii-dev-panel-sdk/API/Application/ApplicationContext';
import {NotificationsSlice} from '@yiisoft/yii-dev-panel-sdk/Component/Notifications';
import {persistReducer} from 'redux-persist';
import storage from 'redux-persist/lib/storage';
import {createStateSyncMiddleware, withReduxStateSync} from 'redux-state-sync';

const applicationSliceConfig = {
    key: ApplicationSlice.name,
    version: 1,
    storage,
};
const notificationsSliceConfig = {
    key: NotificationsSlice.name,
    version: 1,
    storage,
};

export const reducers = {
    [ApplicationSlice.name]: persistReducer(applicationSliceConfig, withReduxStateSync(ApplicationSlice.reducer)),
    [NotificationsSlice.name]: persistReducer(notificationsSliceConfig, NotificationsSlice.reducer),
};
export const middlewares = [
    createStateSyncMiddleware({
        whitelist: [ApplicationSlice.actions.setToolbarOpen.type, ApplicationSlice.actions.changeBaseUrl.type],
    }),
];
