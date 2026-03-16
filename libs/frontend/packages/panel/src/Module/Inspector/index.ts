import {middlewares, reducers} from '@app-dev-panel/panel/Module/Inspector/api';
import {routes} from '@app-dev-panel/panel/Module/Inspector/router';
import {ModuleInterface} from '@app-dev-panel/sdk/Types/Module.types';

export const InspectorModule: ModuleInterface = {
    routes: routes,
    reducers: reducers,
    middlewares: middlewares,
    standaloneModule: false,
};
