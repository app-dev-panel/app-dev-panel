import {describe, expect, it} from 'vitest';
import {isTagString} from './tagMatcher';

describe('isTagString', () => {
    it.each([
        ['tag@app.service', true],
        ['tag@myTag', true],
        ['tag@a.b.c.d', true],
        ['notag@test', false],
        ['tag@', false],
        ['tag', false],
        ['', false],
    ])('isTagString(%s) === %s', (input, expected) => {
        expect(isTagString(input)).toBe(expected);
    });
});
