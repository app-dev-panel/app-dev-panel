import {describe, expect, it} from 'vitest';
import {
    addLiveDump,
    addLiveLog,
    clearLiveEntries,
    liveSlice,
    selectLiveCount,
    selectLiveEntries,
    setLivePaused,
    toggleLivePaused,
} from './LiveContext';

const reducer = liveSlice.reducer;
const initial = liveSlice.getInitialState();

describe('liveSlice', () => {
    it('starts with empty entries and not paused', () => {
        expect(initial).toEqual({entries: [], paused: false});
    });

    it('adds a log entry', () => {
        const state = reducer(initial, addLiveLog({level: 'info', message: 'Hello'}));
        expect(state.entries).toHaveLength(1);
        expect(state.entries[0].kind).toBe('log');
        expect(state.entries[0].payload).toEqual({level: 'info', message: 'Hello'});
    });

    it('adds a dump entry', () => {
        const state = reducer(initial, addLiveDump({variable: {x: 1}}));
        expect(state.entries).toHaveLength(1);
        expect(state.entries[0].kind).toBe('dump');
        expect(state.entries[0].payload).toEqual({variable: {x: 1}});
    });

    it('prepends new entries (newest first)', () => {
        let state = reducer(initial, addLiveLog({level: 'info', message: 'First'}));
        state = reducer(state, addLiveLog({level: 'debug', message: 'Second'}));
        expect(state.entries[0].payload.message).toBe('Second');
        expect(state.entries[1].payload.message).toBe('First');
    });

    it('clears all entries', () => {
        let state = reducer(initial, addLiveLog({level: 'info', message: 'A'}));
        state = reducer(state, addLiveDump({variable: 'b'}));
        state = reducer(state, clearLiveEntries());
        expect(state.entries).toHaveLength(0);
    });

    it('does not add entries when paused', () => {
        let state = reducer(initial, setLivePaused(true));
        state = reducer(state, addLiveLog({level: 'info', message: 'Ignored'}));
        state = reducer(state, addLiveDump({variable: 'Ignored'}));
        expect(state.entries).toHaveLength(0);
    });

    it('toggles paused state', () => {
        let state = reducer(initial, toggleLivePaused());
        expect(state.paused).toBe(true);
        state = reducer(state, toggleLivePaused());
        expect(state.paused).toBe(false);
    });

    it('caps entries at 500', () => {
        let state = initial;
        for (let i = 0; i < 510; i++) {
            state = reducer(state, addLiveLog({level: 'debug', message: `msg-${i}`}));
        }
        expect(state.entries).toHaveLength(500);
        // Most recent should be the last added
        expect(state.entries[0].payload.message).toBe('msg-509');
    });

    it('generates unique IDs', () => {
        let state = reducer(initial, addLiveLog({level: 'info', message: 'A'}));
        state = reducer(state, addLiveLog({level: 'info', message: 'B'}));
        expect(state.entries[0].id).not.toBe(state.entries[1].id);
    });

    it('sets timestamp on entries', () => {
        const before = Date.now();
        const state = reducer(initial, addLiveLog({level: 'info', message: 'A'}));
        const after = Date.now();
        expect(state.entries[0].timestamp).toBeGreaterThanOrEqual(before);
        expect(state.entries[0].timestamp).toBeLessThanOrEqual(after);
    });
});

describe('selectors', () => {
    it('selectLiveEntries returns entries', () => {
        const state = {
            [liveSlice.name]: {
                entries: [{id: '1', kind: 'log' as const, timestamp: 0, payload: {level: 'info', message: ''}}],
                paused: false,
            },
        };
        expect(selectLiveEntries(state)).toHaveLength(1);
    });

    it('selectLiveEntries returns empty array for missing state', () => {
        expect(selectLiveEntries({[liveSlice.name]: undefined as any})).toEqual([]);
    });

    it('selectLiveCount returns count', () => {
        const state = {
            [liveSlice.name]: {
                entries: [
                    {id: '1', kind: 'log' as const, timestamp: 0, payload: {level: 'info', message: ''}},
                    {id: '2', kind: 'log' as const, timestamp: 0, payload: {level: 'info', message: ''}},
                ],
                paused: false,
            },
        };
        expect(selectLiveCount(state)).toBe(2);
    });
});
