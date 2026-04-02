import {RouteObject} from 'react-router';

export const routes = [
    {
        path: '/llm/mcp',
        lazy: async () => {
            const {McpPage} = await import('@app-dev-panel/panel/Module/Mcp/Pages/McpPage');
            return {Component: McpPage};
        },
    },
] satisfies RouteObject[];
