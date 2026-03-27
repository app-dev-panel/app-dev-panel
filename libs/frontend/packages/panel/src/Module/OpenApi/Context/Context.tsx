import {createSlice} from '@reduxjs/toolkit';
import {useSelector} from 'react-redux';

export const openApiSlice = createSlice({
    name: 'store.openApi',
    initialState: {entries: {} as Record<string, string>},
    reducers: {
        addApiEntry: (state, action: {payload: string; type: string}) => {
            const url = action.payload;
            state.entries = {...state.entries, [url]: url};
        },
        updateApiEntry: (state, action: {payload: Record<string, string>; type: string}) => {
            state.entries = action.payload;
        },
        deleteApiEntry: (state, action: {payload: string; type: string}) => {
            const entries = Object.entries(state.entries).filter(([name]) => name !== action.payload);
            state.entries = Object.fromEntries(entries);
        },
    },
});

export const {addApiEntry, updateApiEntry, deleteApiEntry} = openApiSlice.actions;

type State = {[openApiSlice.name]: ReturnType<typeof openApiSlice.getInitialState>};
export const useOpenApiEntries = () => useSelector((state: State) => state[openApiSlice.name].entries);
