import {renderWithProviders} from '@app-dev-panel/sdk/test-utils';
import {screen} from '@testing-library/react';
import userEvent from '@testing-library/user-event';
import {describe, expect, it, vi} from 'vitest';
import {ResultDialog} from './ResultDialog';

const defaultProps = {open: true, onRerun: vi.fn(), onClose: vi.fn()};

describe('ResultDialog', () => {
    it('shows loading spinner when status is loading', () => {
        renderWithProviders(<ResultDialog {...defaultProps} status="loading" content="loading" />);
        expect(screen.getAllByRole('progressbar').length).toBeGreaterThanOrEqual(1);
        expect(screen.getByText('Command Result')).toBeInTheDocument();
    });

    it('shows command name when provided', () => {
        renderWithProviders(<ResultDialog {...defaultProps} status="ok" content="done" commandName="Run Mago" />);
        expect(screen.getByText('Run Mago')).toBeInTheDocument();
    });

    it('shows success chip for ok status', () => {
        renderWithProviders(<ResultDialog {...defaultProps} status="ok" content="All tests passed" />);
        expect(screen.getByText('Success')).toBeInTheDocument();
        expect(screen.getByText('All tests passed')).toBeInTheDocument();
    });

    it('shows error chip for error status', () => {
        renderWithProviders(<ResultDialog {...defaultProps} status="error" content="output" />);
        expect(screen.getByText('Error')).toBeInTheDocument();
    });

    it('shows failed chip for fail status', () => {
        renderWithProviders(<ResultDialog {...defaultProps} status="fail" content="" />);
        expect(screen.getByText('Failed')).toBeInTheDocument();
    });

    it('shows error alert with errors when status is error', () => {
        renderWithProviders(
            <ResultDialog {...defaultProps} status="error" content="output" errors={['Error 1', 'Error 2']} />,
        );
        expect(screen.getByText('Errors')).toBeInTheDocument();
        expect(screen.getByText('Error 1')).toBeInTheDocument();
        expect(screen.getByText('Error 2')).toBeInTheDocument();
    });

    it('shows error alert with errors when status is fail', () => {
        renderWithProviders(<ResultDialog {...defaultProps} status="fail" content="" errors={['Fatal error']} />);
        expect(screen.getByText('Fatal error')).toBeInTheDocument();
    });

    it('does not show error alert when errors array is empty', () => {
        renderWithProviders(<ResultDialog {...defaultProps} status="error" content="output" errors={[]} />);
        expect(screen.queryByText('Errors')).not.toBeInTheDocument();
    });

    it('does not show error alert when errors is undefined', () => {
        renderWithProviders(<ResultDialog {...defaultProps} status="error" content="output" />);
        expect(screen.queryByText('Errors')).not.toBeInTheDocument();
    });

    it('shows "No output" for null content', () => {
        renderWithProviders(<ResultDialog {...defaultProps} status="ok" content={null} />);
        expect(screen.getByText('No output')).toBeInTheDocument();
    });

    it('shows "No output" for "null" string content', () => {
        renderWithProviders(<ResultDialog {...defaultProps} status="ok" content="null" />);
        expect(screen.getByText('No output')).toBeInTheDocument();
    });

    it('shows "No output" for empty string content', () => {
        renderWithProviders(<ResultDialog {...defaultProps} status="ok" content="" />);
        expect(screen.getByText('No output')).toBeInTheDocument();
    });

    it('shows descriptive fallback when a failed command has no content and no errors', () => {
        renderWithProviders(<ResultDialog {...defaultProps} status="fail" content={null} />);
        expect(screen.getByText('Command failed without output')).toBeInTheDocument();
        expect(screen.queryByText('No output')).not.toBeInTheDocument();
    });

    it('shows descriptive fallback when an errored command has no content and empty errors', () => {
        renderWithProviders(<ResultDialog {...defaultProps} status="error" content="" errors={[]} />);
        expect(screen.getByText('Command errored without output')).toBeInTheDocument();
    });

    it('calls onRerun when Rerun button is clicked', async () => {
        const user = userEvent.setup();
        const onRerun = vi.fn();
        renderWithProviders(<ResultDialog {...defaultProps} onRerun={onRerun} status="ok" content="done" />);
        await user.click(screen.getByRole('button', {name: /rerun/i}));
        expect(onRerun).toHaveBeenCalledOnce();
    });

    it('calls onClose when Close button is clicked', async () => {
        const user = userEvent.setup();
        const onClose = vi.fn();
        renderWithProviders(<ResultDialog {...defaultProps} onClose={onClose} status="ok" content="done" />);
        await user.click(screen.getByRole('button', {name: 'Close'}));
        expect(onClose).toHaveBeenCalledOnce();
    });

    it('calls onClose when close icon button is clicked', async () => {
        const user = userEvent.setup();
        const onClose = vi.fn();
        renderWithProviders(<ResultDialog {...defaultProps} onClose={onClose} status="ok" content="done" />);
        await user.click(screen.getByRole('button', {name: 'close'}));
        expect(onClose).toHaveBeenCalledOnce();
    });

    it('renders JSON content as formatted string', () => {
        const content = {key: 'value'};
        renderWithProviders(<ResultDialog {...defaultProps} status="ok" content={content} />);
        expect(screen.getByText(/\"key\": \"value\"/)).toBeInTheDocument();
    });

    it('is not visible when open is false', () => {
        renderWithProviders(<ResultDialog {...defaultProps} open={false} status="ok" content="done" />);
        expect(screen.queryByText('Command Result')).not.toBeInTheDocument();
    });
});
