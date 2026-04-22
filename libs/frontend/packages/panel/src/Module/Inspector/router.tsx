import {InspectorPageLayout} from '@app-dev-panel/panel/Module/Inspector/Component/InspectorPageLayout';
import {Navigate, RouteObject} from 'react-router';

const lazy = (importer: () => Promise<{[key: string]: React.ComponentType}>, name: string) => ({
    lazy: async () => {
        const mod = await importer();
        return {Component: mod[name]};
    },
});

export const routes = [
    {
        path: 'inspector',
        element: <InspectorPageLayout />,
        children: [
            {
                index: true,
                ...lazy(() => import('@app-dev-panel/panel/Module/Inspector/Pages/DashboardPage'), 'DashboardPage'),
            },
            {
                path: 'code-quality',
                ...lazy(() => import('@app-dev-panel/panel/Module/Inspector/Pages/CodeQualityPage'), 'CodeQualityPage'),
            },
            {
                path: 'storage',
                ...lazy(() => import('@app-dev-panel/panel/Module/Inspector/Pages/StoragePage'), 'StoragePage'),
            },
            {
                path: 'storage/database',
                children: [
                    {
                        path: ':table',
                        ...lazy(() => import('@app-dev-panel/panel/Module/Inspector/Pages/TablePage'), 'TablePage'),
                    },
                ],
            },
            {
                path: 'environment',
                ...lazy(() => import('@app-dev-panel/panel/Module/Inspector/Pages/EnvironmentPage'), 'EnvironmentPage'),
            },
            {
                path: 'routes',
                ...lazy(() => import('@app-dev-panel/panel/Module/Inspector/Pages/RoutesPage'), 'RoutesPage'),
            },
            {
                path: 'events',
                ...lazy(() => import('@app-dev-panel/panel/Module/Inspector/Pages/EventsPage'), 'EventsPage'),
            },
            {
                path: 'files',
                ...lazy(
                    () => import('@app-dev-panel/panel/Module/Inspector/Pages/FileExplorerPage'),
                    'FileExplorerPage',
                ),
            },
            {
                path: 'translations',
                ...lazy(
                    () => import('@app-dev-panel/panel/Module/Inspector/Pages/TranslationsPage'),
                    'TranslationsPage',
                ),
            },
            {
                path: 'commands',
                ...lazy(() => import('@app-dev-panel/panel/Module/Inspector/Pages/CommandsPage'), 'CommandsPage'),
            },
            {
                path: 'config',
                ...lazy(
                    () => import('@app-dev-panel/panel/Module/Inspector/Pages/Config/ConfigurationPage'),
                    'ConfigurationPage',
                ),
            },
            {
                path: 'config/:page',
                ...lazy(
                    () => import('@app-dev-panel/panel/Module/Inspector/Pages/Config/ConfigurationPage'),
                    'ConfigurationPage',
                ),
            },
            {
                path: 'http-mock',
                ...lazy(() => import('@app-dev-panel/panel/Module/Inspector/Pages/HttpMockPage'), 'HttpMockPage'),
            },
            {
                path: 'authorization',
                ...lazy(
                    () => import('@app-dev-panel/panel/Module/Inspector/Pages/AuthorizationPage'),
                    'AuthorizationPage',
                ),
            },
            {
                path: 'container',
                children: [
                    {
                        path: 'view',
                        ...lazy(
                            () => import('@app-dev-panel/panel/Module/Inspector/Pages/ContainerEntryPage'),
                            'ContainerEntryPage',
                        ),
                    },
                ],
            },
            // Redirects from old paths to new combined pages
            {path: 'tests', element: <Navigate to="/inspector/code-quality?tab=tests" replace />},
            {path: 'analyse', element: <Navigate to="/inspector/code-quality?tab=analyse" replace />},
            {path: 'coverage', element: <Navigate to="/inspector/code-quality?tab=coverage" replace />},
            {path: 'database', element: <Navigate to="/inspector/storage?tab=database" replace />},
            {
                path: 'database/:table',
                ...lazy(() => import('@app-dev-panel/panel/Module/Inspector/Pages/TablePage'), 'TablePage'),
            },
            {path: 'cache', element: <Navigate to="/inspector/storage?tab=cache" replace />},
            {path: 'redis', element: <Navigate to="/inspector/storage?tab=redis" replace />},
            {path: 'elasticsearch', element: <Navigate to="/inspector/storage?tab=elasticsearch" replace />},
            {path: 'phpinfo', element: <Navigate to="/inspector/environment?tab=phpinfo" replace />},
            {path: 'composer', element: <Navigate to="/inspector/environment?tab=composer" replace />},
            {path: 'opcache', element: <Navigate to="/inspector/environment?tab=opcache" replace />},
            {path: 'git', element: <Navigate to="/inspector/environment?tab=git" replace />},
            {path: 'git/log', element: <Navigate to="/inspector/environment?tab=git-log" replace />},
        ],
    },
] satisfies RouteObject[];
