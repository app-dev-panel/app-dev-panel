import {renderWithProviders} from '@app-dev-panel/sdk/test-utils';
import {screen} from '@testing-library/react';
import userEvent from '@testing-library/user-event';
import {describe, expect, it, vi} from 'vitest';
import {CommandErrorAlert} from './CommandErrorAlert';

describe('CommandErrorAlert', () => {
    it('renders error title and messages', () => {
        renderWithProviders(<CommandErrorAlert errors={['Error one', 'Error two']} />);
        expect(screen.getByText('Command failed')).toBeInTheDocument();
        expect(screen.getByText('Error one')).toBeInTheDocument();
        expect(screen.getByText('Error two')).toBeInTheDocument();
    });

    it('renders custom title', () => {
        renderWithProviders(<CommandErrorAlert title="Custom title" errors={['Error']} />);
        expect(screen.getByText('Custom title')).toBeInTheDocument();
    });

    it('renders empty errors list without crashing', () => {
        renderWithProviders(<CommandErrorAlert errors={[]} />);
        expect(screen.getByText('Command failed')).toBeInTheDocument();
    });

    it('renders retry button and calls onRetry', async () => {
        const user = userEvent.setup();
        const onRetry = vi.fn();
        renderWithProviders(<CommandErrorAlert errors={['Error']} onRetry={onRetry} />);
        const retryButton = screen.getByRole('button', {name: /retry/i});
        await user.click(retryButton);
        expect(onRetry).toHaveBeenCalledOnce();
    });

    it('does not render retry button when onRetry is not provided', () => {
        renderWithProviders(<CommandErrorAlert errors={['Error']} />);
        expect(screen.queryByRole('button', {name: /retry/i})).not.toBeInTheDocument();
    });

    it('renders dismiss button and calls onDismiss', async () => {
        const user = userEvent.setup();
        const onDismiss = vi.fn();
        renderWithProviders(<CommandErrorAlert errors={['Error']} onDismiss={onDismiss} />);
        const dismissButton = screen.getByRole('button', {name: /dismiss error/i});
        await user.click(dismissButton);
        expect(onDismiss).toHaveBeenCalledOnce();
    });

    it('does not render dismiss button when onDismiss is not provided', () => {
        renderWithProviders(<CommandErrorAlert errors={['Error']} />);
        expect(screen.queryByRole('button', {name: /dismiss error/i})).not.toBeInTheDocument();
    });
});
