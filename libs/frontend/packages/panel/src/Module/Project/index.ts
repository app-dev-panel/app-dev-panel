import {middlewares, reducers} from '@app-dev-panel/panel/Module/Project/api';
import {routes} from '@app-dev-panel/panel/Module/Project/router';
import {ModuleInterface} from '@app-dev-panel/sdk/Types/Module.types';

export const ProjectModule: ModuleInterface = {routes, reducers, middlewares, standaloneModule: false};
