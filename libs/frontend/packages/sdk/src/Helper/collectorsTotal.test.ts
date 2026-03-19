import {describe, expect, it} from 'vitest';
import {DebugEntry} from '../API/Debug/Debug';
import {CollectorsMap} from './collectors';
import {getCollectedCountByCollector} from './collectorsTotal';

const makeEntry = (overrides: Partial<DebugEntry> = {}): DebugEntry => ({id: 'test-1', collectors: [], ...overrides});

describe('getCollectedCountByCollector', () => {
    it('returns log count from logger.total', () => {
        const entry = makeEntry({logger: {total: 15}});
        expect(getCollectedCountByCollector(CollectorsMap.LogCollector, entry)).toBe(15);
    });

    it('returns event count', () => {
        const entry = makeEntry({event: {total: 7}});
        expect(getCollectedCountByCollector(CollectorsMap.EventCollector, entry)).toBe(7);
    });

    it('returns database count as queries + transactions', () => {
        const entry = makeEntry({db: {queries: {error: 0, total: 5}, transactions: {error: 0, total: 2}}});
        expect(getCollectedCountByCollector(CollectorsMap.DatabaseCollector, entry)).toBe(7);
    });

    it('returns 1 for exception when exception data present', () => {
        const entry = makeEntry({exception: {class: 'Error', message: 'test', line: '1', file: 'a.php', code: '0'}});
        expect(getCollectedCountByCollector(CollectorsMap.ExceptionCollector, entry)).toBe(1);
    });

    it('returns 0 for exception when no exception data', () => {
        const entry = makeEntry();
        expect(getCollectedCountByCollector(CollectorsMap.ExceptionCollector, entry)).toBe(0);
    });

    it('returns service count', () => {
        const entry = makeEntry({service: {total: 3}});
        expect(getCollectedCountByCollector(CollectorsMap.ServiceCollector, entry)).toBe(3);
    });

    it('returns middleware count', () => {
        const entry = makeEntry({middleware: {total: 10}});
        expect(getCollectedCountByCollector(CollectorsMap.MiddlewareCollector, entry)).toBe(10);
    });

    it('returns mailer count', () => {
        const entry = makeEntry({mailer: {total: 2}});
        expect(getCollectedCountByCollector(CollectorsMap.MailerCollector, entry)).toBe(2);
    });

    it('returns undefined for mailer when no mailer data', () => {
        const entry = makeEntry();
        expect(getCollectedCountByCollector(CollectorsMap.MailerCollector, entry)).toBeUndefined();
    });

    it('returns timeline count', () => {
        const entry = makeEntry({timeline: {total: 4}});
        expect(getCollectedCountByCollector(CollectorsMap.TimelineCollector, entry)).toBe(4);
    });

    it('returns var-dumper count', () => {
        const entry = makeEntry({'var-dumper': {total: 6}});
        expect(getCollectedCountByCollector(CollectorsMap.VarDumperCollector, entry)).toBe(6);
    });

    it('returns undefined for unknown collector', () => {
        const entry = makeEntry();
        expect(getCollectedCountByCollector('Unknown\\Collector' as CollectorsMap, entry)).toBeUndefined();
    });

    it('returns 0 for ConsoleAppInfoCollector', () => {
        const entry = makeEntry();
        expect(getCollectedCountByCollector(CollectorsMap.ConsoleAppInfoCollector, entry)).toBe(0);
    });

    it('returns http client count', () => {
        const entry = makeEntry({http: {count: 3, totalTime: 100}});
        expect(getCollectedCountByCollector(CollectorsMap.HttpClientCollector, entry)).toBe(3);
    });

    it('returns filesystem stream sum', () => {
        const entry = makeEntry({fs_stream: {read: 5, write: 3, mkdir: 1}});
        expect(getCollectedCountByCollector(CollectorsMap.FilesystemStreamCollector, entry)).toBe(9);
    });
});
