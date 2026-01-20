import * as Pages from '@yiisoft/yii-dev-panel/Module/Inspector/Pages';
import {RouteObject} from 'react-router-dom';

export const routes = [
    {
        path: 'inspector',
        children: [
            {
                path: 'tests',
                element: <Pages.TestsPage />,
            },
            {
                path: 'analyse',
                element: <Pages.AnalysePage />,
            },
            {
                path: 'routes',
                element: <Pages.RoutesPage />,
            },
            {
                path: 'events',
                element: <Pages.EventsPage />,
            },
            {
                path: 'files',
                element: <Pages.FileExplorerPage />,
            },
            {
                path: 'translations',
                element: <Pages.TranslationsPage />,
            },
            {
                path: 'commands',
                element: <Pages.CommandsPage />,
            },
            {
                path: 'database',
                children: [
                    {
                        index: true,
                        element: <Pages.DatabasePage />,
                    },
                    {
                        path: ':table',
                        element: <Pages.TablePage />,
                    },
                ],
            },
            {
                path: 'phpinfo',
                element: <Pages.PhpInfoPage />,
            },
            {
                path: 'composer',
                element: <Pages.ComposerPage />,
            },
            {
                path: 'opcache',
                element: <Pages.OpcachePage />,
            },
            {
                path: 'container',
                children: [
                    {
                        path: 'view',
                        element: <Pages.ContainerEntryPage />,
                    },
                ],
            },
            {
                path: 'git',
                children: [
                    {
                        index: true,
                        element: <Pages.GitPages.GitPage />,
                    },
                    {
                        path: 'log',
                        element: <Pages.GitPages.GitLogPage />,
                    },
                ],
            },
            {
                path: 'cache',
                element: <Pages.CachePage />,
            },
            {
                path: 'config',
                element: <Pages.ConfigurationPage />,
            },
            {
                path: 'config/:page',
                element: <Pages.ConfigurationPage />,
            },
        ],
    },
] satisfies RouteObject[];
