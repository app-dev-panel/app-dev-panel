import {routes} from '@app-dev-panel/panel/Module/Debug/router';
import {middlewares, reducers} from '@app-dev-panel/sdk/API/Debug/api';
import {ModuleInterface} from '@app-dev-panel/sdk/Types/Module.types';

export const DebugModule: ModuleInterface = {
    routes: routes,
    reducers: reducers,
    middlewares: middlewares,
    standaloneModule: true,
};
