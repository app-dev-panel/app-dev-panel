import * as Pages from '@app-dev-panel/panel/Module/Llm/Pages';
import {RouteObject} from 'react-router';

export const routes = [
    {path: '/llm', element: <Pages.Layout />},
    {path: '/llm/callback', element: <Pages.OAuthCallback />},
] satisfies RouteObject[];
