import * as Pages from '@app-dev-panel/panel/Module/Mcp/Pages';
import {RouteObject} from 'react-router-dom';

export const routes = [{path: '/mcp', element: <Pages.McpPage />}] satisfies RouteObject[];
