import {describe, expect, it} from 'vitest';
import {createPathMapper, mapToLocal, mapToLocalWithLine, mapToRemote} from './pathMapper';

describe('mapToLocal', () => {
    it('maps remote path to local using first matching rule', () => {
        const rules = {'/app': '/home/user/project'};
        expect(mapToLocal('/app/src/Foo.php', rules)).toBe('/home/user/project/src/Foo.php');
    });

    it('returns original path when no rule matches', () => {
        const rules = {'/app': '/home/user/project'};
        expect(mapToLocal('/other/path/file.php', rules)).toBe('/other/path/file.php');
    });

    it('first matching rule wins', () => {
        const rules = {'/app/vendor': '/home/user/vendor', '/app': '/home/user/project'};
        expect(mapToLocal('/app/vendor/autoload.php', rules)).toBe('/home/user/vendor/autoload.php');
        expect(mapToLocal('/app/src/Foo.php', rules)).toBe('/home/user/project/src/Foo.php');
    });

    it('returns original with empty rules', () => {
        expect(mapToLocal('/app/src/Foo.php', {})).toBe('/app/src/Foo.php');
    });
});

describe('mapToRemote', () => {
    it('maps local path to remote using first matching rule', () => {
        const rules = {'/app': '/home/user/project'};
        expect(mapToRemote('/home/user/project/src/Foo.php', rules)).toBe('/app/src/Foo.php');
    });

    it('returns original path when no rule matches', () => {
        const rules = {'/app': '/home/user/project'};
        expect(mapToRemote('/other/path/file.php', rules)).toBe('/other/path/file.php');
    });
});

describe('mapToLocalWithLine', () => {
    it('maps path with colon line number', () => {
        const rules = {'/app': '/home/user/project'};
        expect(mapToLocalWithLine('/app/src/Foo.php:42', rules)).toBe('/home/user/project/src/Foo.php:42');
    });

    it('maps path with hash line number', () => {
        const rules = {'/app': '/home/user/project'};
        expect(mapToLocalWithLine('/app/src/Foo.php#42', rules)).toBe('/home/user/project/src/Foo.php#42');
    });

    it('maps path with line range', () => {
        const rules = {'/app': '/home/user/project'};
        expect(mapToLocalWithLine('/app/src/Foo.php:10-20', rules)).toBe('/home/user/project/src/Foo.php:10-20');
    });

    it('maps path without line number', () => {
        const rules = {'/app': '/home/user/project'};
        expect(mapToLocalWithLine('/app/src/Foo.php', rules)).toBe('/home/user/project/src/Foo.php');
    });

    it('returns original when no match', () => {
        const rules = {'/app': '/home/user/project'};
        expect(mapToLocalWithLine('/other/file.php:42', rules)).toBe('/other/file.php:42');
    });
});

describe('createPathMapper', () => {
    it('creates mapper with bound rules', () => {
        const mapper = createPathMapper({'/app': '/home/user/project'});
        expect(mapper.toLocal('/app/src/Foo.php')).toBe('/home/user/project/src/Foo.php');
        expect(mapper.toRemote('/home/user/project/src/Foo.php')).toBe('/app/src/Foo.php');
        expect(mapper.toLocalWithLine('/app/src/Foo.php:42')).toBe('/home/user/project/src/Foo.php:42');
        expect(mapper.hasRules).toBe(true);
    });

    it('creates mapper with no rules', () => {
        const mapper = createPathMapper({});
        expect(mapper.toLocal('/app/src/Foo.php')).toBe('/app/src/Foo.php');
        expect(mapper.hasRules).toBe(false);
    });
});
