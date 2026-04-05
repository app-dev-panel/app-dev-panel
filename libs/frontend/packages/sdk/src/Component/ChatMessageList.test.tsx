import {type ChatBubble} from '@app-dev-panel/sdk/API/Llm/AiChatSlice';
import {renderWithProviders} from '@app-dev-panel/sdk/test-utils';
import {screen} from '@testing-library/react';
import userEvent from '@testing-library/user-event';
import {describe, expect, it, vi} from 'vitest';
import {ChatMessageList} from './ChatMessageList';

vi.mock('@app-dev-panel/sdk/Component/Markdown', () => ({
    Markdown: ({content}: {content: string}) => <div data-testid="markdown">{content}</div>,
}));

vi.mock('@app-dev-panel/sdk/Component/MessageCopyButton', () => ({
    MessageCopyButton: () => <button data-testid="copy-button">copy</button>,
}));

const makeMsg = (overrides: Partial<ChatBubble> & Pick<ChatBubble, 'id' | 'role' | 'content' | 'status'>): ChatBubble =>
    overrides as ChatBubble;

describe('ChatMessageList', () => {
    it('renders empty state message when no messages', () => {
        renderWithProviders(<ChatMessageList messages={[]} variant="full" />);
        expect(
            screen.getByText('Ask questions about your application, debug data, or get development advice.'),
        ).toBeInTheDocument();
    });

    it('renders custom empty message', () => {
        renderWithProviders(<ChatMessageList messages={[]} variant="compact" emptyMessage="No messages yet" />);
        expect(screen.getByText('No messages yet')).toBeInTheDocument();
    });

    it('renders user message bubble with correct alignment', () => {
        const messages: ChatBubble[] = [makeMsg({id: 'u1', role: 'user', content: 'Hello world', status: 'ok'})];
        renderWithProviders(<ChatMessageList messages={messages} variant="compact" />);
        expect(screen.getByText('Hello world')).toBeInTheDocument();
    });

    it('renders assistant message with Markdown in full variant', () => {
        const messages: ChatBubble[] = [
            makeMsg({id: 'a1', role: 'assistant', content: '**bold** text', status: 'ok'}),
        ];
        renderWithProviders(<ChatMessageList messages={messages} variant="full" />);
        expect(screen.getByTestId('markdown')).toHaveTextContent('**bold** text');
    });

    it('renders assistant message as plain text in compact variant', () => {
        const messages: ChatBubble[] = [
            makeMsg({id: 'a1', role: 'assistant', content: 'plain reply', status: 'ok'}),
        ];
        renderWithProviders(<ChatMessageList messages={messages} variant="compact" />);
        expect(screen.getByText('plain reply')).toBeInTheDocument();
        expect(screen.queryByTestId('markdown')).not.toBeInTheDocument();
    });

    it('renders "Thinking..." for sending status', () => {
        const messages: ChatBubble[] = [makeMsg({id: 's1', role: 'assistant', content: '', status: 'sending'})];
        renderWithProviders(<ChatMessageList messages={messages} variant="full" />);
        expect(screen.getByText('Thinking...')).toBeInTheDocument();
    });

    it('renders error as Alert in full variant', () => {
        const messages: ChatBubble[] = [
            makeMsg({id: 'e1', role: 'assistant', content: 'request body', status: 'error', error: 'Server error'}),
        ];
        renderWithProviders(<ChatMessageList messages={messages} variant="full" />);
        expect(screen.getByRole('alert')).toBeInTheDocument();
        // "Server error" appears in both Alert body and caption (since error !== content)
        expect(screen.getAllByText('Server error')).toHaveLength(2);
    });

    it('renders error as red bubble in compact variant', () => {
        const messages: ChatBubble[] = [
            makeMsg({id: 'e1', role: 'assistant', content: 'fail', status: 'error', error: 'Oops'}),
        ];
        renderWithProviders(<ChatMessageList messages={messages} variant="compact" />);
        expect(screen.getByText('Oops')).toBeInTheDocument();
        expect(screen.queryByRole('alert')).not.toBeInTheDocument();
    });

    it('shows retry button on error bubbles in compact variant when onRetry is provided', async () => {
        const onRetry = vi.fn();
        const messages: ChatBubble[] = [
            makeMsg({id: 'e1', role: 'assistant', content: 'fail', status: 'error', error: 'Oops'}),
        ];
        renderWithProviders(<ChatMessageList messages={messages} variant="compact" onRetry={onRetry} />);

        const retryButton = screen.getByRole('button', {name: 'Retry'});
        expect(retryButton).toBeInTheDocument();

        const user = userEvent.setup();
        await user.click(retryButton);
        expect(onRetry).toHaveBeenCalledWith(0);
    });

    it('shows retry button on error Alert in full variant when onRetry is provided', async () => {
        const onRetry = vi.fn();
        const messages: ChatBubble[] = [
            makeMsg({id: 'e1', role: 'assistant', content: 'fail', status: 'error', error: 'Oops'}),
        ];
        renderWithProviders(<ChatMessageList messages={messages} variant="full" onRetry={onRetry} />);

        const retryButton = screen.getByRole('button', {name: 'Retry'});
        expect(retryButton).toBeInTheDocument();

        const user = userEvent.setup();
        await user.click(retryButton);
        expect(onRetry).toHaveBeenCalledWith(0);
    });

    it('does not show retry button when onRetry is not provided', () => {
        const messages: ChatBubble[] = [
            makeMsg({id: 'e1', role: 'assistant', content: 'fail', status: 'error', error: 'Oops'}),
        ];
        renderWithProviders(<ChatMessageList messages={messages} variant="compact" />);
        expect(screen.queryByRole('button', {name: 'Retry'})).not.toBeInTheDocument();
    });

    it('uses msg.id as key (each message bubble renders)', () => {
        const messages: ChatBubble[] = [
            makeMsg({id: 'id-abc', role: 'user', content: 'first', status: 'ok'}),
            makeMsg({id: 'id-def', role: 'assistant', content: 'second', status: 'ok'}),
        ];
        renderWithProviders(<ChatMessageList messages={messages} variant="compact" />);

        const bubbles = screen.getAllByText(/first|second/);
        expect(bubbles).toHaveLength(2);
    });

    it('does NOT show duplicate error caption when content equals error', () => {
        const messages: ChatBubble[] = [
            makeMsg({id: 'e1', role: 'assistant', content: 'Same text', status: 'error', error: 'Same text'}),
        ];
        renderWithProviders(<ChatMessageList messages={messages} variant="full" />);

        const matches = screen.getAllByText('Same text');
        expect(matches).toHaveLength(1);
    });

    it('shows error caption when content and error differ in full variant', () => {
        const messages: ChatBubble[] = [
            makeMsg({
                id: 'e1',
                role: 'assistant',
                content: 'Original request',
                status: 'error',
                error: 'Something went wrong',
            }),
        ];
        renderWithProviders(<ChatMessageList messages={messages} variant="full" />);

        // Alert body shows the error, caption also shows the error (since content !== error)
        const errorTexts = screen.getAllByText('Something went wrong');
        expect(errorTexts.length).toBe(2);
        // One is inside Alert, one is the caption
        expect(screen.getByRole('alert')).toBeInTheDocument();
    });
});
