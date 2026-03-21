import {middlewares, reducers} from '@app-dev-panel/panel/Module/DevServer/api';
import {routes} from '@app-dev-panel/panel/Module/DevServer/router';
import {ModuleInterface} from '@app-dev-panel/sdk/Types/Module.types';

export const DevServerModule: ModuleInterface = {
    routes: routes,
    reducers: reducers,
    middlewares: middlewares,
    standaloneModule: false,
};
