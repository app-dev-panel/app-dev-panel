import {RouteObject} from 'react-router';

/**
 * The Project module is plumbing only: it wires the OpenAPI/Frames slices
 * to the backend via {@link projectSyncMiddleware} but does not own any
 * page of its own. The relevant UI lives in the OpenAPI and Frames modules.
 */
export const routes = [] satisfies RouteObject[];
