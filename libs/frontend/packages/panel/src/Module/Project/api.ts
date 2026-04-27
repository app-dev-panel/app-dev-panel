import {projectSyncMiddleware} from '@app-dev-panel/panel/Module/Project/projectSyncMiddleware';
import {projectApi} from '@app-dev-panel/sdk/API/Project/Project';

export const reducers = {[projectApi.reducerPath]: projectApi.reducer};

export const middlewares = [projectApi.middleware, projectSyncMiddleware];
