import * as Pages from '@app-dev-panel/panel/Module/Mcp/Pages';
import {RouteObject} from 'react-router';

export const routes = [{path: '/llm/mcp', element: <Pages.McpPage />}] satisfies RouteObject[];
