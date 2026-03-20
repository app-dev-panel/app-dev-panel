import {describe, expect, it} from 'vitest';
import {DebugEntry} from '../API/Debug/Debug';
import {isDebugEntryAboutConsole, isDebugEntryAboutWeb} from './debugEntry';

const makeEntry = (overrides: Partial<DebugEntry> = {}): DebugEntry => ({id: 'test-1', collectors: [], ...overrides});

describe('isDebugEntryAboutWeb', () => {
    it('returns true when entry has request property', () => {
        const entry = makeEntry({
            request: {url: '/test', path: '/test', query: '', method: 'GET', isAjax: false, userIp: '127.0.0.1'},
        });
        expect(isDebugEntryAboutWeb(entry)).toBe(true);
    });

    it('returns false when entry has no request property', () => {
        const entry = makeEntry();
        expect(isDebugEntryAboutWeb(entry)).toBe(false);
    });
});

describe('isDebugEntryAboutConsole', () => {
    it('returns true when entry has command property', () => {
        const entry = makeEntry({command: {name: 'test:run', input: 'test:run', exitCode: 0}});
        expect(isDebugEntryAboutConsole(entry)).toBe(true);
    });

    it('returns false when entry has no command property', () => {
        const entry = makeEntry();
        expect(isDebugEntryAboutConsole(entry)).toBe(false);
    });
});
