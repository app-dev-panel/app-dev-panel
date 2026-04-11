import {describe, expect, it} from 'vitest';
import {extractErrorMessage, formatQueryError} from './extractErrorMessage';

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

describe('formatQueryError', () => {
    it('returns extracted message when available', () => {
        expect(formatQueryError({data: {error: 'Backend exploded'}})).toBe('Backend exploded');
    });

    it('returns connection message for FETCH_ERROR', () => {
        expect(formatQueryError({status: 'FETCH_ERROR'})).toBe(
            'Unable to connect to the server. Make sure the application is running.',
        );
    });

    it('returns connection message for TIMEOUT_ERROR', () => {
        expect(formatQueryError({status: 'TIMEOUT_ERROR'})).toBe(
            'Unable to connect to the server. Make sure the application is running.',
        );
    });

    it('appends HTTP status code to fallback when status is numeric', () => {
        expect(formatQueryError({status: 500}, 'Failed to fetch routes.')).toBe('Failed to fetch routes. (HTTP 500)');
    });

    it('returns fallback for unknown error shapes', () => {
        expect(formatQueryError({foo: 'bar'}, 'Could not load.')).toBe('Could not load.');
    });

    it('uses default fallback when none provided', () => {
        expect(formatQueryError(null)).toBe('Failed to load data.');
    });

    it('returns SerializedError message when present', () => {
        expect(formatQueryError({name: 'TypeError', message: 'Cannot read property of undefined'})).toBe(
            'Cannot read property of undefined',
        );
    });

    it('returns SerializedError name when message is empty', () => {
        expect(formatQueryError({name: 'AbortError', message: ''}, 'Failed to fetch.')).toBe('AbortError');
    });

    it('prefers transport status over SerializedError shape', () => {
        expect(formatQueryError({status: 'FETCH_ERROR', message: 'irrelevant'})).toBe(
            'Unable to connect to the server. Make sure the application is running.',
        );
    });
});
