import {createSlice} from '@reduxjs/toolkit';
import {DebugEntry} from '@yiisoft/yii-dev-panel-sdk/API/Debug/Debug';
import {useSelector} from 'react-redux';

type StateType = {
    entry: DebugEntry | null;
    currentPageRequestIds: string[];
};
const initialState: StateType = {
    entry: null,
    currentPageRequestIds: [],
};
export const debugSlice = createSlice({
    name: 'store.debug',
    initialState: initialState,
    reducers: {
        changeEntryAction: (state, action) => {
            state.entry = action.payload;
        },
        addCurrentPageRequestId: (state, action) => {
            state.currentPageRequestIds = [...state.currentPageRequestIds, action.payload].slice(0, 100);
        },
    },
});

export const {changeEntryAction, addCurrentPageRequestId} = debugSlice.actions;

type State = {[debugSlice.name]: ReturnType<typeof debugSlice.getInitialState>};
export const useDebugEntry = () => useSelector((state: State) => state[debugSlice.name]?.entry);
export const useCurrentPageRequestIds = (): string[] =>
    useSelector((state: State) => state[debugSlice.name]?.currentPageRequestIds);
