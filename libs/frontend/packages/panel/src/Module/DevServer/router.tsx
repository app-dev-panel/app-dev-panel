import * as Pages from '@app-dev-panel/panel/Module/DevServer/Pages';
import {RouteObject} from 'react-router-dom';

export const routes = [{path: '/dev-server', element: <Pages.Layout />}] satisfies RouteObject[];
