import {routes} from '@app-dev-panel/panel/Application/router';
import {middlewares, reducers} from '@app-dev-panel/sdk/API/Application/api';
import {ModuleInterface} from '@app-dev-panel/sdk/Types/Module.types';

export const ApplicationModule: ModuleInterface = {
    routes: routes,
    reducers: reducers,
    middlewares: middlewares,
    standaloneModule: false,
};
