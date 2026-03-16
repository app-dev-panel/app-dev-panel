import {describe, expect, it} from 'vitest';
import {regexpQuote} from './regexpQuote';

describe('regexpQuote', () => {
    it('escapes special regex characters', () => {
        expect(regexpQuote('hello.world')).toBe('hello\\.world');
        expect(regexpQuote('a+b*c?')).toBe('a\\+b\\*c\\?');
        expect(regexpQuote('foo[bar]')).toBe('foo\\[bar\\]');
        expect(regexpQuote('(test)')).toBe('\\(test\\)');
        expect(regexpQuote('$100')).toBe('\\$100');
        expect(regexpQuote('^start')).toBe('\\^start');
        expect(regexpQuote('a|b')).toBe('a\\|b');
        expect(regexpQuote('path\\to')).toBe('path\\\\to');
    });

    it('returns plain strings unchanged', () => {
        expect(regexpQuote('hello')).toBe('hello');
        expect(regexpQuote('')).toBe('');
        expect(regexpQuote('abc123')).toBe('abc123');
    });

    it('produces valid regex patterns', () => {
        const input = 'file.name (v2.0) [final]';
        const pattern = new RegExp(regexpQuote(input));
        expect(pattern.test(input)).toBe(true);
        expect(pattern.test('file_name (v2.0) [final]')).toBe(false);
    });
});
