import {openApiSlice} from '@app-dev-panel/panel/Module/OpenApi/Context/Context';
import {persistReducer} from 'redux-persist';
import storage from 'redux-persist/lib/storage';

const openApiSliceConfig = {key: openApiSlice.name, version: 1, storage};

export const reducers = {[openApiSlice.name]: persistReducer(openApiSliceConfig, openApiSlice.reducer)};

export const middlewares = [];
