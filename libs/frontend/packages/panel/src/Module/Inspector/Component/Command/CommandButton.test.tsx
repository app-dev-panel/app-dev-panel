import {renderWithProviders} from '@app-dev-panel/sdk/test-utils';
import {screen} from '@testing-library/react';
import userEvent from '@testing-library/user-event';
import {describe, expect, it, vi} from 'vitest';
import {CommandButton} from './CommandButton';

describe('CommandButton', () => {
    it('renders the title', () => {
        renderWithProviders(<CommandButton title="PHPUnit" onClick={() => {}} />);
        expect(screen.getByText('PHPUnit')).toBeInTheDocument();
        expect(screen.getByRole('button', {name: /run phpunit/i})).toBeInTheDocument();
    });

    it('shows the description when provided', () => {
        renderWithProviders(<CommandButton title="PHPUnit" description="Run the unit test suite" onClick={() => {}} />);
        expect(screen.getByText('Run the unit test suite')).toBeInTheDocument();
    });

    it('shows a default hint when no description is provided', () => {
        renderWithProviders(<CommandButton title="PHPUnit" onClick={() => {}} />);
        expect(screen.getByText('Click to run')).toBeInTheDocument();
    });

    it('calls onClick when clicked', async () => {
        const user = userEvent.setup();
        const onClick = vi.fn();
        renderWithProviders(<CommandButton title="PHPUnit" onClick={onClick} />);
        await user.click(screen.getByRole('button', {name: /run phpunit/i}));
        expect(onClick).toHaveBeenCalledOnce();
    });

    it('is disabled while loading and exposes aria-busy', () => {
        renderWithProviders(<CommandButton title="PHPUnit" status="loading" onClick={() => {}} />);
        const button = screen.getByRole('button', {name: /run phpunit/i});
        expect(button).toBeDisabled();
        expect(button).toHaveAttribute('aria-busy', 'true');
    });

    it('is disabled when the disabled prop is true', () => {
        renderWithProviders(<CommandButton title="PHPUnit" disabled onClick={() => {}} />);
        expect(screen.getByRole('button', {name: /run phpunit/i})).toBeDisabled();
    });

    it('does not invoke onClick when disabled (pointer-events: none)', () => {
        const onClick = vi.fn();
        renderWithProviders(<CommandButton title="PHPUnit" disabled onClick={onClick} />);
        const button = screen.getByRole('button', {name: /run phpunit/i});
        expect(button).toBeDisabled();
        expect(getComputedStyle(button).pointerEvents).toBe('none');
    });
});
