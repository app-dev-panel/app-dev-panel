import {debugSlice} from '@yiisoft/yii-dev-panel-sdk/API/Debug/Context';
import {debugApi} from '@yiisoft/yii-dev-panel-sdk/API/Debug/Debug';
import {persistReducer} from 'redux-persist';
import storage from 'redux-persist/lib/storage';

const debugSliceConfig = {
    key: debugSlice.name,
    version: 1,
    whitelist: ['entry'],
    storage,
};

export const reducers = {
    [debugSlice.name]: persistReducer(debugSliceConfig, debugSlice.reducer),
    [debugApi.reducerPath]: debugApi.reducer,
};
export const middlewares = [debugApi.middleware];
