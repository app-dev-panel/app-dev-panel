import {RouteObject} from 'react-router';

export const routes = [
    {
        index: true,
        lazy: async () => {
            const {IndexPage} = await import('@app-dev-panel/panel/Application/Pages/IndexPage');
            return {Component: IndexPage};
        },
    },
    {
        path: 'shared',
        lazy: async () => {
            const {SharedPage} = await import('@app-dev-panel/panel/Application/Pages/SharedPage');
            return {Component: SharedPage};
        },
    },
] satisfies RouteObject[];
