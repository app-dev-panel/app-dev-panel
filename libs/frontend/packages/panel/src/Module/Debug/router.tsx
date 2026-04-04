import {RouteObject} from 'react-router';

export const routes = [
    {
        path: 'debug',
        lazy: async () => {
            const {Layout} = await import('@app-dev-panel/panel/Module/Debug/Pages/Layout');
            return {Component: Layout};
        },
        children: [
            {
                index: true,
                lazy: async () => {
                    const {IndexPage} = await import('@app-dev-panel/panel/Module/Debug/Pages/IndexPage');
                    return {Component: IndexPage};
                },
            },
            {
                path: 'list',
                lazy: async () => {
                    const {ListPage} = await import('@app-dev-panel/panel/Module/Debug/Pages/ListPage');
                    return {Component: ListPage};
                },
            },
            {
                path: 'live',
                lazy: async () => {
                    const {LivePage} = await import('@app-dev-panel/panel/Module/Debug/Pages/LivePage');
                    return {Component: LivePage};
                },
            },
        ],
    },
    {
        path: 'debug/object',
        lazy: async () => {
            const {ObjectPage} = await import('@app-dev-panel/panel/Module/Debug/Pages/ObjectPage');
            return {Component: ObjectPage};
        },
    },
] satisfies RouteObject[];
