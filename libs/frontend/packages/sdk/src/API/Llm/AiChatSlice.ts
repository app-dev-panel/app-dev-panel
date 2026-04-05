import {createSlice, PayloadAction} from '@reduxjs/toolkit';

type AiChatState = {prefillMessage: string | null; floatingOpen: boolean};

export const AiChatSlice = createSlice({
    name: 'aiChat',
    initialState: {prefillMessage: null, floatingOpen: false} as AiChatState,
    reducers: {
        setPrefillMessage(state, action: PayloadAction<string>) {
            state.prefillMessage = action.payload;
            state.floatingOpen = true;
        },
        setFloatingOpen(state, action: PayloadAction<boolean>) {
            state.floatingOpen = action.payload;
        },
        clearPrefillMessage(state) {
            state.prefillMessage = null;
        },
    },
});

export const {setPrefillMessage, clearPrefillMessage, setFloatingOpen} = AiChatSlice.actions;
