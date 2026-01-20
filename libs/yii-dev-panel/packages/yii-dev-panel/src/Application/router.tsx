import * as Pages from '@yiisoft/yii-dev-panel/Application/Pages';
import {RouteObject} from 'react-router-dom';

export const routes = [
    {
        index: true,
        element: <Pages.IndexPage />,
    },
    {
        path: 'shared',
        element: <Pages.SharedPage />,
    },
] satisfies RouteObject[];
