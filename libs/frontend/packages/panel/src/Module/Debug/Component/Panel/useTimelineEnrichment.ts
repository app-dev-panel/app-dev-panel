import {type TimelineItem} from '@app-dev-panel/panel/Module/Debug/Component/Panel/timelineTypes';
import {useDebugEntry} from '@app-dev-panel/sdk/API/Debug/Context';
import {useLazyGetCollectorInfoQuery} from '@app-dev-panel/sdk/API/Debug/Debug';
import {CollectorsMap} from '@app-dev-panel/sdk/Helper/collectors';
import {useEffect, useMemo, useState} from 'react';

// Collectors we fetch additional data for
const enrichableCollectors = new Set([
    CollectorsMap.LogCollector,
    CollectorsMap.EventCollector,
    CollectorsMap.DatabaseCollector,
    CollectorsMap.TemplateCollector,
]);

type CollectorDataMap = Map<string, any>;

export type EnrichedDetail = {
    /** Truncated preview for inline display */
    preview: string;
    /** Full untruncated text for expanded view */
    full: string;
};

function getEnrichedDetail(
    row: TimelineItem,
    collectorData: CollectorDataMap,
    collectorIndexes: Map<string, number>,
): EnrichedDetail | null {
    const collectorClass = row[2];
    const data = collectorData.get(collectorClass);

    // Track per-collector index
    const currentIndex = collectorIndexes.get(collectorClass) ?? 0;
    collectorIndexes.set(collectorClass, currentIndex + 1);

    // EventCollector: cross-reference with collector data for event name,
    // fall back to row[3] (event class) if data not yet loaded
    if (collectorClass === CollectorsMap.EventCollector) {
        if (Array.isArray(data) && data[currentIndex]) {
            const entry = data[currentIndex];
            const shortName = (entry.name || '').split('\\').pop() ?? '';
            // Show "ClassName (eventName)" when event field differs from class name
            if (entry.event && entry.event !== entry.name && entry.event !== shortName) {
                const text = `${shortName} (${entry.event})`;
                return {preview: text, full: text};
            }
            const text = shortName || entry.event || null;
            return text ? {preview: text, full: text} : null;
        }
        // Fallback: use row[3] if collector data not loaded yet
        if (row[3]) {
            const eventClass = typeof row[3] === 'string' ? row[3] : Array.isArray(row[3]) ? row[3][0] : null;
            if (eventClass && typeof eventClass === 'string') {
                const text = eventClass.split('\\').pop() ?? eventClass;
                return {preview: text, full: text};
            }
        }
    }

    if (!data) return null;

    // LogCollector: data is an array of log entries
    if (collectorClass === CollectorsMap.LogCollector && Array.isArray(data) && data[currentIndex]) {
        const entry = data[currentIndex];
        const message = typeof entry.message === 'string' ? entry.message : JSON.stringify(entry.message);
        const full = `[${entry.level}] ${message}`;
        const preview = message.length > 80 ? `[${entry.level}] ${message.slice(0, 80)}...` : full;
        return {preview, full};
    }

    // DatabaseCollector: data is {queries: [...], transactions: [...], duplicates: {...}}
    if (collectorClass === CollectorsMap.DatabaseCollector) {
        const queries = data.queries ?? (Array.isArray(data) ? data : []);
        if (Array.isArray(queries) && queries[currentIndex]) {
            const sql = queries[currentIndex].sql || queries[currentIndex].rawSql || '';
            const preview = sql.length > 80 ? sql.slice(0, 80) + '...' : sql;
            return {preview, full: sql};
        }
    }

    // TemplateCollector: data is {renders: [...], totalTime, renderCount, duplicates}
    if (collectorClass === CollectorsMap.TemplateCollector) {
        const renders = data.renders ?? (Array.isArray(data) ? data : []);
        if (Array.isArray(renders) && renders[currentIndex]) {
            const template = renders[currentIndex].template || '';
            const name = template.includes('/') ? template.split('/').pop() : template;
            return name ? {preview: name, full: template} : null;
        }
    }

    return null;
}

/**
 * Hook that fetches collector data and computes enriched detail strings
 * for each timeline event by cross-referencing with source collector records.
 */
export function useTimelineEnrichment(allData: TimelineItem[], filteredData: TimelineItem[]): (EnrichedDetail | null)[] {
    const debugEntry = useDebugEntry();
    const [fetchCollector] = useLazyGetCollectorInfoQuery();
    const [collectorData, setCollectorData] = useState<CollectorDataMap>(new Map());

    // Identify which enrichable collectors are in the timeline
    const collectorsToFetch = useMemo(() => {
        if (!debugEntry) return [];
        const entryCollectors = new Set(debugEntry.collectors.map((c: any) => (typeof c === 'string' ? c : c.id)));
        const timelineCollectors = new Set(allData.map((r) => r[2]));
        return [...enrichableCollectors].filter((c) => timelineCollectors.has(c) && entryCollectors.has(c));
    }, [allData, debugEntry]);

    // Fetch collector data for enrichment
    useEffect(() => {
        if (!debugEntry || collectorsToFetch.length === 0) return;

        for (const collector of collectorsToFetch) {
            if (collectorData.has(collector)) continue;
            fetchCollector({id: debugEntry.id, collector}).then(({data: result}) => {
                setCollectorData((prev) => {
                    const next = new Map(prev);
                    next.set(collector, result);
                    return next;
                });
            });
        }
    }, [debugEntry, collectorsToFetch, fetchCollector]);

    // Build enriched details — track per-collector index for cross-referencing
    return useMemo(() => {
        const collectorIndexes = new Map<string, number>();
        return filteredData.map((row) => getEnrichedDetail(row, collectorData, collectorIndexes));
    }, [filteredData, collectorData]);
}
