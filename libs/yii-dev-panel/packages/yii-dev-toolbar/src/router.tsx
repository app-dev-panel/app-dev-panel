import type {HydrationState} from '@remix-run/router';
import type {FutureConfig as RouterFutureConfig} from '@remix-run/router/dist/router';
import {ModuleInterface} from '@yiisoft/yii-dev-panel-sdk/Types/Module.types';
import {createBrowserRouter, createHashRouter, RouteObject} from 'react-router-dom';

export function createRouter(modules: ModuleInterface[], routerConfig: {basename: string; useHashRouter: boolean}) {
    const standaloneModules = modules.filter((module) => module.standaloneModule);

    const routes: RouteObject[] = [
        ...([] satisfies RouteObject[]).concat(...standaloneModules.map((module) => module.routes)),
    ];
    const opts: DOMRouterOpts = {basename: routerConfig.basename};

    return routerConfig.useHashRouter ? createHashRouter(routes) : createBrowserRouter(routes, opts);
}

// from react-router
type DOMRouterOpts = {
    basename?: string;
    future?: Partial<Omit<RouterFutureConfig, 'v7_prependBasename'>>;
    hydrationData?: HydrationState;
    window?: Window;
};
