import {describe, expect, it} from 'vitest';
import {fuzzyMatch} from './fuzzyMatch';

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
    });

    it('returns null for non-matching query', () => {
        expect(fuzzyMatch('/api/users', 'xyz')).toBeNull();
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

    it('returns null when query is longer than text', () => {
        expect(fuzzyMatch('ab', 'abcdef')).toBeNull();
    });

    it('handles single character query', () => {
        const result = fuzzyMatch('hello', 'h');
        expect(result).not.toBeNull();
        expect(result!.indices).toEqual([0]);
    });

    it('handles query equal to text', () => {
        const result = fuzzyMatch('hello', 'hello');
        expect(result).not.toBeNull();
        expect(result!.indices).toEqual([0, 1, 2, 3, 4]);
    });
});
