import {genCodeApi} from '@app-dev-panel/panel/Module/GenCode/API/GenCode';

export const reducers = {[genCodeApi.reducerPath]: genCodeApi.reducer};
export const middlewares = [genCodeApi.middleware];
