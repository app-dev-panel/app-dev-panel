import {middlewares, reducers} from '@app-dev-panel/panel/Module/GenCode/api';
import {routes} from '@app-dev-panel/panel/Module/GenCode/router';
import {ModuleInterface} from '@app-dev-panel/sdk/Types/Module.types';

export const GenCodeModule: ModuleInterface = {
    routes: routes,
    reducers: reducers,
    middlewares: middlewares,
    standaloneModule: false,
};
