import {describe, expect, it} from 'vitest';
import {searchVariants, translit} from './layoutTranslit';

describe('translit', () => {
    it('converts Russian ЙЦУКЕН to English QWERTY', () => {
        expect(translit('ашдеук')).toBe('filter');
        expect(translit('йцу')).toBe('qwe');
    });

    it('converts English QWERTY to Russian ЙЦУКЕН', () => {
        // f→а, i→ш, l→д, t→е, e→у, r→к
        expect(translit('filter')).toBe('ашдеук');
    });

    it('handles uppercase characters', () => {
        expect(translit('Ашдеук')).toBe('Filter');
        expect(translit('Filter')).toBe('Ашдеук');
    });

    it('preserves characters not in either mapping', () => {
        expect(translit('123')).toBe('123');
    });

    it('handles empty string', () => {
        expect(translit('')).toBe('');
    });

    it('round-trips back to original', () => {
        expect(translit(translit('hello'))).toBe('hello');
        expect(translit(translit('привет'))).toBe('привет');
    });

    it('converts common words correctly', () => {
        // "ghbdtn" typed on English layout = "привет" on Russian layout
        expect(translit('ghbdtn')).toBe('привет');
        expect(translit('привет')).toBe('ghbdtn');
    });
});

describe('searchVariants', () => {
    it('returns two variants for Russian input', () => {
        const variants = searchVariants('ашдеук');
        expect(variants).toEqual(['ашдеук', 'filter']);
    });

    it('returns two variants for English input', () => {
        const variants = searchVariants('filter');
        expect(variants).toEqual(['filter', 'ашдеук']);
    });

    it('returns single variant for neutral input', () => {
        const variants = searchVariants('123');
        expect(variants).toEqual(['123']);
    });

    it('returns single variant for empty string', () => {
        const variants = searchVariants('');
        expect(variants).toEqual(['']);
    });
});
