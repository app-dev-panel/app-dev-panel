import * as Pages from '@app-dev-panel/toolbar/Module/Toolbar/Pages';
import {RouteObject} from 'react-router';

export const routes = [{path: '*', element: <Pages.Toolbar />}] satisfies RouteObject[];
