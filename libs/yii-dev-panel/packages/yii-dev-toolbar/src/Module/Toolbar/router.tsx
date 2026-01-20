import * as Pages from '@yiisoft/yii-dev-toolbar/Module/Toolbar/Pages';
import {RouteObject} from 'react-router-dom';

export const routes = [
    {
        path: '*',
        element: <Pages.Toolbar />,
    },
] satisfies RouteObject[];
