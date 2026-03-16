import {describe, expect, it} from 'vitest';
import {DebugEntry} from '../API/Debug/Debug';
import {isDebugEntryAboutConsole, isDebugEntryAboutWeb} from './debugEntry';

const makeEntry = (overrides: Partial<DebugEntry> = {}): DebugEntry => ({id: 'test-1', collectors: [], ...overrides});

describe('isDebugEntryAboutWeb', () => {
    it('returns true when entry has web property', () => {
        const entry = makeEntry({
            web: {php: {version: '8.4'}, request: {startTime: 0, processingTime: 0}, memory: {peakUsage: 0}},
        });
        expect(isDebugEntryAboutWeb(entry)).toBe(true);
    });

    it('returns false when entry has no web property', () => {
        const entry = makeEntry();
        expect(isDebugEntryAboutWeb(entry)).toBe(false);
    });
});

describe('isDebugEntryAboutConsole', () => {
    it('returns true when entry has console property', () => {
        const entry = makeEntry({
            console: {php: {version: '8.4'}, request: {startTime: 0, processingTime: 0}, memory: {peakUsage: 0}},
        });
        expect(isDebugEntryAboutConsole(entry)).toBe(true);
    });

    it('returns false when entry has no console property', () => {
        const entry = makeEntry();
        expect(isDebugEntryAboutConsole(entry)).toBe(false);
    });
});
