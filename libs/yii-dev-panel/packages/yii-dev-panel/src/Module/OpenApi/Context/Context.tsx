import {createSlice} from '@reduxjs/toolkit';
import {useSelector} from 'react-redux';

export const openApiSlice = createSlice({
    name: 'store.openApi',
    initialState: {
        entries: {} as Record<string, string>,
    },
    reducers: {
        addApiEntry: (state, action) => {
            state.entries = {
                ...state.entries,
                [action.payload]: action.payload,
            };
        },
        updateApiEntry: (state, action) => {
            state.entries = action.payload;
        },
        deleteApiEntry: (state, action) => {
            const entries = Object.entries(state.entries).filter(([name, url]) => name != action.payload);
            state.entries = Object.fromEntries(entries);
        },
    },
});

export const {addApiEntry, updateApiEntry, deleteApiEntry} = openApiSlice.actions;

type State = {[openApiSlice.name]: ReturnType<typeof openApiSlice.getInitialState>};
export const useOpenApiEntries = () => useSelector((state: State) => state[openApiSlice.name].entries);
