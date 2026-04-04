import {Layout} from '@app-dev-panel/panel/Application/Component/Layout';
import {NotFoundPage} from '@app-dev-panel/panel/Application/Pages/NotFoundPage';
import {ModuleInterface} from '@app-dev-panel/sdk/Types/Module.types';
import {createBrowserRouter, createHashRouter, RouteObject} from 'react-router';

// TODO: move DebugToolbar somewhere else
export function createRouter(
    modules: ModuleInterface[],
    routerConfig: {basename: string; useHashRouter: boolean},
    _modulesConfig: {toolbar: boolean},
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
    return routerConfig.useHashRouter
        ? createHashRouter(routes)
        : createBrowserRouter(routes, {basename: routerConfig.basename});
}
