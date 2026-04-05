import {describe, expect, it} from 'vitest';
import {
    addMessage,
    AiChatSlice,
    type ChatBubble,
    clearMessages,
    clearPrefillMessage,
    removeErrorMessages,
    setPrefillMessage,
    updateLastSending,
} from './AiChatSlice';

const reducer = AiChatSlice.reducer;
const initialState = () => reducer(undefined, {type: '@@INIT'});

describe('AiChatSlice', () => {
    describe('addMessage', () => {
        it('adds a message with an auto-generated unique id', () => {
            const state = initialState();
            const next = reducer(state, addMessage({role: 'user', content: 'hello', status: 'ok'}));

            expect(next.messages).toHaveLength(1);
            expect(next.messages[0].role).toBe('user');
            expect(next.messages[0].content).toBe('hello');
            expect(next.messages[0].status).toBe('ok');
            expect(next.messages[0].id).toEqual(expect.any(String));
            expect(next.messages[0].id.length).toBeGreaterThan(0);
        });

        it('generates unique ids for each message', () => {
            let state = initialState();
            state = reducer(state, addMessage({role: 'user', content: 'a', status: 'ok'}));
            state = reducer(state, addMessage({role: 'assistant', content: 'b', status: 'ok'}));

            expect(state.messages).toHaveLength(2);
            expect(state.messages[0].id).not.toBe(state.messages[1].id);
        });
    });

    describe('updateLastSending', () => {
        it('updates content, status, and error of the last sending message', () => {
            let state = initialState();
            state = reducer(state, addMessage({role: 'user', content: 'q', status: 'ok'}));
            state = reducer(state, addMessage({role: 'assistant', content: '', status: 'sending'}));

            const next = reducer(
                state,
                updateLastSending({status: 'ok', content: 'answer', error: undefined}),
            );

            expect(next.messages[1].status).toBe('ok');
            expect(next.messages[1].content).toBe('answer');
        });

        it('sets error field when provided', () => {
            let state = initialState();
            state = reducer(state, addMessage({role: 'assistant', content: '', status: 'sending'}));

            const next = reducer(
                state,
                updateLastSending({status: 'error', content: 'fail', error: 'Something went wrong'}),
            );

            expect(next.messages[0].status).toBe('error');
            expect(next.messages[0].error).toBe('Something went wrong');
            expect(next.messages[0].content).toBe('fail');
        });

        it('does not crash when no sending messages exist', () => {
            let state = initialState();
            state = reducer(state, addMessage({role: 'user', content: 'q', status: 'ok'}));

            const next = reducer(state, updateLastSending({status: 'ok', content: 'x'}));

            expect(next.messages).toHaveLength(1);
            expect(next.messages[0].content).toBe('q');
        });

        it('updates the last sending message when multiple exist', () => {
            let state = initialState();
            state = reducer(state, addMessage({role: 'assistant', content: 'first', status: 'sending'}));
            state = reducer(state, addMessage({role: 'assistant', content: 'second', status: 'sending'}));

            const next = reducer(state, updateLastSending({status: 'ok', content: 'done'}));

            expect(next.messages[0].status).toBe('sending');
            expect(next.messages[1].status).toBe('ok');
            expect(next.messages[1].content).toBe('done');
        });
    });

    describe('removeErrorMessages', () => {
        it('filters out only error messages', () => {
            let state = initialState();
            state = reducer(state, addMessage({role: 'user', content: 'q', status: 'ok'}));
            state = reducer(state, addMessage({role: 'assistant', content: 'err', status: 'error'}));
            state = reducer(state, addMessage({role: 'assistant', content: 'ok', status: 'ok'}));

            const next = reducer(state, removeErrorMessages());

            expect(next.messages).toHaveLength(2);
            expect(next.messages.every((m: ChatBubble) => m.status !== 'error')).toBe(true);
        });
    });

    describe('clearMessages', () => {
        it('empties the messages array', () => {
            let state = initialState();
            state = reducer(state, addMessage({role: 'user', content: 'a', status: 'ok'}));
            state = reducer(state, addMessage({role: 'assistant', content: 'b', status: 'ok'}));

            const next = reducer(state, clearMessages());

            expect(next.messages).toEqual([]);
        });
    });

    describe('setPrefillMessage', () => {
        it('sets message and opens floating', () => {
            const state = initialState();
            const next = reducer(state, setPrefillMessage('Explain this error'));

            expect(next.prefillMessage).toBe('Explain this error');
            expect(next.floatingOpen).toBe(true);
        });
    });

    describe('clearPrefillMessage', () => {
        it('clears the prefill message', () => {
            let state = initialState();
            state = reducer(state, setPrefillMessage('something'));

            const next = reducer(state, clearPrefillMessage());

            expect(next.prefillMessage).toBeNull();
        });
    });
});
