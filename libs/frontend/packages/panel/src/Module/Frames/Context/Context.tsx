import {createSlice} from '@reduxjs/toolkit';
import {useSelector} from 'react-redux';

export const framesSlice = createSlice({
    name: 'store.frames2',
    initialState: {frames: {} as Record<string, string>},
    reducers: {
        addFrame: (state, action: {payload: string; type: string}) => {
            const url = action.payload;
            state.frames = {...state.frames, [url]: url};
        },
        updateFrame: (state, action: {payload: Record<string, string>; type: string}) => {
            state.frames = action.payload;
        },
        deleteFrame: (state, action: {payload: string; type: string}) => {
            const frames = Object.entries(state.frames).filter(([name]) => name !== action.payload);
            state.frames = Object.fromEntries(frames);
        },
    },
});

export const {addFrame, updateFrame, deleteFrame} = framesSlice.actions;

type State = {[framesSlice.name]: ReturnType<typeof framesSlice.getInitialState>};
export const useFramesEntries = () => useSelector((state: State) => state[framesSlice.name].frames);
