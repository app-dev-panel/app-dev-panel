import {FullScreenCircularProgress} from '@yiisoft/yii-dev-panel-sdk/Component/FullScreenCircularProgress';
import {lazy, Suspense} from 'react';
import {RouteObject} from 'react-router-dom';

const TestsPage = lazy(() =>
    import('@yiisoft/yii-dev-panel/Module/Inspector/Pages/TestsPage').then((m) => ({default: m.TestsPage})),
);
const AnalysePage = lazy(() =>
    import('@yiisoft/yii-dev-panel/Module/Inspector/Pages/AnalysePage').then((m) => ({default: m.AnalysePage})),
);
const RoutesPage = lazy(() =>
    import('@yiisoft/yii-dev-panel/Module/Inspector/Pages/RoutesPage').then((m) => ({default: m.RoutesPage})),
);
const EventsPage = lazy(() =>
    import('@yiisoft/yii-dev-panel/Module/Inspector/Pages/EventsPage').then((m) => ({default: m.EventsPage})),
);
const FileExplorerPage = lazy(() =>
    import('@yiisoft/yii-dev-panel/Module/Inspector/Pages/FileExplorerPage').then((m) => ({
        default: m.FileExplorerPage,
    })),
);
const TranslationsPage = lazy(() =>
    import('@yiisoft/yii-dev-panel/Module/Inspector/Pages/TranslationsPage').then((m) => ({
        default: m.TranslationsPage,
    })),
);
const CommandsPage = lazy(() =>
    import('@yiisoft/yii-dev-panel/Module/Inspector/Pages/CommandsPage').then((m) => ({default: m.CommandsPage})),
);
const DatabasePage = lazy(() =>
    import('@yiisoft/yii-dev-panel/Module/Inspector/Pages/DatabasePage').then((m) => ({default: m.DatabasePage})),
);
const TablePage = lazy(() =>
    import('@yiisoft/yii-dev-panel/Module/Inspector/Pages/TablePage').then((m) => ({default: m.TablePage})),
);
const PhpInfoPage = lazy(() =>
    import('@yiisoft/yii-dev-panel/Module/Inspector/Pages/PhpInfoPage').then((m) => ({default: m.PhpInfoPage})),
);
const ComposerPage = lazy(() =>
    import('@yiisoft/yii-dev-panel/Module/Inspector/Pages/ComposerPage').then((m) => ({default: m.ComposerPage})),
);
const OpcachePage = lazy(() =>
    import('@yiisoft/yii-dev-panel/Module/Inspector/Pages/OpcachePage').then((m) => ({default: m.OpcachePage})),
);
const ContainerEntryPage = lazy(() =>
    import('@yiisoft/yii-dev-panel/Module/Inspector/Pages/ContainerEntryPage').then((m) => ({
        default: m.ContainerEntryPage,
    })),
);
const GitPage = lazy(() =>
    import('@yiisoft/yii-dev-panel/Module/Inspector/Pages/Git/GitPage').then((m) => ({default: m.GitPage})),
);
const GitLogPage = lazy(() =>
    import('@yiisoft/yii-dev-panel/Module/Inspector/Pages/Git/GitLogPage').then((m) => ({default: m.GitLogPage})),
);
const CachePage = lazy(() =>
    import('@yiisoft/yii-dev-panel/Module/Inspector/Pages/CachePage').then((m) => ({default: m.CachePage})),
);
const ConfigurationPage = lazy(() =>
    import('@yiisoft/yii-dev-panel/Module/Inspector/Pages/Config/ConfigurationPage').then((m) => ({
        default: m.ConfigurationPage,
    })),
);

const withSuspense = (Component: React.LazyExoticComponent<React.ComponentType>) => (
    <Suspense fallback={<FullScreenCircularProgress />}>
        <Component />
    </Suspense>
);

export const routes = [
    {
        path: 'inspector',
        children: [
            {path: 'tests', element: withSuspense(TestsPage)},
            {path: 'analyse', element: withSuspense(AnalysePage)},
            {path: 'routes', element: withSuspense(RoutesPage)},
            {path: 'events', element: withSuspense(EventsPage)},
            {path: 'files', element: withSuspense(FileExplorerPage)},
            {path: 'translations', element: withSuspense(TranslationsPage)},
            {path: 'commands', element: withSuspense(CommandsPage)},
            {
                path: 'database',
                children: [
                    {index: true, element: withSuspense(DatabasePage)},
                    {path: ':table', element: withSuspense(TablePage)},
                ],
            },
            {path: 'phpinfo', element: withSuspense(PhpInfoPage)},
            {path: 'composer', element: withSuspense(ComposerPage)},
            {path: 'opcache', element: withSuspense(OpcachePage)},
            {path: 'container', children: [{path: 'view', element: withSuspense(ContainerEntryPage)}]},
            {
                path: 'git',
                children: [
                    {index: true, element: withSuspense(GitPage)},
                    {path: 'log', element: withSuspense(GitLogPage)},
                ],
            },
            {path: 'cache', element: withSuspense(CachePage)},
            {path: 'config', element: withSuspense(ConfigurationPage)},
            {path: 'config/:page', element: withSuspense(ConfigurationPage)},
        ],
    },
] satisfies RouteObject[];
