import {render, screen, waitFor} from '@testing-library/react';
import userEvent from '@testing-library/user-event';
import {describe, expect, it, vi} from 'vitest';
import {renderWithProviders} from '../test-utils';
import {SearchFilter, type SearchMatch, useSearchFilter} from './SearchFilter';

// ---------------------------------------------------------------------------
// Helper: render hook via a test component
// ---------------------------------------------------------------------------

type Item = {id: number; text: string};

const items: Item[] = [
    {id: 1, text: 'Hello world'},
    {id: 2, text: 'Goodbye world'},
    {id: 3, text: 'Foo bar baz'},
    {id: 4, text: 'React TypeScript'},
    {id: 5, text: 'Material UI'},
];

function HookTester({
    items,
    query,
    getSearchText,
    mode,
}: {
    items: Item[];
    query: string;
    getSearchText: (item: Item) => string | string[];
    mode?: 'includes' | 'fuzzy';
}) {
    const results = useSearchFilter({items, query, getSearchText, mode});
    return (
        <div>
            <span data-testid="count">{results.length}</span>
            {results.map((r, i) => (
                <div key={i} data-testid={`result-${i}`} data-score={r.score} data-indices={r.indices.join(',')}>
                    {r.item.text}
                </div>
            ))}
        </div>
    );
}

// ---------------------------------------------------------------------------
// useSearchFilter — includes mode
// ---------------------------------------------------------------------------

describe('useSearchFilter (includes)', () => {
    const getText = (item: Item) => item.text;

    it('returns all items when query is empty', () => {
        render(<HookTester items={items} query="" getSearchText={getText} />);
        expect(screen.getByTestId('count').textContent).toBe('5');
    });

    it('returns all items when query is whitespace', () => {
        render(<HookTester items={items} query="   " getSearchText={getText} />);
        expect(screen.getByTestId('count').textContent).toBe('5');
    });

    it('filters items by substring match', () => {
        render(<HookTester items={items} query="world" getSearchText={getText} />);
        expect(screen.getByTestId('count').textContent).toBe('2');
        expect(screen.getByText('Hello world')).toBeInTheDocument();
        expect(screen.getByText('Goodbye world')).toBeInTheDocument();
    });

    it('is case-insensitive', () => {
        render(<HookTester items={items} query="HELLO" getSearchText={getText} />);
        expect(screen.getByTestId('count').textContent).toBe('1');
        expect(screen.getByText('Hello world')).toBeInTheDocument();
    });

    it('returns empty when nothing matches', () => {
        render(<HookTester items={items} query="zzzzz" getSearchText={getText} />);
        expect(screen.getByTestId('count').textContent).toBe('0');
    });

    it('supports layout-aware search (Russian keyboard)', () => {
        // "рудд" on Russian layout = "hell" on English layout
        render(<HookTester items={items} query="рудд" getSearchText={getText} />);
        expect(screen.getByTestId('count').textContent).toBe('1');
        expect(screen.getByText('Hello world')).toBeInTheDocument();
    });

    it('supports multiple search fields', () => {
        const getMultiText = (item: Item) => [item.text, `id:${item.id}`];
        render(<HookTester items={items} query="id:3" getSearchText={getMultiText} />);
        expect(screen.getByTestId('count').textContent).toBe('1');
        expect(screen.getByText('Foo bar baz')).toBeInTheDocument();
    });
});

// ---------------------------------------------------------------------------
// useSearchFilter — fuzzy mode
// ---------------------------------------------------------------------------

