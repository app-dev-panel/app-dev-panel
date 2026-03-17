import {renderWithProviders} from '@app-dev-panel/sdk/test-utils';
import {screen} from '@testing-library/react';
import {afterEach, beforeEach, describe, expect, it, vi} from 'vitest';
import {EntryFilterConfig, EntryFilterState, loadFilterState, saveFilterState} from './EntryFilterConfig';

// ---------------------------------------------------------------------------
// Pure function tests
// ---------------------------------------------------------------------------

describe('loadFilterState', () => {
    beforeEach(() => {
        localStorage.clear();
    });

    it('returns default state when localStorage is empty', () => {
        const state = loadFilterState();
        expect(state).toEqual({enabled: false, conditions: []});
    });

    it('handles invalid JSON gracefully', () => {
        localStorage.setItem('adp:entry-filter', '{not valid json!!!');
        const state = loadFilterState();
        expect(state).toEqual({enabled: false, conditions: []});
    });
});

describe('saveFilterState + loadFilterState round-trip', () => {
    beforeEach(() => {
        localStorage.clear();
    });

    it('round-trips correctly', () => {
        const state: EntryFilterState = {
            enabled: true,
            conditions: [{id: 'c1', field: 'url', operator: 'contains', value: '/api'}],
        };
        saveFilterState(state);
        const loaded = loadFilterState();
        expect(loaded).toEqual(state);
    });
});

// ---------------------------------------------------------------------------
// Component tests
// ---------------------------------------------------------------------------

describe('EntryFilterConfig', () => {
    let anchorEl: HTMLDivElement;

    beforeEach(() => {
        anchorEl = document.createElement('div');
        document.body.appendChild(anchorEl);
    });

    afterEach(() => {
        document.body.removeChild(anchorEl);
    });

    it('renders "Filter Conditions" header when open', () => {
        renderWithProviders(
            <EntryFilterConfig
                anchorEl={anchorEl}
                open={true}
                onClose={vi.fn()}
                filterState={{enabled: false, conditions: []}}
                onChange={vi.fn()}
            />,
        );
        expect(screen.getByText('Filter Conditions')).toBeInTheDocument();
    });

    it('renders "No filter conditions" when conditions array is empty', () => {
        renderWithProviders(
            <EntryFilterConfig
                anchorEl={anchorEl}
                open={true}
                onClose={vi.fn()}
                filterState={{enabled: false, conditions: []}}
                onChange={vi.fn()}
            />,
        );
        expect(screen.getByText('No filter conditions')).toBeInTheDocument();
    });

    it('renders condition rows when conditions are provided', () => {
        const filterState: EntryFilterState = {
            enabled: true,
            conditions: [
                {id: 'c1', field: 'url', operator: 'contains', value: '/api'},
                {id: 'c2', field: 'status', operator: 'equals', value: '200'},
            ],
        };
        renderWithProviders(
            <EntryFilterConfig
                anchorEl={anchorEl}
                open={true}
                onClose={vi.fn()}
                filterState={filterState}
                onChange={vi.fn()}
            />,
        );
        expect(screen.queryByText('No filter conditions')).not.toBeInTheDocument();
        expect(screen.getByText('2 conditions')).toBeInTheDocument();
    });

    it('renders "Add condition" button', () => {
        renderWithProviders(
            <EntryFilterConfig
                anchorEl={anchorEl}
                open={true}
                onClose={vi.fn()}
                filterState={{enabled: false, conditions: []}}
                onChange={vi.fn()}
            />,
        );
        expect(screen.getByText('Add condition')).toBeInTheDocument();
    });
});
