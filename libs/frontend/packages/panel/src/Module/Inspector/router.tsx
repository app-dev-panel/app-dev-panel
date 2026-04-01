import * as Pages from '@app-dev-panel/panel/Module/Inspector/Pages';
import {Navigate, RouteObject} from 'react-router-dom';

export const routes = [
    {
        path: 'inspector',
        children: [
            {index: true, element: <Pages.DashboardPage />},
            {path: 'code-quality', element: <Pages.CodeQualityPage />},
            {path: 'storage', element: <Pages.StoragePage />},
            {path: 'storage/database', children: [{path: ':table', element: <Pages.TablePage />}]},
            {path: 'environment', element: <Pages.EnvironmentPage />},
            {path: 'routes', element: <Pages.RoutesPage />},
            {path: 'events', element: <Pages.EventsPage />},
            {path: 'files', element: <Pages.FileExplorerPage />},
            {path: 'translations', element: <Pages.TranslationsPage />},
            {path: 'commands', element: <Pages.CommandsPage />},
            {path: 'config', element: <Pages.ConfigurationPage />},
            {path: 'config/:page', element: <Pages.ConfigurationPage />},
            {path: 'authorization', element: <Pages.AuthorizationPage />},
            {path: 'container', children: [{path: 'view', element: <Pages.ContainerEntryPage />}]},
            // Redirects from old paths to new combined pages
            {path: 'tests', element: <Navigate to="/inspector/code-quality?tab=tests" replace />},
            {path: 'analyse', element: <Navigate to="/inspector/code-quality?tab=analyse" replace />},
            {path: 'coverage', element: <Navigate to="/inspector/code-quality?tab=coverage" replace />},
            {path: 'database', element: <Navigate to="/inspector/storage?tab=database" replace />},
            {path: 'database/:table', element: <Pages.TablePage />},
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
