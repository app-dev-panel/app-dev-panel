import {describe, expect, it} from 'vitest';
import {formatBytes} from './formatBytes';

describe('formatBytes', () => {
    it('formats zero bytes', () => {
        expect(formatBytes(0)).toBe('0 B');
    });

    it('formats bytes', () => {
        expect(formatBytes(500)).toBe('500 B');
        expect(formatBytes(1023)).toBe('1023 B');
    });

    it('formats kilobytes', () => {
        expect(formatBytes(1024)).toBe('1 KB');
        expect(formatBytes(1536)).toBe('1.5 KB');
    });

    it('formats megabytes', () => {
        expect(formatBytes(1048576)).toBe('1 MB');
        expect(formatBytes(1572864)).toBe('1.5 MB');
    });

    it('formats gigabytes', () => {
        expect(formatBytes(1073741824)).toBe('1 GB');
    });

    it('respects decimals parameter', () => {
        expect(formatBytes(1536, 0)).toBe('2 KB');
        expect(formatBytes(1536, 1)).toBe('1.5 KB');
        expect(formatBytes(1536, 3)).toBe('1.5 KB');
    });
});
