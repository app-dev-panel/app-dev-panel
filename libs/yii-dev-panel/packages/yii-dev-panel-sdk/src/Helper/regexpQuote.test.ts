import {describe, expect, it} from 'vitest';
import {regexpQuote} from './regexpQuote';

describe('regexpQuote', () => {
    it('escapes special regex characters', () => {
        expect(regexpQuote('hello.world')).toBe('hello\\.world');
        expect(regexpQuote('foo[bar]')).toBe('foo\\[bar\\]');
        expect(regexpQuote('a+b*c?')).toBe('a\\+b\\*c\\?');
        expect(regexpQuote('(test)')).toBe('\\(test\\)');
        expect(regexpQuote('$100')).toBe('\\$100');
        expect(regexpQuote('a^b')).toBe('a\\^b');
        expect(regexpQuote('a{1}')).toBe('a\\{1\\}');
        expect(regexpQuote('a|b')).toBe('a\\|b');
        expect(regexpQuote('path\\to\\file')).toBe('path\\\\to\\\\file');
    });

    it('leaves plain strings unchanged', () => {
        expect(regexpQuote('hello')).toBe('hello');
    });

    it('produces valid regex from escaped string', () => {
        const input = 'file.name+extra';
        const pattern = new RegExp(regexpQuote(input));
        expect(pattern.test(input)).toBe(true);
        expect(pattern.test('fileXnameXextra')).toBe(false);
    });
});
