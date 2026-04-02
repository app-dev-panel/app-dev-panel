import {RouteObject} from 'react-router';

export const routes = [
    {
        path: '/frames',
        lazy: async () => {
            const {Layout} = await import('@app-dev-panel/panel/Module/Frames/Pages/Layout');
            return {Component: Layout};
        },
    },
] satisfies RouteObject[];
