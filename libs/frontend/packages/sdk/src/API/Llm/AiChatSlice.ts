import {createSlice, PayloadAction} from '@reduxjs/toolkit';

type MessageStatus = 'ok' | 'error' | 'sending';
export type ChatBubble = {role: 'user' | 'assistant'; content: string; status: MessageStatus; error?: string};

type AiChatState = {prefillMessage: string | null; floatingOpen: boolean; messages: ChatBubble[]};

export const AiChatSlice = createSlice({
    name: 'aiChat',
    initialState: {prefillMessage: null, floatingOpen: false, messages: []} as AiChatState,
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
        addMessage(state, action: PayloadAction<ChatBubble>) {
            state.messages.push(action.payload);
        },
        updateLastSending(state, action: PayloadAction<{status: MessageStatus; content?: string; error?: string}>) {
            const idx = state.messages.findLastIndex((m) => m.status === 'sending');
            if (idx !== -1) {
                state.messages[idx].status = action.payload.status;
                if (action.payload.content !== undefined) state.messages[idx].content = action.payload.content;
                if (action.payload.error) state.messages[idx].error = action.payload.error;
            }
        },
        removeErrorMessages(state) {
            state.messages = state.messages.filter((m) => m.status !== 'error');
        },
        clearMessages(state) {
            state.messages = [];
        },
    },
});

export const {
    setPrefillMessage,
    clearPrefillMessage,
    setFloatingOpen,
    addMessage,
    updateLastSending,
    removeErrorMessages,
    clearMessages,
} = AiChatSlice.actions;
