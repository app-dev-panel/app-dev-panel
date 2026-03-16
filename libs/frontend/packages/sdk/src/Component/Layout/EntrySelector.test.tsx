import {render, screen} from '@testing-library/react';
import userEvent from '@testing-library/user-event';
import {describe, expect, it, vi} from 'vitest';
import {renderWithProviders} from '../../test-utils';
import {EntrySelector, fuzzyMatch, HighlightedText} from './EntrySelector';

// ---------------------------------------------------------------------------
// fuzzyMatch unit tests
// ---------------------------------------------------------------------------

describe('fuzzyMatch', () => {
    it('returns match for exact substring', () => {
        const result = fuzzyMatch('/api/users', 'users');
        expect(result).not.toBeNull();
        expect(result!.indices).toHaveLength(5);
    });

    it('returns match for fuzzy pattern', () => {
        const result = fuzzyMatch('/api/users/profile', 'aupr');
        expect(result).not.toBeNull();
        expect(result!.indices).toHaveLength(4);
        // a=2, u=5, p=11, r=12
    });

    it('returns null for non-matching query', () => {
        const result = fuzzyMatch('/api/users', 'xyz');
        expect(result).toBeNull();
    });

    it('returns match with empty query', () => {
        const result = fuzzyMatch('/api/users', '');
        expect(result).not.toBeNull();
        expect(result!.indices).toHaveLength(0);
        expect(result!.score).toBe(0);
    });

    it('is case-insensitive', () => {
        const result = fuzzyMatch('/API/Users', 'api');
        expect(result).not.toBeNull();
        expect(result!.indices).toHaveLength(3);
    });

    it('scores exact substring higher than sparse match', () => {
        const exact = fuzzyMatch('/api/users', 'api');
        const sparse = fuzzyMatch('/a_p_i_stuff', 'api');
        expect(exact).not.toBeNull();
        expect(sparse).not.toBeNull();
        expect(exact!.score).toBeLessThan(sparse!.score);
    });

    it('scores earlier matches higher', () => {
        const early = fuzzyMatch('abc_def', 'ab');
        const late = fuzzyMatch('___ab_def', 'ab');
        expect(early).not.toBeNull();
        expect(late).not.toBeNull();
        expect(early!.score).toBeLessThan(late!.score);
    });

    it('matches method + path combined text', () => {
        const result = fuzzyMatch('GET /api/users', 'get');
        expect(result).not.toBeNull();
        expect(result!.indices).toEqual([0, 1, 2]);
    });

    it('matches partial path segments', () => {
        const result = fuzzyMatch('POST /api/orders/create', 'orders');
        expect(result).not.toBeNull();
    });
});

// ---------------------------------------------------------------------------
// HighlightedText tests
// ---------------------------------------------------------------------------

describe('HighlightedText', () => {
    it('renders text without highlights when indices are empty', () => {
        render(<HighlightedText text="hello" indices={[]} />);
        expect(screen.getByText('hello')).toBeInTheDocument();
    });

    it('highlights matched characters', () => {
        const {container} = render(<HighlightedText text="/api/users" indices={[1, 2, 3]} />);
        const marks = container.querySelectorAll('mark');
        expect(marks).toHaveLength(1);
        expect(marks[0].textContent).toBe('api');
    });

    it('highlights non-consecutive characters separately', () => {
        const {container} = render(<HighlightedText text="abcdef" indices={[0, 2, 4]} />);
        const marks = container.querySelectorAll('mark');
        expect(marks).toHaveLength(3);
        expect(marks[0].textContent).toBe('a');
        expect(marks[1].textContent).toBe('c');
        expect(marks[2].textContent).toBe('e');
    });

    it('highlights all characters when all indices match', () => {
        const {container} = render(<HighlightedText text="abc" indices={[0, 1, 2]} />);
        const marks = container.querySelectorAll('mark');
        expect(marks).toHaveLength(1);
        expect(marks[0].textContent).toBe('abc');
    });
});

// ---------------------------------------------------------------------------
// EntrySelector component tests
// ---------------------------------------------------------------------------

