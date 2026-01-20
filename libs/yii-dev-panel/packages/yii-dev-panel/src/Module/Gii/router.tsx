import * as Pages from '@yiisoft/yii-dev-panel/Module/Gii/Pages';
import {RouteObject} from 'react-router-dom';

export const routes = [
    {
        path: '/gii',
        element: <Pages.Layout />,
    },
] satisfies RouteObject[];
