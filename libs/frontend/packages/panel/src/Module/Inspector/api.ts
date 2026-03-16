import {gitApi} from '@app-dev-panel/panel/Module/Inspector/API/GitApi';
import {inspectorApi} from '@app-dev-panel/panel/Module/Inspector/API/Inspector';

export const reducers = {[inspectorApi.reducerPath]: inspectorApi.reducer, [gitApi.reducerPath]: gitApi.reducer};
export const middlewares = [inspectorApi.middleware, gitApi.middleware];
