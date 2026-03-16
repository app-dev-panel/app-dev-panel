import {describe, expect, it} from 'vitest';
import {formatBytes} from './formatBytes';

describe('formatBytes', () => {
    it.each([
        [0, '0 B'],
        [100, '100 B'],
        [1024, '1 KB'],
        [1536, '1.5 KB'],
        [1048576, '1 MB'],
        [1073741824, '1 GB'],
    ])('formats %d as %s', (bytes, expected) => {
        expect(formatBytes(bytes)).toBe(expected);
    });

    it('respects custom decimals', () => {
        expect(formatBytes(1536, 0)).toBe('2 KB');
        expect(formatBytes(1536, 3)).toBe('1.5 KB');
    });
});
