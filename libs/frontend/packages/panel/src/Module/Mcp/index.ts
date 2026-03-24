import {routes} from '@app-dev-panel/panel/Module/Mcp/router';
import {ModuleInterface} from '@app-dev-panel/sdk/Types/Module.types';

export const McpModule: ModuleInterface = {
    routes: routes,
    reducers: {},
    middlewares: [],
    standaloneModule: false,
};
