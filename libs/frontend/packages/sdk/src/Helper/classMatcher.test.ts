import {describe, expect, it} from 'vitest';
import {isClassString} from './classMatcher';

describe('isClassString', () => {
    it.each([
        ['App\\Controller\\HomeController', true],
        ['Yiisoft\\Yii\\Debug\\Collector\\LogCollector', true],
        ['A\\B', false],
        ['hello', false],
        ['', false],
        ['App/', false],
        ['app\\b', true],
    ])('isClassString(%s) === %s', (input, expected) => {
        expect(isClassString(input)).toBe(expected);
    });
});
