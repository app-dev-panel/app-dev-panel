import * as Pages from '@yiisoft/yii-dev-panel/Module/Frames/Pages';
import {RouteObject} from 'react-router-dom';

export const routes = [
    {
        path: '/frames',
        element: <Pages.Layout />,
    },
] satisfies RouteObject[];
