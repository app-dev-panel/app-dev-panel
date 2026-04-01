import {describe, expect, it} from 'vitest';
import {compareCollectorWeight, getCollectorIcon, getCollectorLabel, getCollectorMeta} from './collectorMeta';
import {CollectorsMap} from './collectors';

describe('getCollectorMeta', () => {
    it('returns mapped metadata for known collectors', () => {
        const meta = getCollectorMeta(CollectorsMap.RequestCollector);
        expect(meta).toEqual({label: 'Request', icon: 'http', weight: 1});
    });

    it('returns metadata for all known collectors', () => {
        const knownCollectors = [
            CollectorsMap.RequestCollector,
            CollectorsMap.LogCollector,
            CollectorsMap.DatabaseCollector,
            CollectorsMap.EventCollector,
            CollectorsMap.ExceptionCollector,
            CollectorsMap.MiddlewareCollector,
            CollectorsMap.ServiceCollector,
            CollectorsMap.TimelineCollector,
            CollectorsMap.VarDumperCollector,
            CollectorsMap.MailerCollector,
            CollectorsMap.FilesystemStreamCollector,
            CollectorsMap.HttpClientCollector,
            CollectorsMap.HttpStreamCollector,
            CollectorsMap.QueueCollector,
            CollectorsMap.AssetBundleCollector,
            CollectorsMap.ValidatorCollector,
            CollectorsMap.ConsoleAppInfoCollector,
            CollectorsMap.WebAppInfoCollector,
            CollectorsMap.CommandCollector,
        ];
        for (const collector of knownCollectors) {
            const meta = getCollectorMeta(collector);
            expect(meta.label).toBeTruthy();
            expect(meta.icon).toBeTruthy();
            expect(meta.weight).toBeGreaterThan(0);
        }
    });

    it('falls back to short class name for unknown collectors', () => {
        const meta = getCollectorMeta('App\\Debug\\CustomCollector');
        expect(meta.label).toBe('Custom');
        expect(meta.icon).toBe('extension');
        expect(meta.weight).toBe(99);
    });

    it('handles class name without Collector suffix', () => {
        const meta = getCollectorMeta('App\\Debug\\SomeTracker');
        expect(meta.label).toBe('SomeTracker');
    });

    it('returns default meta for non-string input', () => {
        const meta = getCollectorMeta(42 as any);
        expect(meta).toEqual({label: 'Unknown', icon: 'extension', weight: 99});
    });

    it('returns default meta for null input', () => {
        const meta = getCollectorMeta(null as any);
        expect(meta).toEqual({label: 'Unknown', icon: 'extension', weight: 99});
    });

    it('returns default meta for object input', () => {
        const meta = getCollectorMeta({class: 'Foo'} as any);
        expect(meta).toEqual({label: 'Unknown', icon: 'extension', weight: 99});
    });
});

describe('getCollectorLabel', () => {
    it('returns label for known collector', () => {
        expect(getCollectorLabel(CollectorsMap.LogCollector)).toBe('Log');
    });

    it('returns parsed label for unknown collector', () => {
        expect(getCollectorLabel('Vendor\\FooCollector')).toBe('Foo');
    });
});

describe('getCollectorIcon', () => {
    it('returns icon for known collector', () => {
        expect(getCollectorIcon(CollectorsMap.DatabaseCollector)).toBe('storage');
    });

    it('returns default icon for unknown collector', () => {
        expect(getCollectorIcon('Unknown\\Thing')).toBe('extension');
    });
});

describe('compareCollectorWeight', () => {
    it('sorts known collectors by weight', () => {
        const collectors = [
            CollectorsMap.TimelineCollector,
            CollectorsMap.RequestCollector,
            CollectorsMap.LogCollector,
        ];
        const sorted = [...collectors].sort(compareCollectorWeight);
        expect(sorted).toEqual([
            CollectorsMap.RequestCollector,
            CollectorsMap.LogCollector,
            CollectorsMap.TimelineCollector,
        ]);
    });

    it('puts unknown collectors after known ones', () => {
        const collectors = ['Unknown\\FooCollector', CollectorsMap.RequestCollector];
        const sorted = [...collectors].sort(compareCollectorWeight);
        expect(sorted).toEqual([CollectorsMap.RequestCollector, 'Unknown\\FooCollector']);
    });

    it('handles non-string values without crashing', () => {
        const collectors = [42 as any, CollectorsMap.RequestCollector];
        const sorted = [...collectors].sort(compareCollectorWeight);
        expect(sorted).toHaveLength(2);
    });
});
