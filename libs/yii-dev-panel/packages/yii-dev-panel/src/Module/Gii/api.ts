import {giiApi} from '@yiisoft/yii-dev-panel/Module/Gii/API/Gii';

export const reducers = {
    [giiApi.reducerPath]: giiApi.reducer,
};
export const middlewares = [giiApi.middleware];
