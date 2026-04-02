import {framesSlice} from '@app-dev-panel/panel/Module/Frames/Context/Context';
import storage from '@app-dev-panel/sdk/API/storage';
import {persistReducer} from 'redux-persist';

const framesSliceConfig = {key: framesSlice.name, version: 1, storage};

export const reducers = {[framesSlice.name]: persistReducer(framesSliceConfig, framesSlice.reducer)};

export const middlewares = [];
