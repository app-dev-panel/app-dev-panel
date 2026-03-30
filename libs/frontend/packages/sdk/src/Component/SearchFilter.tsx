import {FilterList} from '@mui/icons-material';
import {InputAdornment, TextField, type TextFieldProps} from '@mui/material';
import {useDeferredValue, useEffect, useMemo, useState} from 'react';
import {type FuzzyMatch, fuzzyMatch} from '../Helper/fuzzyMatch';
import {searchVariants} from '../Helper/layoutTranslit';

// ---------------------------------------------------------------------------
// Types
// ---------------------------------------------------------------------------

export type SearchMatch<T> = {item: T; score: number; indices: number[]};

export type SearchMode = 'includes' | 'fuzzy';

type UseSearchFilterOptions<T> = {
    items: T[];
    query: string;
    getSearchText: (item: T) => string | string[];
    mode?: SearchMode;
};

// ---------------------------------------------------------------------------
// Hook: useSearchFilter
// ---------------------------------------------------------------------------

/**
 * Filters items using layout-aware search (QWERTY ↔ ЙЦУКЕН).
 *
 * Supports two modes:
 * - `'includes'` (default): case-insensitive substring match
 * - `'fuzzy'`: fuzzy character matching with scoring (lower score = better match)
 *
 * Results are sorted by score in fuzzy mode.
 *
 * @example
 * ```tsx
 * const results = useSearchFilter({
 *     items: logs,
 *     query: filter,
 *     getSearchText: (log) => log.message,
 * });
 * ```
 *
 * @example
 * ```tsx
 * // Multiple search fields
 * const results = useSearchFilter({
 *     items: entries,
 *     query: filter,
 *     getSearchText: (entry) => [entry.url, entry.method],
 *     mode: 'fuzzy',
 * });
 * ```
 */
export function useSearchFilter<T>({
    items,
    query,
    getSearchText,
    mode = 'includes',
}: UseSearchFilterOptions<T>): SearchMatch<T>[] {
    return useMemo(() => {
        const trimmed = query.trim();
        if (!trimmed) {
            return items.map((item) => ({item, score: 0, indices: []}));
        }

        const variants = searchVariants(trimmed.toLowerCase());

        if (mode === 'fuzzy') {
            return filterFuzzy(items, variants, getSearchText);
        }

        return filterIncludes(items, variants, getSearchText);
    }, [items, query, getSearchText, mode]);
}

function filterIncludes<T>(
    items: T[],
    variants: string[],
    getSearchText: (item: T) => string | string[],
): SearchMatch<T>[] {
    const results: SearchMatch<T>[] = [];

    for (const item of items) {
        const fields = normalizeFields(getSearchText(item));
        const matched = fields.some((field) => {
            const lower = field.toLowerCase();
            return variants.some((v) => lower.includes(v));
        });

        if (matched) {
            results.push({item, score: 0, indices: []});
        }
    }

    return results;
}

function filterFuzzy<T>(
    items: T[],
    variants: string[],
    getSearchText: (item: T) => string | string[],
): SearchMatch<T>[] {
    const results: SearchMatch<T>[] = [];

    for (const item of items) {
        const fields = normalizeFields(getSearchText(item));
        let bestMatch: FuzzyMatch | null = null;

        for (const field of fields) {
            for (const variant of variants) {
                const match = fuzzyMatch(field, variant);
                if (match && (bestMatch === null || match.score < bestMatch.score)) {
                    bestMatch = match;
                }
            }
        }

        if (bestMatch) {
            results.push({item, score: bestMatch.score, indices: bestMatch.indices});
        }
    }

    results.sort((a, b) => a.score - b.score);
    return results;
}

function normalizeFields(value: string | string[]): string[] {
    return Array.isArray(value) ? value : [value];
}

// ---------------------------------------------------------------------------
// Component: SearchFilter
// ---------------------------------------------------------------------------

type SearchFilterProps<T> = {
    items: T[];
    getSearchText: (item: T) => string | string[];
    mode?: SearchMode;
    placeholder?: string;
    onChange: (results: SearchMatch<T>[], query: string) => void;
    inputProps?: Partial<TextFieldProps>;
};

/**
 * Self-contained search filter component.
 *
 * Manages its own query state with `useDeferredValue` for smooth typing.
 * Calls `onChange` with filtered results whenever the query or items change.
 *
 * @example
 * ```tsx
 * <SearchFilter
 *     items={logs}
 *     getSearchText={(log) => log.message}
 *     placeholder="Filter logs..."
 *     onChange={(results) => setFiltered(results)}
 * />
 * ```
 */
export function SearchFilter<T>({
    items,
    getSearchText,
    mode = 'includes',
    placeholder = 'Filter...',
    onChange,
    inputProps,
}: SearchFilterProps<T>) {
    const [query, setQuery] = useState('');
    const deferredQuery = useDeferredValue(query);

    const results = useSearchFilter({items, query: deferredQuery, getSearchText, mode});

    useEffect(() => {
        onChange(results, deferredQuery);
    }, [results, deferredQuery, onChange]);

    return (
        <TextField
            size="small"
            placeholder={placeholder}
            value={query}
            onChange={(e) => setQuery(e.target.value)}
            InputProps={{
                startAdornment: (
                    <InputAdornment position="start">
                        <FilterList sx={{fontSize: 14, color: 'text.disabled'}} />
                    </InputAdornment>
                ),
            }}
            sx={{
                width: 180,
                '& .MuiOutlinedInput-root': {fontSize: '12px', height: 26, borderRadius: 0.75},
                '& .MuiInputAdornment-root': {mr: 0},
            }}
            {...inputProps}
        />
    );
}
