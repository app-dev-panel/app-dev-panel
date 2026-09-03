/**
 * @vitest-environment jsdom
 */
import {framesSlice} from '@app-dev-panel/panel/Module/Frames/Context/Context';
import {openApiSlice} from '@app-dev-panel/panel/Module/OpenApi/Context/Context';
import {projectSyncMiddleware} from '@app-dev-panel/panel/Module/Project/projectSyncMiddleware';
import {projectApi} from '@app-dev-panel/sdk/API/Project/Project';
import {afterEach, beforeEach, describe, expect, it, vi} from 'vitest';

/**
 * The middleware is exercised in isolation — wrapping the bare
 * `(api) => (next) => (action) => …` API in a hand-rolled fake `api` plus a
 * single fake `next` that mutates a shared local state. This keeps the test
 * focused on the middleware's decision logic (when to push, when to hydrate,
 * when to migrate) without dragging in the entire RTK Query / Redux Toolkit
 * runtime, which can't be exercised end-to-end without a real fetch impl.
 */

type SliceState = {
    [openApiSlice.name]: {entries: Record<string, string>};
    [framesSlice.name]: {frames: Record<string, string>};
};

const fulfilledAction = (frames: Record<string, string>, openapi: Record<string, string>) => ({
    // RTK Query's `matchFulfilled` is a predicate function, not a typed action
    // creator, so the actual fulfilled type ends in `/fulfilled` and is what we
    // hand-craft here. We assert the matcher accepts it in a separate test.
    type: `${projectApi.reducerPath}/executeQuery/fulfilled`,
    payload: {config: {version: 1, frames, openapi}, configDir: '/tmp/config/adp'},
    meta: {
        arg: {endpointName: 'getProjectConfig', originalArgs: undefined, type: 'query', subscribe: true},
        requestId: 'test',
        requestStatus: 'fulfilled',
        fulfilledTimeStamp: 0,
        baseQueryMeta: {},
    },
});

type Action = {type: string; payload?: unknown};

const createHarness = async (initial?: Partial<SliceState>) => {
    let state: SliceState = {
        [openApiSlice.name]: {entries: {}, ...(initial?.[openApiSlice.name] ?? {})},
        [framesSlice.name]: {frames: {}, ...(initial?.[framesSlice.name] ?? {})},
    };
    const dispatched: Action[] = [];
    const dispatch = vi.fn((action: unknown) => {
        if (typeof action === 'function') {
            dispatched.push({type: '<thunk>', payload: action});
            return action;
        }
        const a = action as Action;
        dispatched.push(a);
        if (a.type.startsWith(openApiSlice.name + '/')) {
            state = {...state, [openApiSlice.name]: openApiSlice.reducer(state[openApiSlice.name], a)};
        } else if (a.type.startsWith(framesSlice.name + '/')) {
            state = {...state, [framesSlice.name]: framesSlice.reducer(state[framesSlice.name], a)};
        }
        return action;
    });
    const api = {dispatch, getState: () => state};
    const next = vi.fn((a: unknown) => a);
    const handler = projectSyncMiddleware(api as never)(next);

    // Trigger and flush the lazy bootstrap dispatch so the harness starts in a
    // post-bootstrap state. Tests reset `dispatched` after this so they only
    // see thunks queued by their own actions.
    handler({type: '@@harness/init'});
    await Promise.resolve();
    dispatched.length = 0;
    dispatch.mockClear();
    next.mockClear();

    return {handler, dispatch, dispatched, getState: () => state};
};

