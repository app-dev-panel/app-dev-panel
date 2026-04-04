import {createSelector, createSlice, nanoid, PayloadAction} from '@reduxjs/toolkit';
import {useSelector} from 'react-redux';

// ---------------------------------------------------------------------------
// Types
// ---------------------------------------------------------------------------

export type LiveLogEntry = {
    id: string;
    kind: 'log';
    timestamp: number;
    payload: {level: string; message: string; context?: Record<string, unknown>};
};

export type LiveDumpEntry = {id: string; kind: 'dump'; timestamp: number; payload: {variable: unknown; line?: string}};

export type LiveEntry = LiveLogEntry | LiveDumpEntry;

type LiveState = {entries: LiveEntry[]; paused: boolean};

const MAX_ENTRIES = 500;

// ---------------------------------------------------------------------------
// Slice
// ---------------------------------------------------------------------------

const initialState: LiveState = {entries: [], paused: false};

export const liveSlice = createSlice({
    name: 'store.live',
    initialState,
    reducers: {
        addLiveLog(state, action: PayloadAction<LiveLogEntry['payload']>) {
            if (state.paused) return;
            state.entries.unshift({id: nanoid(), kind: 'log', timestamp: Date.now(), payload: action.payload});
            if (state.entries.length > MAX_ENTRIES) {
                state.entries.length = MAX_ENTRIES;
            }
        },
        addLiveDump(state, action: PayloadAction<LiveDumpEntry['payload']>) {
            if (state.paused) return;
            state.entries.unshift({id: nanoid(), kind: 'dump', timestamp: Date.now(), payload: action.payload});
            if (state.entries.length > MAX_ENTRIES) {
                state.entries.length = MAX_ENTRIES;
            }
        },
        clearLiveEntries(state) {
            state.entries = [];
        },
        toggleLivePaused(state) {
            state.paused = !state.paused;
        },
        setLivePaused(state, action: PayloadAction<boolean>) {
            state.paused = action.payload;
        },
    },
});

export const {addLiveLog, addLiveDump, clearLiveEntries, toggleLivePaused, setLivePaused} = liveSlice.actions;

// ---------------------------------------------------------------------------
// Selectors
// ---------------------------------------------------------------------------

type State = {[liveSlice.name]: ReturnType<typeof liveSlice.getInitialState>};

const selectLiveState = (state: State) => state[liveSlice.name];

export const selectLiveEntries = createSelector(selectLiveState, (s) => s?.entries ?? []);
export const selectLivePaused = createSelector(selectLiveState, (s) => s?.paused ?? false);
export const selectLiveCount = createSelector(selectLiveEntries, (entries) => entries.length);

export const useLiveEntries = () => useSelector(selectLiveEntries);
export const useLivePaused = () => useSelector(selectLivePaused);
export const useLiveCount = () => useSelector(selectLiveCount);
