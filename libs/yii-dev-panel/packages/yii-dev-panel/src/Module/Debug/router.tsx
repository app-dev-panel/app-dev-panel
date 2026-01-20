import * as Pages from '@yiisoft/yii-dev-panel/Module/Debug/Pages';
import {RouteObject} from 'react-router-dom';

export const routes = [
    {
        path: 'debug',
        element: <Pages.Layout />,
        children: [
            {
                element: <Pages.IndexPage />,
                index: true,
            },
        ],
    },
    {
        path: 'debug/object',
        element: <Pages.ObjectPage />,
    },
    {
        path: 'debug/list',
        element: <Pages.ListPage />,
    },
] satisfies RouteObject[];
