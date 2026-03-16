import {middlewares, reducers} from '@app-dev-panel/panel/Module/Gii/api';
import {routes} from '@app-dev-panel/panel/Module/Gii/router';
import {ModuleInterface} from '@app-dev-panel/sdk/Types/Module.types';

export const GiiModule: ModuleInterface = {
    routes: routes,
    reducers: reducers,
    middlewares: middlewares,
    standaloneModule: false,
};
