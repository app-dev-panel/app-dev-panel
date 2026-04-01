import {describe, expect, it} from 'vitest';
import {extractCommandError} from './extractCommandError';

describe('extractCommandError', () => {
    it('returns null for successful response', () => {
        const result = extractCommandError({data: {status: 'ok', result: [], errors: []}});
        expect(result).toBeNull();
    });

    it('returns errors for status "error"', () => {
        const result = extractCommandError({
            data: {status: 'error', result: [], errors: ['Something went wrong', 'Another error']},
        });
        expect(result).toEqual({errors: ['Something went wrong', 'Another error']});
    });

    it('returns errors for status "fail"', () => {
        const result = extractCommandError({data: {status: 'fail', result: null, errors: ['Command crashed']}});
        expect(result).toEqual({errors: ['Command crashed']});
    });

    it('returns fallback message when status is error but errors array is empty', () => {
        const result = extractCommandError({data: {status: 'error', result: null, errors: []}});
        expect(result).toEqual({errors: ['Command finished with status "error"']});
    });

    it('returns fallback message when status is fail but errors array is empty', () => {
        const result = extractCommandError({data: {status: 'fail', result: null, errors: []}});
        expect(result).toEqual({errors: ['Command finished with status "fail"']});
    });

    it('handles FETCH_ERROR', () => {
        const result = extractCommandError({error: {status: 'FETCH_ERROR', error: 'Network error'}});
        expect(result).toEqual({errors: ['Network error']});
    });

    it('handles FETCH_ERROR without error message', () => {
        const result = extractCommandError({error: {status: 'FETCH_ERROR'}});
        expect(result).toEqual({errors: ['Unable to reach the server. Check that the backend is running.']});
    });

    it('handles HTTP error with message in data', () => {
        const result = extractCommandError({error: {status: 500, data: {message: 'Internal server error'}}});
        expect(result).toEqual({errors: ['Internal server error']});
    });

    it('handles HTTP error with error field in data', () => {
        const result = extractCommandError({error: {status: 400, data: {error: 'Bad request'}}});
        expect(result).toEqual({errors: ['Bad request']});
    });

    it('handles HTTP error with numeric status and no data message', () => {
        const result = extractCommandError({error: {status: 503, data: {}}});
        expect(result).toEqual({errors: ['Request failed with status 503']});
    });

    it('handles unknown error shape', () => {
        const result = extractCommandError({error: 'some string error'});
        expect(result).toEqual({errors: ['An unexpected error occurred']});
    });
});
