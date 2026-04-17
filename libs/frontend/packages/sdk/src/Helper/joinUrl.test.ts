import {describe, expect, it} from 'vitest';
import {joinUrl} from './joinUrl';

describe('joinUrl', () => {
    it('concatenates base without trailing slash and path with leading slash', () => {
        expect(joinUrl('http://localhost:8080', '/debug/api')).toBe('http://localhost:8080/debug/api');
    });

    it('strips a trailing slash from the base to avoid double slashes', () => {
        expect(joinUrl('http://localhost:8080/', '/debug/api')).toBe('http://localhost:8080/debug/api');
    });

    it('leaves a base without trailing slash unchanged', () => {
        expect(joinUrl('http://localhost', '/x')).toBe('http://localhost/x');
    });

    it('handles an empty base', () => {
        expect(joinUrl('', '/debug/api')).toBe('/debug/api');
    });
});
