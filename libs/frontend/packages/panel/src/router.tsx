import {Layout} from '@app-dev-panel/panel/Application/Component/Layout';
import {NotFoundPage} from '@app-dev-panel/panel/Application/Pages/NotFoundPage';
import {ModuleInterface} from '@app-dev-panel/sdk/Types/Module.types';
import type {HydrationState} from '@remix-run/router';
import type {FutureConfig as RouterFutureConfig} from '@remix-run/router/dist/router';
import {createBrowserRouter, createHashRouter, RouteObject} from 'react-router-dom';

// TODO: move DebugToolbar somewhere else
export function createRouter(
    modules: ModuleInterface[],
    routerConfig: {basename: string; useHashRouter: boolean},
    modulesConfig: {toolbar: boolean},
) {
    const standaloneModules = modules.filter((module) => module.standaloneModule);
    const others = modules.filter((module) => !module.standaloneModule);

    const routes: RouteObject[] = [
        {
            path: '/',
            element: <Layout />,
            children: ([] satisfies RouteObject[]).concat(...others.map((module) => module.routes)),
        },
        ...([] satisfies RouteObject[]).concat(...standaloneModules.map((module) => module.routes)),
        {
            path: '*',
            element: (
                <Layout>
                    <NotFoundPage />
                </Layout>
            ),
        },
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
