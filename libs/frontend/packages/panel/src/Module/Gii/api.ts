import {giiApi} from '@app-dev-panel/panel/Module/Gii/API/Gii';

export const reducers = {[giiApi.reducerPath]: giiApi.reducer};
export const middlewares = [giiApi.middleware];
