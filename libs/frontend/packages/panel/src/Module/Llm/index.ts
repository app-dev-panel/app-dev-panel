import {middlewares, reducers} from '@app-dev-panel/panel/Module/Llm/api';
import {routes} from '@app-dev-panel/panel/Module/Llm/router';
import {ModuleInterface} from '@app-dev-panel/sdk/Types/Module.types';

export const LlmModule: ModuleInterface = {
    routes: routes,
    reducers: reducers,
    middlewares: middlewares,
    standaloneModule: false,
};
