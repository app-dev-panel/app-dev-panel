import {ModuleInterface} from '@app-dev-panel/sdk/Types/Module.types';
import {middlewares, reducers} from '@app-dev-panel/toolbar/Module/Toolbar/api';
import {routes} from '@app-dev-panel/toolbar/Module/Toolbar/router';

export const ToolbarModule: ModuleInterface = {
    routes: routes,
    reducers: reducers,
    middlewares: middlewares,
    standaloneModule: true,
};
