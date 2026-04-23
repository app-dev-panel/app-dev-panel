import {renderWithProviders} from '@app-dev-panel/sdk/test-utils';
import {fireEvent, screen} from '@testing-library/react';
import {describe, expect, it, vi} from 'vitest';
import {EvictedEntryState} from './Layout';

describe('EvictedEntryState', () => {
    const baseProps = {
        entryId: '69e90567cc523642177752',
        error: {status: 404, data: {error: "Requested collector doesn't exist: TimelineCollector"}},
        onOpenLatest: vi.fn(),
        onViewAllEntries: vi.fn(),
    };

    it('shows the eviction headline and the affected entry id', () => {
        renderWithProviders(<EvictedEntryState {...baseProps} latestEntryId="abc123" />);
        expect(screen.getByText('Debug entry is no longer available')).toBeInTheDocument();
        expect(screen.getByText(/69e90567cc523642177752/)).toBeInTheDocument();
    });

    it('surfaces the server error message when present', () => {
        renderWithProviders(<EvictedEntryState {...baseProps} latestEntryId="abc123" />);
        expect(screen.getByText(/Requested collector doesn't exist/)).toBeInTheDocument();
    });

    it('offers "Open latest entry" when another entry exists and calls the handler', () => {
        const onOpenLatest = vi.fn();
        renderWithProviders(<EvictedEntryState {...baseProps} latestEntryId="abc123" onOpenLatest={onOpenLatest} />);
        const button = screen.getByRole('button', {name: 'Open latest entry'});
        fireEvent.click(button);
        expect(onOpenLatest).toHaveBeenCalledTimes(1);
    });

    it('hides "Open latest entry" when the latest id equals the evicted id', () => {
        renderWithProviders(<EvictedEntryState {...baseProps} latestEntryId="69e90567cc523642177752" />);
        expect(screen.queryByRole('button', {name: 'Open latest entry'})).not.toBeInTheDocument();
        expect(screen.getByRole('button', {name: 'View all entries'})).toBeInTheDocument();
    });

    it('hides "Open latest entry" when no other entries are available', () => {
        renderWithProviders(<EvictedEntryState {...baseProps} latestEntryId={undefined} />);
        expect(screen.queryByRole('button', {name: 'Open latest entry'})).not.toBeInTheDocument();
        expect(
            screen.getByText('This debug entry was evicted from storage and no other entries are available yet.'),
        ).toBeInTheDocument();
    });

    it('calls the all-entries handler when its button is clicked', () => {
        const onViewAllEntries = vi.fn();
        renderWithProviders(
            <EvictedEntryState {...baseProps} latestEntryId="abc123" onViewAllEntries={onViewAllEntries} />,
        );
        fireEvent.click(screen.getByRole('button', {name: 'View all entries'}));
        expect(onViewAllEntries).toHaveBeenCalledTimes(1);
    });
});
