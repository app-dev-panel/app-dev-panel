import {describe, expect, it} from 'vitest';
import {parseObjectId, toObjectReference, toObjectString} from './objectString';

describe('parseObjectId', () => {
    it('extracts numeric id from object string', () => {
        expect(parseObjectId('object@App\\MyClass#42')).toBe(42);
    });

    it('handles strings without hash', () => {
        expect(parseObjectId('no-hash-here')).toBe(NaN);
    });
});

describe('toObjectReference', () => {
    it('removes object@ prefix', () => {
        expect(toObjectReference('object@App\\MyClass#42')).toBe('App\\MyClass#42');
    });
});

describe('toObjectString', () => {
    it('constructs object string from class and id', () => {
        expect(toObjectString('App\\MyClass', 42)).toBe('object@App\\MyClass#42');
    });
});
