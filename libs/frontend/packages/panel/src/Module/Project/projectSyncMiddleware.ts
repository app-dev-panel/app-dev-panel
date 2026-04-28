import {framesSlice, updateFrame} from '@app-dev-panel/panel/Module/Frames/Context/Context';
import {openApiSlice, updateApiEntry} from '@app-dev-panel/panel/Module/OpenApi/Context/Context';
import {projectApi} from '@app-dev-panel/sdk/API/Project/Project';
import type {Action, Middleware, ThunkAction, ThunkDispatch, UnknownAction} from '@reduxjs/toolkit';
import {isAnyOf} from '@reduxjs/toolkit';

/**
 * Bridges the offline-first OpenAPI/Frames slices to the backend project
 * config (`config/adp/project.json`) and listens for real-time changes via
 * a Server-Sent Events stream.
 *
 * Lifecycle:
 *
 * 1. **Bootstrap** — on the first plain action the middleware sees, it
 *    dispatches `getProjectConfig`. On success the server document
 *    overwrites the local Redux state, except when the server is empty
 *    and `localStorage` already has data — in that case we treat the
 *    local copy as a one-shot migration and `PUT` it back.
 * 2. **User edits** — any mutation to the OpenAPI/Frames slices schedules a
 *    debounced PUT so multiple rapid edits coalesce into a single round-trip.
 * 3. **External changes** — an `EventSource` listens to
 *    `/debug/api/project/event-stream`. When a `project-config-changed`
 *    event arrives (from `git pull`, the API saving a PUT/PATCH, or
 *    another browser tab editing the same backend), the middleware fires
 *    a forced refetch. The existing `getProjectConfig.fulfilled` handler
 *    then applies the new state — same code path as the initial bootstrap.
 *
 * The middleware ignores its own dispatches (slice updates triggered by a
 * server response) to avoid feedback loops.
 */

const SYNC_DEBOUNCE_MS = 500;
const SSE_RECONNECT_MS = 3_000;

const SLICE_MUTATION_TYPES = new Set<string>([
    'store.openApi/addApiEntry',
    'store.openApi/updateApiEntry',
    'store.openApi/deleteApiEntry',
    'store.frames2/addFrame',
    'store.frames2/updateFrame',
    'store.frames2/deleteFrame',
]);

type StateWithSlices = {
    application?: {baseUrl?: string};
    [openApiSlice.name]: {entries: Record<string, string>};
    [framesSlice.name]: {frames: Record<string, string>};
};

/**
 * Local typed dispatch. We can't import `AppDispatch` from `store.ts` because
 * the store's type is inferred from `createStore`, which transitively pulls
 * in this middleware — TypeScript would see the resulting cycle even with
 * `import type`. `ThunkDispatch` here mirrors the dispatch the real store
 * exposes (the store uses `configureStore`, which always installs
 * `redux-thunk`), so dispatching RTK Query `.initiate(...)` thunks below
 * type-checks without any casts.
 */
type ProjectDispatch = ThunkDispatch<StateWithSlices, unknown, UnknownAction>;

// Type predicate: narrows `unknown` (the type Redux gives us for `action`)
// straight to `Action`, removing the need for an intermediate
// `typedAction = action as Action` cast further down.
const isPlainAction = (action: unknown): action is Action =>
    typeof action === 'object' && action !== null && typeof (action as {type?: unknown}).type === 'string';

const isProjectMutation = (action: Action): boolean =>
    typeof action.type === 'string' && SLICE_MUTATION_TYPES.has(action.type);

const isGetFulfilled = isAnyOf(projectApi.endpoints.getProjectConfig.matchFulfilled);

