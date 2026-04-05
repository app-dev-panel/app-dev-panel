import {describe, expect, it} from 'vitest';
import {extractErrorMessage} from './extractErrorMessage';

describe('extractErrorMessage', () => {
    it('returns error string from {data: {error: "msg"}}', () => {
        expect(extractErrorMessage({data: {error: 'Something went wrong'}})).toBe('Something went wrong');
    });

    it('returns error string from nested {data: {data: {error: "msg"}}}', () => {
        expect(extractErrorMessage({data: {data: {error: 'Nested error'}}})).toBe('Nested error');
    });

    it('prefers top-level error over nested error', () => {
        expect(extractErrorMessage({data: {error: 'Top', data: {error: 'Nested'}}})).toBe('Top');
    });

    it('returns null for non-object input', () => {
        expect(extractErrorMessage('string')).toBeNull();
        expect(extractErrorMessage(42)).toBeNull();
        expect(extractErrorMessage(true)).toBeNull();
    });

    it('returns null for missing error field', () => {
        expect(extractErrorMessage({data: {message: 'no error key'}})).toBeNull();
    });

    it('returns null for null input', () => {
        expect(extractErrorMessage(null)).toBeNull();
    });

    it('returns null for undefined input', () => {
        expect(extractErrorMessage(undefined)).toBeNull();
    });

    it('returns null when data is not an object', () => {
        expect(extractErrorMessage({data: 'just a string'})).toBeNull();
    });
});
