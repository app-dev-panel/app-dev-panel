import {describe, expect, it} from 'vitest';
import {buttonColorConsole, buttonColorHttp} from './buttonColor';

describe('buttonColorHttp', () => {
    it.each([
        [200, 'success'],
        [201, 'success'],
        [299, 'success'],
        [300, 'warning'],
        [301, 'warning'],
        [399, 'warning'],
        [400, 'error'],
        [404, 'error'],
        [500, 'error'],
        [503, 'error'],
        [100, 'info'],
        [199, 'info'],
    ])('returns %s for status %d', (status, expected) => {
        expect(buttonColorHttp(status)).toBe(expected);
    });
});

describe('buttonColorConsole', () => {
    it('returns success for exit code 0', () => {
        expect(buttonColorConsole(0)).toBe('success');
    });

    it('returns error for non-zero exit code', () => {
        expect(buttonColorConsole(1)).toBe('error');
        expect(buttonColorConsole(255)).toBe('error');
    });
});
