import {framesSlice} from '@yiisoft/yii-dev-panel/Module/Frames/Context/Context';
import {persistReducer} from 'redux-persist';
import storage from 'redux-persist/lib/storage';

const framesSliceConfig = {
    key: framesSlice.name,
    version: 1,
    storage,
};

export const reducers = {
    [framesSlice.name]: persistReducer(framesSliceConfig, framesSlice.reducer),
};

export const middlewares = [];
