import {describe, expect, it} from 'vitest';
import {formatDate, formatMicrotime, formatMillisecondsAsDuration, formatWithMicrotime} from './formatDate';

describe('formatDate', () => {
    it('formats unix timestamp to readable date', () => {
        // 2024-01-15 12:30:45 UTC
        const result = formatDate(1705319445);
        expect(result).toMatch(/15th Jan/);
        expect(result).toMatch(/\d{2}:\d{2}:\d{2}/);
    });
});

describe('formatMicrotime', () => {
    it('formats timestamp with microseconds', () => {
        const result = formatMicrotime(1705319445.123456);
        expect(result).toMatch(/\d{2}:\d{2}:\d{2}\.123456/);
    });

    it('returns 0.000000 for falsy value', () => {
        expect(formatMicrotime(0)).toBe('0.000000');
    });

    it('pads microseconds to 6 digits', () => {
        const result = formatMicrotime(1705319445.12);
        expect(result).toMatch(/\.120000$/);
    });
});

describe('formatWithMicrotime', () => {
    it('formats with custom date format', () => {
        const result = formatWithMicrotime(1705319445.5, 'HH:mm:ss');
        expect(result).toMatch(/\d{2}:\d{2}:\d{2}\.500000/);
    });

    it('handles timestamps without decimal part', () => {
        const result = formatWithMicrotime(1705319445, 'HH:mm:ss');
        expect(result).toMatch(/^\d{2}:\d{2}:\d{2}$/);
    });
});

describe('formatMillisecondsAsDuration', () => {
    it('converts seconds to ms (multiplies by 1000)', () => {
        expect(formatMillisecondsAsDuration(0.001)).toBe('1.000 ms');
        expect(formatMillisecondsAsDuration(0.1)).toBe('100.000 ms');
        expect(formatMillisecondsAsDuration(0.0005)).toBe('0.500 ms');
    });

    it('handles zero', () => {
        expect(formatMillisecondsAsDuration(0)).toBe('0.000 ms');
    });
});
