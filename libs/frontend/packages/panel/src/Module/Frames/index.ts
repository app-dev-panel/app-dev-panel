import {middlewares, reducers} from '@app-dev-panel/panel/Module/Frames/api';
import {routes} from '@app-dev-panel/panel/Module/Frames/router';
import {ModuleInterface} from '@app-dev-panel/sdk/Types/Module.types';

export const FramesModule: ModuleInterface = {
    routes: routes,
    reducers: reducers,
    middlewares: middlewares,
    standaloneModule: false,
};