describe('useSearchFilter (fuzzy)', () => {
    const getText = (item: Item) => item.text;

    it('returns all items when query is empty', () => {
        render(<HookTester items={items} query="" getSearchText={getText} mode="fuzzy" />);
        expect(screen.getByTestId('count').textContent).toBe('5');
    });

    it('fuzzy-matches items', () => {
        render(<HookTester items={items} query="hw" getSearchText={getText} mode="fuzzy" />);
        // "hw" matches "Hello world" (H...w) — first char h and w
        expect(Number(screen.getByTestId('count').textContent)).toBeGreaterThanOrEqual(1);
        expect(screen.getByText('Hello world')).toBeInTheDocument();
    });

    it('returns empty for non-matching fuzzy query', () => {
        render(<HookTester items={items} query="zzz" getSearchText={getText} mode="fuzzy" />);
        expect(screen.getByTestId('count').textContent).toBe('0');
    });

    it('sorts results by score (best match first)', () => {
        const testItems: Item[] = [
            {id: 1, text: 'a___b___c'}, // sparse match for "abc" — high gap penalty
            {id: 2, text: 'abc_def'}, // exact substring — low score
        ];
        render(<HookTester items={testItems} query="abc" getSearchText={getText} mode="fuzzy" />);
        expect(screen.getByTestId('count').textContent).toBe('2');
        // The exact match should come first (lower score)
        expect(screen.getByTestId('result-0').textContent).toBe('abc_def');
        expect(screen.getByTestId('result-1').textContent).toBe('a___b___c');
    });

    it('provides match indices', () => {
        const testItems: Item[] = [{id: 1, text: 'abcdef'}];
        render(<HookTester items={testItems} query="ace" getSearchText={getText} mode="fuzzy" />);
        expect(screen.getByTestId('result-0').getAttribute('data-indices')).toBe('0,2,4');
    });

    it('supports layout-aware fuzzy search', () => {
        // "ашщ" on Russian layout = "foo" on English layout? Let's check: а→f, ш→i, щ→o
        // Actually: а→f, щ→o, щ→o. Let me use a known mapping: "ифк" → "bar" (и→b, а→f... no)
        // и→b, ф→a, к→r => "ифк" → "bar"
        render(<HookTester items={items} query="ифк" getSearchText={getText} mode="fuzzy" />);
        // Should match "Foo bar baz" via transliterated "bar"
        expect(screen.getByText('Foo bar baz')).toBeInTheDocument();
    });
});

// ---------------------------------------------------------------------------
// SearchFilter component
// ---------------------------------------------------------------------------

describe('SearchFilter component', () => {
    it('renders input with placeholder', () => {
        renderWithProviders(
            <SearchFilter
                items={items}
                getSearchText={(item) => item.text}
                placeholder="Search items..."
                onChange={vi.fn()}
            />,
        );
        expect(screen.getByPlaceholderText('Search items...')).toBeInTheDocument();
    });

    it('renders with default placeholder', () => {
        renderWithProviders(<SearchFilter items={items} getSearchText={(item) => item.text} onChange={vi.fn()} />);
        expect(screen.getByPlaceholderText('Filter...')).toBeInTheDocument();
    });

    it('calls onChange with all items initially', async () => {
        const onChange = vi.fn();
        renderWithProviders(<SearchFilter items={items} getSearchText={(item) => item.text} onChange={onChange} />);
        await waitFor(() => {
            expect(onChange).toHaveBeenCalled();
            const [results] = onChange.mock.calls[onChange.mock.calls.length - 1];
            expect(results).toHaveLength(5);
        });
    });

    it('filters results when user types', async () => {
        const user = userEvent.setup();
        const onChange = vi.fn();
        renderWithProviders(<SearchFilter items={items} getSearchText={(item) => item.text} onChange={onChange} />);
        const input = screen.getByPlaceholderText('Filter...');
        await user.type(input, 'world');

        await waitFor(() => {
            const lastCall = onChange.mock.calls[onChange.mock.calls.length - 1];
            const results: SearchMatch<Item>[] = lastCall[0];
            const query: string = lastCall[1];
            expect(query).toBe('world');
            expect(results).toHaveLength(2);
            expect(results.map((r) => r.item.text).sort()).toEqual(['Goodbye world', 'Hello world']);
        });
    });

    it('filters results in fuzzy mode', async () => {
        const user = userEvent.setup();
        const onChange = vi.fn();
        renderWithProviders(
            <SearchFilter items={items} getSearchText={(item) => item.text} mode="fuzzy" onChange={onChange} />,
        );
        const input = screen.getByPlaceholderText('Filter...');
        await user.type(input, 'fbb');

        await waitFor(() => {
            const lastCall = onChange.mock.calls[onChange.mock.calls.length - 1];
            const results: SearchMatch<Item>[] = lastCall[0];
            // "fbb" fuzzy matches "Foo bar baz" (f...b...b)
            expect(results.length).toBeGreaterThanOrEqual(1);
            expect(results[0].item.text).toBe('Foo bar baz');
        });
    });

    it('passes query string to onChange callback', async () => {
        const user = userEvent.setup();
        const onChange = vi.fn();
        renderWithProviders(<SearchFilter items={items} getSearchText={(item) => item.text} onChange={onChange} />);
        const input = screen.getByPlaceholderText('Filter...');
        await user.type(input, 'test');

        await waitFor(() => {
            const lastCall = onChange.mock.calls[onChange.mock.calls.length - 1];
            expect(lastCall[1]).toBe('test');
        });
    });
});