// `Middleware<DispatchExt, State, Dispatch>` parameterizes the API the
// middleware sees. The first generic is the dispatch *extension* — extra
// overloads this middleware would inject into the store's dispatch type.
// We don't add any (we only consume thunks), so `unknown` is correct.
// With a `ThunkDispatch`-compatible `Dispatch`, `api.dispatch(thunk)` type-checks
// for the RTK Query `.initiate(...)` thunks dispatched below — no `as never` casts.
export const projectSyncMiddleware: Middleware<unknown, StateWithSlices, ProjectDispatch> = (api) => {
    let bootstrapped = false;
    let suppressNextSync = false;
    let pushTimer: ReturnType<typeof setTimeout> | null = null;
    let eventSource: EventSource | null = null;
    let sseReconnectTimer: ReturnType<typeof setTimeout> | null = null;

    const schedulePush = () => {
        if (pushTimer !== null) clearTimeout(pushTimer);
        pushTimer = setTimeout(() => {
            pushTimer = null;
            const state = api.getState();
            api.dispatch(
                projectApi.endpoints.updateProjectConfig.initiate({
                    frames: state[framesSlice.name].frames,
                    openapi: state[openApiSlice.name].entries,
                }),
            );
        }, SYNC_DEBOUNCE_MS);
    };

    /**
     * Force a refetch of every project-related query. The existing
     * `getProjectConfig.fulfilled` handler then routes the new state into
     * the OpenAPI/Frames slices; secrets are routed via RTK Query's tag
     * invalidation, which any subscribed component picks up automatically.
     */
    const forceRefetchAll = () => {
        api.dispatch(projectApi.endpoints.getProjectConfig.initiate(undefined, {forceRefetch: true}));
        api.dispatch(projectApi.endpoints.getSecrets.initiate(undefined, {forceRefetch: true}));
    };

    const connectEventStream = () => {
        if (typeof EventSource === 'undefined') return; // Non-browser env (SSR, tests).
        if (eventSource !== null) return;

        const baseUrl = (api.getState().application?.baseUrl ?? '').replace(/\/$/, '');
        const url = `${baseUrl}/debug/api/project/event-stream`;

        let source: EventSource;
        try {
            source = new EventSource(url, {withCredentials: false});
        } catch {
            return;
        }
        eventSource = source;

        source.onmessage = (event: MessageEvent) => {
            try {
                const payload = JSON.parse(event.data) as {type?: string};
                if (payload.type === 'project-config-changed') {
                    forceRefetchAll();
                }
            } catch {
                // Ignore non-JSON payloads / heartbeats.
            }
        };

        source.onerror = () => {
            // Browser already auto-reconnects on transient errors. We close
            // explicitly and re-create the EventSource on a longer delay so
            // we don't hammer the backend if the SSE endpoint is permanently
            // down (no PHP backend, missing route, …).
            source.close();
            if (eventSource === source) {
                eventSource = null;
            }
            if (sseReconnectTimer !== null) clearTimeout(sseReconnectTimer);
            sseReconnectTimer = setTimeout(connectEventStream, SSE_RECONNECT_MS);
        };
    };

    return (next) => (action) => {
        if (typeof action === 'function') {
            // A thunk slipped past redux-thunk — usually because someone
            // dispatched it via an internal RTK Query path. Forwarding it to
            // `next` would tank `createStore`'s plain-action check, so we
            // re-enter the chain via the store's `dispatch` instead.
            // The `typeof === 'function'` check narrows `action` to the bare
            // `Function` type, which doesn't structurally match `ThunkAction`'s
            // call signature; the cast restores the convention (any function
            // dispatched into a thunk-enabled store is a thunk).
            return api.dispatch(action as ThunkAction<unknown, StateWithSlices, unknown, UnknownAction>);
        }
        if (!isPlainAction(action)) {
            return next(action);
        }

        // Trigger the initial fetch lazily on the first plain action hitting
        // the store. Defer to a microtask so the dispatch happens cleanly on
        // its own stack frame, avoiding re-entrancy into our `next`.
        if (!bootstrapped) {
            bootstrapped = true;
            queueMicrotask(() => {
                api.dispatch(projectApi.endpoints.getProjectConfig.initiate());
                connectEventStream();
            });
        }

        const result = next(action);

        if (isGetFulfilled(action)) {
            const {config} = action.payload;
            const localState = api.getState();
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
