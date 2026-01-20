import {Middleware, Reducer} from '@reduxjs/toolkit';
import {RouteObject} from 'react-router-dom';

export interface ModuleInterface {
    routes: RouteObject[];
    reducers: Record<string, Reducer>;
    middlewares: Middleware[];
    standaloneModule: boolean;
}
