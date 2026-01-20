import {ModuleInterface} from '@yiisoft/yii-dev-panel-sdk/Types/Module.types';
import {Layout} from '@yiisoft/yii-dev-panel/Application/Component/Layout';
import {NotFoundPage} from '@yiisoft/yii-dev-panel/Application/Pages/NotFoundPage';
import {createBrowserRouter, createHashRouter, RouteObject} from 'react-router-dom';
import type {FutureConfig as RouterFutureConfig} from '@remix-run/router/dist/router';
import type {HydrationState} from '@remix-run/router';

// TODO: move DebugToolbar somewhere else
export function createRouter(
    modules: ModuleInterface[],
    routerConfig: {
        basename: string;
        useHashRouter: boolean;
    },
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
