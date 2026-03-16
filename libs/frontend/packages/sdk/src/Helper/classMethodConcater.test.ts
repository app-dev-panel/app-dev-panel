import {describe, expect, it} from 'vitest';
import {concatClassMethod} from './classMethodConcater';

describe('concatClassMethod', () => {
    it('concatenates class and method with :: and ()', () => {
        expect(concatClassMethod('App\\Controller', 'index')).toBe('App\\Controller::index()');
    });
});
