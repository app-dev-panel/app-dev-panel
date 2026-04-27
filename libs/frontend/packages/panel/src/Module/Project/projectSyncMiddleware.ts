import {framesSlice, updateFrame} from '@app-dev-panel/panel/Module/Frames/Context/Context';
import {openApiSlice, updateApiEntry} from '@app-dev-panel/panel/Module/OpenApi/Context/Context';
import {projectApi} from '@app-dev-panel/sdk/API/Project/Project';
import type {Action, Middleware, MiddlewareAPI} from '@reduxjs/toolkit';
import {isAnyOf} from '@reduxjs/toolkit';

/**
 * Bridges the offline-first OpenAPI/Frames slices to the backend project
 * config (`config/adp/project.json`).
 *
 * - On startup the middleware fires `getProjectConfig` once. On success the
 *   server document overwrites the local Redux state, except when the server
 *   is empty and `localStorage` already has data — in that case we treat the
 *   local copy as a one-shot migration and `PUT` it back to the backend.
 * - Any user mutation to the OpenAPI/Frames slices schedules a debounced
 *   `PUT` so multiple rapid edits coalesce into a single round-trip.
 * - The middleware ignores its own dispatches (the slice updates triggered
 *   from a server response) to avoid feedback loops.
 */

const SYNC_DEBOUNCE_MS = 500;

const SLICE_MUTATION_TYPES = new Set<string>([
    'store.openApi/addApiEntry',
    'store.openApi/updateApiEntry',
    'store.openApi/deleteApiEntry',
    'store.frames2/addFrame',
    'store.frames2/updateFrame',
    'store.frames2/deleteFrame',
]);

type StateWithSlices = {
    [openApiSlice.name]: {entries: Record<string, string>};
    [framesSlice.name]: {frames: Record<string, string>};
};

const isProjectMutation = (action: Action): boolean =>
    typeof action.type === 'string' && SLICE_MUTATION_TYPES.has(action.type);

const isGetFulfilled = isAnyOf(projectApi.endpoints.getProjectConfig.matchFulfilled);

export const projectSyncMiddleware: Middleware = (api: MiddlewareAPI) => {
    let bootstrapped = false;
    let suppressNextSync = false;
    let pushTimer: ReturnType<typeof setTimeout> | null = null;

    const schedulePush = () => {
        if (pushTimer !== null) clearTimeout(pushTimer);
        pushTimer = setTimeout(() => {
            pushTimer = null;
            const state = api.getState() as StateWithSlices;
            api.dispatch(
                projectApi.endpoints.updateProjectConfig.initiate({
                    frames: state[framesSlice.name].frames,
                    openapi: state[openApiSlice.name].entries,
                }),
            );
        }, SYNC_DEBOUNCE_MS);
    };

    return (next) => (action) => {
        if (typeof action === 'function') {
            // A thunk slipped past redux-thunk — usually because someone
            // dispatched it via an internal RTK Query path. Forwarding it to
            // `next` would tank `createStore`'s plain-action check, so we
            // re-enter the chain via the store's `dispatch` instead.
            return api.dispatch(action as never);
        }
        if (typeof action !== 'object' || action === null || typeof (action as Action).type !== 'string') {
            return next(action);
        }

        // Trigger the initial fetch lazily on the first plain action hitting
        // the store. Defer to a microtask so the dispatch happens cleanly on
        // its own stack frame, avoiding re-entrancy into our `next`.
        if (!bootstrapped) {
            bootstrapped = true;
            queueMicrotask(() => {
                api.dispatch(projectApi.endpoints.getProjectConfig.initiate());
            });
        }

        const result = next(action);

        if (isGetFulfilled(action)) {
            const {config} = action.payload;
            const localState = api.getState() as StateWithSlices;
            const localOpenApi = localState[openApiSlice.name].entries;
            const localFrames = localState[framesSlice.name].frames;

            const serverIsEmpty = Object.keys(config.openapi).length === 0 && Object.keys(config.frames).length === 0;
            const localHasData = Object.keys(localOpenApi).length > 0 || Object.keys(localFrames).length > 0;

            if (serverIsEmpty && localHasData) {
                // First-run migration: push local state to a still-empty backend.
                schedulePush();
            } else {
                suppressNextSync = true;
                api.dispatch(updateApiEntry(config.openapi));
                api.dispatch(updateFrame(config.frames));
                suppressNextSync = false;
            }
            return result;
        }

        if (isProjectMutation(action) && !suppressNextSync) {
            schedulePush();
        }

        return result;
    };
};
