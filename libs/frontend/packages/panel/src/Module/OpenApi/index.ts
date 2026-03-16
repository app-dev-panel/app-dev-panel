import {middlewares, reducers} from '@app-dev-panel/panel/Module/OpenApi/api';
import {routes} from '@app-dev-panel/panel/Module/OpenApi/router';
import {ModuleInterface} from '@app-dev-panel/sdk/Types/Module.types';

export const OpenApiModule: ModuleInterface = {
    routes: routes,
    reducers: reducers,
    middlewares: middlewares,
    standaloneModule: false,
};
