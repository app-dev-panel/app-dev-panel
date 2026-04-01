import {Middleware, Reducer} from '@reduxjs/toolkit';
import {RouteObject} from 'react-router';

export type ModuleInterface = {
    routes: RouteObject[];
    reducers: Record<string, Reducer>;
    middlewares: Middleware[];
    standaloneModule: boolean;
};