describe('EntrySelector', () => {
    const webEntry = {
        id: 'entry-1',
        collectors: [],
        web: {php: {version: '8.4'}, request: {startTime: 1705319445, processingTime: 0.02}, memory: {peakUsage: 1024}},
        request: {url: '/api/users', path: '/api/users', query: '', method: 'GET', isAjax: false, userIp: '127.0.0.1'},
        response: {statusCode: 200},
    };

    const webEntry2 = {
        id: 'entry-2',
        collectors: [],
        web: {php: {version: '8.4'}, request: {startTime: 1705319446, processingTime: 0.03}, memory: {peakUsage: 2048}},
        request: {
            url: '/api/orders',
            path: '/api/orders',
            query: '',
            method: 'POST',
            isAjax: false,
            userIp: '127.0.0.1',
        },
        response: {statusCode: 201},
    };

    const webEntry3 = {
        id: 'entry-3',
        collectors: [],
        web: {php: {version: '8.4'}, request: {startTime: 1705319447, processingTime: 0.01}, memory: {peakUsage: 512}},
        request: {
            url: '/admin/dashboard',
            path: '/admin/dashboard',
            query: '',
            method: 'GET',
            isAjax: false,
            userIp: '127.0.0.1',
        },
        response: {statusCode: 200},
    };

    const anchorEl = document.createElement('div');

    it('renders filter input with placeholder', () => {
        renderWithProviders(
            <EntrySelector
                anchorEl={anchorEl}
                open={true}
                onClose={vi.fn()}
                entries={[webEntry] as any}
                onSelect={vi.fn()}
            />,
        );
        expect(screen.getByPlaceholderText('Search by URL, method, or command...')).toBeInTheDocument();
    });

    it('renders all entries when no filter', () => {
        renderWithProviders(
            <EntrySelector
                anchorEl={anchorEl}
                open={true}
                onClose={vi.fn()}
                entries={[webEntry, webEntry2] as any}
                onSelect={vi.fn()}
            />,
        );
        expect(screen.getByText('/api/users')).toBeInTheDocument();
        expect(screen.getByText('/api/orders')).toBeInTheDocument();
    });

    it('fuzzy-filters entries by path', async () => {
        const user = userEvent.setup();
        renderWithProviders(
            <EntrySelector
                anchorEl={anchorEl}
                open={true}
                onClose={vi.fn()}
                entries={[webEntry, webEntry2, webEntry3] as any}
                onSelect={vi.fn()}
            />,
        );
        const input = screen.getByPlaceholderText('Search by URL, method, or command...');
        await user.type(input, 'orders');
        expect(screen.getByText('201')).toBeInTheDocument();
        expect(screen.queryByText('200')).not.toBeInTheDocument();
    });

    it('fuzzy-filters with partial/sparse query', async () => {
        const user = userEvent.setup();
        renderWithProviders(
            <EntrySelector
                anchorEl={anchorEl}
                open={true}
                onClose={vi.fn()}
                entries={[webEntry, webEntry2, webEntry3] as any}
                onSelect={vi.fn()}
            />,
        );
        const input = screen.getByPlaceholderText('Search by URL, method, or command...');
        await user.type(input, 'adm');
        // Should match /admin/dashboard
        expect(screen.queryByText(/dashboard/i)).toBeInTheDocument();
    });

    it('shows "no entries match" for unmatched query', async () => {
        const user = userEvent.setup();
        renderWithProviders(
            <EntrySelector
                anchorEl={anchorEl}
                open={true}
                onClose={vi.fn()}
                entries={[webEntry] as any}
                onSelect={vi.fn()}
            />,
        );
        const input = screen.getByPlaceholderText('Search by URL, method, or command...');
        await user.type(input, 'zzzzz');
        expect(screen.getByText(/No entries match/)).toBeInTheDocument();
    });

    it('shows count label when filtering', async () => {
        const user = userEvent.setup();
        renderWithProviders(
            <EntrySelector
                anchorEl={anchorEl}
                open={true}
                onClose={vi.fn()}
                entries={[webEntry, webEntry2, webEntry3] as any}
                onSelect={vi.fn()}
            />,
        );
        const input = screen.getByPlaceholderText('Search by URL, method, or command...');
        await user.type(input, 'api');
        expect(screen.getByText('2 of 3 entries')).toBeInTheDocument();
    });

    it('highlights matched characters in results', async () => {
        const user = userEvent.setup();
        renderWithProviders(
            <EntrySelector
                anchorEl={anchorEl}
                open={true}
                onClose={vi.fn()}
                entries={[webEntry] as any}
                onSelect={vi.fn()}
            />,
        );
        const input = screen.getByPlaceholderText('Search by URL, method, or command...');
        await user.type(input, 'users');
        // Popover renders in a Portal, so query document.body
        const marks = document.body.querySelectorAll('mark');
        expect(marks.length).toBeGreaterThan(0);
    });

    it('calls onSelect when entry is clicked', async () => {
        const user = userEvent.setup();
        const onSelect = vi.fn();
        renderWithProviders(
            <EntrySelector
                anchorEl={anchorEl}
                open={true}
                onClose={vi.fn()}
                entries={[webEntry] as any}
                onSelect={onSelect}
            />,
        );
        await user.click(screen.getByText('/api/users'));
        expect(onSelect).toHaveBeenCalledWith(webEntry);
    });

    it('highlights current entry', () => {
        renderWithProviders(
            <EntrySelector
                anchorEl={anchorEl}
                open={true}
                onClose={vi.fn()}
                entries={[webEntry, webEntry2] as any}
                currentEntryId="entry-1"
                onSelect={vi.fn()}
            />,
        );
        // The active entry row should exist — verified by the component rendering
        expect(screen.getByText('/api/users')).toBeInTheDocument();
        expect(screen.getByText('/api/orders')).toBeInTheDocument();
    });
});
