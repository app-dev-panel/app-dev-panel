import {Config} from '@yiisoft/yii-dev-panel-sdk/Config';
import {ModuleInterface} from '@yiisoft/yii-dev-panel-sdk/Types/Module.types';
import {createBrowserRouter, createHashRouter, RouteObject} from 'react-router-dom';
import type {FutureConfig as RouterFutureConfig} from '@remix-run/router/dist/router';
import type {HydrationState} from '@remix-run/router';

export function createRouter(
    modules: ModuleInterface[],
    routerConfig: {
        basename: string;
        useHashRouter: boolean;
    },
) {
    const standaloneModules = modules.filter((module) => module.standaloneModule);

    const routes: RouteObject[] = [
        ...([] satisfies RouteObject[]).concat(...standaloneModules.map((module) => module.routes)),
    ];
    const opts: DOMRouterOpts = {
        basename: routerConfig.basename,
    };

    return routerConfig.useHashRouter ? createHashRouter(routes) : createBrowserRouter(routes, opts);
}

// from react-router
interface DOMRouterOpts {
    basename?: string;
    future?: Partial<Omit<RouterFutureConfig, 'v7_prependBasename'>>;
    hydrationData?: HydrationState;
    window?: Window;
}
