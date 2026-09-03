import {createSseUpdatesHandler} from '@app-dev-panel/panel/Application/Component/sseUpdatesHandler';
import {DebugEntry} from '@app-dev-panel/sdk/API/Debug/Debug';
import {addLiveDump, addLiveLog} from '@app-dev-panel/sdk/API/Debug/LiveContext';
import {describe, expect, it, vi} from 'vitest';

const entries = [{id: 'newest'}, {id: 'older'}] as DebugEntry[];

const message = (data: unknown) => ({data: typeof data === 'string' ? data : JSON.stringify(data)}) as MessageEvent;

const makeHandler = (autoLatest: boolean, result: {data?: DebugEntry[]; error?: unknown} = {data: entries}) => {
    const getDebugQuery = vi.fn().mockResolvedValue(result);
    const changeEntry = vi.fn();
    const dispatch = vi.fn();
    const handler = createSseUpdatesHandler({getDebugQuery, changeEntry, dispatch, isAutoLatest: () => autoLatest});
    return {handler, getDebugQuery, changeEntry, dispatch};
};

describe('createSseUpdatesHandler', () => {
    it('refreshes the list and jumps to the newest entry when auto-latest is on', async () => {
        const {handler, getDebugQuery, changeEntry} = makeHandler(true);

        await handler(message({type: 'entry-created', payload: []}));

        expect(getDebugQuery).toHaveBeenCalledTimes(1);
        expect(changeEntry).toHaveBeenCalledWith(entries[0]);
    });

    it('refreshes the list but keeps the current entry when auto-latest is off', async () => {
        const {handler, getDebugQuery, changeEntry} = makeHandler(false);

        await handler(message({type: 'debug-updated', payload: []}));

        expect(getDebugQuery).toHaveBeenCalledTimes(1);
        expect(changeEntry).not.toHaveBeenCalled();
    });

    it('reads the toggle at event time, not at subscription time', async () => {
        let autoLatest = false;
        const getDebugQuery = vi.fn().mockResolvedValue({data: entries});
        const changeEntry = vi.fn();
        const handler = createSseUpdatesHandler({
            getDebugQuery,
            changeEntry,
            dispatch: vi.fn(),
            isAutoLatest: () => autoLatest,
        });

        await handler(message({type: 'entry-created'}));
        expect(changeEntry).not.toHaveBeenCalled();

        autoLatest = true;
        await handler(message({type: 'entry-created'}));
        expect(changeEntry).toHaveBeenCalledWith(entries[0]);
    });

    it('does not select anything when the refreshed list is empty or errored', async () => {
        const empty = makeHandler(true, {data: []});
        await empty.handler(message({type: 'entry-created'}));
        expect(empty.changeEntry).not.toHaveBeenCalled();

        const errored = makeHandler(true, {error: {status: 500}});
        await errored.handler(message({type: 'entry-created'}));
        expect(errored.changeEntry).not.toHaveBeenCalled();
    });

    it('feeds live logs into the store regardless of the toggle', async () => {
        const {handler, dispatch, getDebugQuery} = makeHandler(false);

        await handler(message({type: 'live-log', payload: JSON.stringify({level: 'error', message: 'boom'})}));

        expect(getDebugQuery).not.toHaveBeenCalled();
        expect(dispatch).toHaveBeenCalledWith(addLiveLog({level: 'error', message: 'boom', context: undefined}));
    });

    it('feeds live dumps with their source line', async () => {
        const {handler, dispatch} = makeHandler(false);

        await handler(message({type: 'live-dump', payload: {foo: 'bar', $__line__$: 12}}));

        expect(dispatch).toHaveBeenCalledWith(addLiveDump({variable: {foo: 'bar', $__line__$: 12}, line: '12'}));
    });

    it('ignores malformed messages', async () => {
        const {handler, dispatch, getDebugQuery, changeEntry} = makeHandler(true);

        await handler(message('not json'));
        await handler(message({payload: 'x'}));
        await handler(message({type: 'live-log', payload: 'not json'}));

        expect(getDebugQuery).not.toHaveBeenCalled();
        expect(changeEntry).not.toHaveBeenCalled();
        expect(dispatch).not.toHaveBeenCalled();
    });
});
