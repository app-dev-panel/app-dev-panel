import {createSlice, PayloadAction} from '@reduxjs/toolkit';

type AiChatState = {prefillMessage: string | null};

export const AiChatSlice = createSlice({
    name: 'aiChat',
    initialState: {prefillMessage: null} as AiChatState,
    reducers: {
        setPrefillMessage(state, action: PayloadAction<string>) {
            state.prefillMessage = action.payload;
        },
        clearPrefillMessage(state) {
            state.prefillMessage = null;
        },
    },
});

export const {setPrefillMessage, clearPrefillMessage} = AiChatSlice.actions;
