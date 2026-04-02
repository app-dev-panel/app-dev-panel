import {RouteObject} from 'react-router';

export const routes = [
    {
        path: '/llm',
        lazy: async () => {
            const {Layout} = await import('@app-dev-panel/panel/Module/Llm/Pages/Layout');
            return {Component: Layout};
        },
    },
    {
        path: '/llm/callback',
        lazy: async () => {
            const {OAuthCallback} = await import('@app-dev-panel/panel/Module/Llm/Pages/OAuthCallback');
            return {Component: OAuthCallback};
        },
    },
] satisfies RouteObject[];
