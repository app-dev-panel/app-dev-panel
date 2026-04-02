import {debugSlice} from '@app-dev-panel/sdk/API/Debug/Context';
import {debugApi} from '@app-dev-panel/sdk/API/Debug/Debug';
import storage from '@app-dev-panel/sdk/API/storage';
import {persistReducer} from 'redux-persist';

const debugSliceConfig = {key: debugSlice.name, version: 1, whitelist: ['entry'], storage};

export const reducers = {
    [debugSlice.name]: persistReducer(debugSliceConfig, debugSlice.reducer),
    [debugApi.reducerPath]: debugApi.reducer,
};
export const middlewares = [debugApi.middleware];
