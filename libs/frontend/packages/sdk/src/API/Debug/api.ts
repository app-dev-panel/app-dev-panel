import {debugSlice} from '@app-dev-panel/sdk/API/Debug/Context';
import {debugApi} from '@app-dev-panel/sdk/API/Debug/Debug';
import {liveSlice} from '@app-dev-panel/sdk/API/Debug/LiveContext';
import storage from '@app-dev-panel/sdk/API/storage';
import {persistReducer} from 'redux-persist';

const debugSliceConfig = {key: debugSlice.name, version: 1, whitelist: ['entry'], storage};

export const reducers = {
    [debugSlice.name]: persistReducer(debugSliceConfig, debugSlice.reducer),
    [debugApi.reducerPath]: debugApi.reducer,
    [liveSlice.name]: liveSlice.reducer,
};
export const middlewares = [debugApi.middleware];
