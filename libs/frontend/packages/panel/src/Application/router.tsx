import * as Pages from '@app-dev-panel/panel/Application/Pages';
import {RouteObject} from 'react-router';

export const routes = [
    {index: true, element: <Pages.IndexPage />},
    {path: 'shared', element: <Pages.SharedPage />},
] satisfies RouteObject[];
