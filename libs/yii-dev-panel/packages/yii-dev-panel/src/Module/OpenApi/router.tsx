import * as Pages from '@yiisoft/yii-dev-panel/Module/OpenApi/Pages';
import {RouteObject} from 'react-router-dom';

export const routes = [
    {
        path: '/open-api',
        element: <Pages.Layout />,
    },
] satisfies RouteObject[];
