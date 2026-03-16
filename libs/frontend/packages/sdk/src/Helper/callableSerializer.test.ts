import {describe, expect, it} from 'vitest';
import {serializeCallable} from './callableSerializer';

describe('serializeCallable', () => {
    it('serializes array of [class, method] as static call', () => {
        expect(serializeCallable(['App\\Handler', 'handle'])).toBe('App\\Handler::handle()');
    });

    it('returns string callable as-is', () => {
        expect(serializeCallable('myFunction')).toBe('myFunction');
    });

    it('JSON-stringifies other types', () => {
        expect(serializeCallable({foo: 'bar'})).toBe('{"foo":"bar"}');
        expect(serializeCallable(42)).toBe('42');
    });

    it('serializes array with more than 2 elements as JSON', () => {
        expect(serializeCallable(['a', 'b', 'c'])).toBe('["a","b","c"]');
    });
});
