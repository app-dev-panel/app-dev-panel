import {ModuleInterface} from '@app-dev-panel/sdk/Types/Module.types';
import {createBrowserRouter, createHashRouter, RouteObject} from 'react-router';

export function createRouter(modules: ModuleInterface[], routerConfig: {basename: string; useHashRouter: boolean}) {
    const standaloneModules = modules.filter((module) => module.standaloneModule);

    const routes: RouteObject[] = [
        ...([] satisfies RouteObject[]).concat(...standaloneModules.map((module) => module.routes)),
    ];

    return routerConfig.useHashRouter
        ? createHashRouter(routes)
        : createBrowserRouter(routes, {basename: routerConfig.basename});
}
