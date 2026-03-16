import {describe, expect, it} from 'vitest';
import {parseFilePath, parseFilePathWithLineAnchor, parseFilename, parsePathLineAnchor} from './filePathParser';

describe('parseFilePath', () => {
    it('removes line number suffix with colon', () => {
        expect(parseFilePath('/src/app.php:42')).toBe('/src/app.php');
    });

    it('removes line number suffix with hash', () => {
        expect(parseFilePath('/src/app.php#42')).toBe('/src/app.php');
    });

    it('returns path without line suffix unchanged', () => {
        expect(parseFilePath('/src/app.php')).toBe('/src/app.php');
    });

    it('returns empty string for non-string input', () => {
        expect(parseFilePath(42 as any)).toBe('');
    });
});

describe('parseFilePathWithLineAnchor', () => {
    it('converts line number to #L anchor', () => {
        expect(parseFilePathWithLineAnchor('/src/app.php:42')).toBe('/src/app.php#L42');
    });

    it('converts line range to #L anchor', () => {
        expect(parseFilePathWithLineAnchor('/src/app.php:10-20')).toBe('/src/app.php#L10-20');
    });

    it('returns path without line number unchanged', () => {
        expect(parseFilePathWithLineAnchor('/src/app.php')).toBe('/src/app.php');
    });
});

describe('parsePathLineAnchor', () => {
    it('parses single line anchor', () => {
        expect(parsePathLineAnchor('#L42')).toEqual([42]);
    });

    it('parses line range anchor', () => {
        expect(parsePathLineAnchor('#L10-20')).toEqual([10, 20]);
    });

    it('returns undefined for no anchor', () => {
        expect(parsePathLineAnchor('/src/app.php')).toBeUndefined();
    });
});

describe('parseFilename', () => {
    it('extracts filename from path', () => {
        expect(parseFilename('/src/app.php')).toBe('app.php');
    });

    it('returns the string itself if no slash', () => {
        expect(parseFilename('app.php')).toBe('app.php');
    });
});