describe('projectSyncMiddleware', () => {
    beforeEach(() => {
        // Stub fetch so the bootstrap dispatch (a thunk) doesn't try to use the network.
        vi.stubGlobal(
            'fetch',
            vi.fn(() => new Promise(() => {})),
        );
    });

    afterEach(() => {
        vi.useRealTimers();
        vi.unstubAllGlobals();
        vi.restoreAllMocks();
    });

    it('the synthetic fulfilled action shape is recognised by the RTK Query matcher', () => {
        const action = fulfilledAction({}, {});
        expect(projectApi.endpoints.getProjectConfig.matchFulfilled(action)).toBe(true);
    });

    it('hydrates the slices from a fulfilled getProjectConfig action', async () => {
        const {handler, dispatched, getState} = await createHarness();

        handler(fulfilledAction({Logs: 'https://logs.example/'}, {Main: '/openapi.json'}));

        expect(getState()[openApiSlice.name].entries).toEqual({Main: '/openapi.json'});
        expect(getState()[framesSlice.name].frames).toEqual({Logs: 'https://logs.example/'});
        expect(dispatched.filter((a) => a.type === '<thunk>')).toHaveLength(0);
    });

    it('does not feed back into a PUT when applying server state', async () => {
        vi.useFakeTimers();
        const {handler, dispatched} = await createHarness();

        handler(fulfilledAction({Logs: 'https://logs.example/'}, {Main: '/openapi.json'}));
        await vi.advanceTimersByTimeAsync(1_000);

        // The middleware applied server state to the slices; there should be
        // no follow-up PUT — the slice updates it dispatched are suppressed.
        expect(dispatched.filter((a) => a.type === '<thunk>')).toHaveLength(0);
    });

    it('debounces user mutations into a single update dispatch', async () => {
        vi.useFakeTimers();
        const {handler, dispatched} = await createHarness();

        // Establish baseline: server returned an empty config.
        handler(fulfilledAction({}, {}));

        handler(openApiSlice.actions.addApiEntry('https://api1.example/'));
        handler(openApiSlice.actions.addApiEntry('https://api2.example/'));
        handler(framesSlice.actions.addFrame('https://frame.example/'));

        expect(dispatched.filter((a) => a.type === '<thunk>')).toHaveLength(0);

        await vi.advanceTimersByTimeAsync(600);

        expect(dispatched.filter((a) => a.type === '<thunk>')).toHaveLength(1);
    });

    it('triggers a one-shot migration when the server is empty but local has data', async () => {
        vi.useFakeTimers();
        const {handler, dispatched, getState} = await createHarness({
            [openApiSlice.name]: {entries: {Local: 'https://local.example/'}},
        });

        handler(fulfilledAction({}, {}));
        await vi.advanceTimersByTimeAsync(600);

        expect(dispatched.filter((a) => a.type === '<thunk>')).toHaveLength(1);
        // Local data must remain — migration pushes UP, never overwrites local.
        expect(getState()[openApiSlice.name].entries).toEqual({Local: 'https://local.example/'});
    });
});

describe('projectSyncMiddleware event stream', () => {
    class FakeEventSource {
        static instances: FakeEventSource[] = [];
        closed = false;
        onmessage: ((event: MessageEvent) => void) | null = null;
        onerror: (() => void) | null = null;

        constructor(public url: string) {
            FakeEventSource.instances.push(this);
        }

        close() {
            this.closed = true;
        }
    }

    type State = SliceState & {application: {baseUrl: string}};

    const createStreamHarness = async (baseUrl: string) => {
        let state: State = {
            application: {baseUrl},
            [openApiSlice.name]: {entries: {}},
            [framesSlice.name]: {frames: {}},
        };
        const dispatched: Action[] = [];
        const dispatch = vi.fn((action: unknown) => {
            if (typeof action === 'function') {
                dispatched.push({type: '<thunk>', payload: action});
                return action;
            }
            dispatched.push(action as Action);
            return action;
        });
        const api = {dispatch, getState: () => state};
        const handler = projectSyncMiddleware(api as never)(vi.fn((a: unknown) => a));

        handler({type: '@@harness/init'});
        await Promise.resolve();
        dispatched.length = 0;

        return {
            handler,
            dispatched,
            setBaseUrl: (url: string) => {
                state = {...state, application: {baseUrl: url}};
            },
        };
    };

    beforeEach(() => {
        FakeEventSource.instances = [];
        vi.stubGlobal('EventSource', FakeEventSource);
        vi.stubGlobal(
            'fetch',
            vi.fn(() => new Promise(() => {})),
        );
    });

    afterEach(() => {
        vi.unstubAllGlobals();
        vi.restoreAllMocks();
    });

    it('opens the stream against the current backend on bootstrap', async () => {
        await createStreamHarness('http://one.test/');

        expect(FakeEventSource.instances).toHaveLength(1);
        expect(FakeEventSource.instances[0].url).toBe('http://one.test/debug/api/project/event-stream');
    });

    it('re-opens the stream and refetches when application.baseUrl changes', async () => {
        const {handler, setBaseUrl, dispatched} = await createStreamHarness('http://one.test');
        const first = FakeEventSource.instances[0];

        setBaseUrl('http://two.test');
        handler({type: 'application/changeBaseUrl', payload: 'http://two.test'});

        expect(first.closed).toBe(true);
        expect(FakeEventSource.instances).toHaveLength(2);
        expect(FakeEventSource.instances[1].url).toBe('http://two.test/debug/api/project/event-stream');
        // getProjectConfig + getSecrets forced refetch thunks.
        expect(dispatched.filter((a) => a.type === '<thunk>')).toHaveLength(2);
    });

    it('keeps the stream when the base URL is unchanged', async () => {
        const {handler} = await createStreamHarness('http://one.test');

        handler({type: 'application/setToolbarOpen', payload: true});
        handler({type: 'application/changeBaseUrl', payload: 'http://one.test'});

        expect(FakeEventSource.instances).toHaveLength(1);
        expect(FakeEventSource.instances[0].closed).toBe(false);
    });
});
