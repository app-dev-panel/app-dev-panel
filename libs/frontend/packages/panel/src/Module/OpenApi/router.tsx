import {RouteObject} from 'react-router';

export const routes = [
    {
        path: '/open-api',
        lazy: async () => {
            const {Layout} = await import('@app-dev-panel/panel/Module/OpenApi/Pages/Layout');
            return {Component: Layout};
        },
    },
] satisfies RouteObject[];
