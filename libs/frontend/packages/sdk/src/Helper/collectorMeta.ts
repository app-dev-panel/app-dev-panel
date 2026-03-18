import {CollectorsMap} from '@app-dev-panel/sdk/Helper/collectors';

/**
 * Metadata for each collector: display label and Material Icons icon name.
 * Used by the CollectorSidebar to render navigation items.
 */
export type CollectorMeta = {label: string; icon: string; weight: number};

const collectorMetaMap: Record<string, CollectorMeta> = {
    [CollectorsMap.RequestCollector]: {label: 'Request', icon: 'http', weight: 1},
    [CollectorsMap.LogCollector]: {label: 'Log', icon: 'description', weight: 2},
    [CollectorsMap.DatabaseCollector]: {label: 'Database', icon: 'storage', weight: 3},
    [CollectorsMap.EventCollector]: {label: 'Events', icon: 'bolt', weight: 4},
    [CollectorsMap.ExceptionCollector]: {label: 'Exception', icon: 'warning', weight: 5},
    [CollectorsMap.MiddlewareCollector]: {label: 'Middleware', icon: 'filter_list', weight: 6},
    [CollectorsMap.ServiceCollector]: {label: 'Service', icon: 'inventory_2', weight: 7},
    [CollectorsMap.TimelineCollector]: {label: 'Timeline', icon: 'timeline', weight: 8},
    [CollectorsMap.VarDumperCollector]: {label: 'Dump', icon: 'data_object', weight: 9},
    [CollectorsMap.MailerCollector]: {label: 'Mailer', icon: 'mail', weight: 10},
    [CollectorsMap.FilesystemStreamCollector]: {label: 'Filesystem', icon: 'folder', weight: 11},
    [CollectorsMap.HttpClientCollector]: {label: 'HTTP Client', icon: 'cloud', weight: 12},
    [CollectorsMap.HttpStreamCollector]: {label: 'HTTP Stream', icon: 'stream', weight: 13},
    [CollectorsMap.QueueCollector]: {label: 'Queue', icon: 'queue', weight: 14},
    [CollectorsMap.AssetCollector]: {label: 'Assets', icon: 'web_asset', weight: 15},
    [CollectorsMap.ValidatorCollector]: {label: 'Validator', icon: 'check_circle', weight: 16},
    [CollectorsMap.CacheCollector]: {label: 'Cache', icon: 'cached', weight: 15},
    [CollectorsMap.DoctrineCollector]: {label: 'Doctrine', icon: 'storage', weight: 15},
    [CollectorsMap.TwigCollector]: {label: 'Twig', icon: 'code', weight: 15},
    [CollectorsMap.SecurityCollector]: {label: 'Security', icon: 'shield', weight: 15},
    [CollectorsMap.MessengerCollector]: {label: 'Messenger', icon: 'send', weight: 15},
    // Yii 2 adapter collectors (reuse same icons/weights as their generic equivalents)
    [CollectorsMap.Yii2DbCollector]: {label: 'Database', icon: 'storage', weight: 3},
    [CollectorsMap.Yii2MailerCollector]: {label: 'Mailer', icon: 'mail', weight: 10},
    [CollectorsMap.Yii2AssetBundleCollector]: {label: 'Assets', icon: 'web_asset', weight: 15},

    [CollectorsMap.ConsoleAppInfoCollector]: {label: 'Console', icon: 'terminal', weight: 17},
    [CollectorsMap.WebAppInfoCollector]: {label: 'Web Info', icon: 'language', weight: 18},
    [CollectorsMap.CommandCollector]: {label: 'Command', icon: 'terminal', weight: 19},
    [CollectorsMap.WebViewCollector]: {label: 'View', icon: 'visibility', weight: 20},
};

const defaultMeta: CollectorMeta = {label: 'Unknown', icon: 'extension', weight: 99};

/**
 * Returns display metadata for a collector class name.
 * Falls back to parsing the short class name if no explicit mapping exists.
 */
export const getCollectorMeta = (collectorClass: string): CollectorMeta => {
    if (typeof collectorClass !== 'string') {
        return defaultMeta;
    }
    if (collectorClass in collectorMetaMap) {
        return collectorMetaMap[collectorClass];
    }
    // Fallback: extract short class name
    const shortName = collectorClass.split('\\').pop() ?? collectorClass;
    const label = shortName.replace(/Collector$/, '');
    return {...defaultMeta, label};
};

/**
 * Returns the display label for a collector.
 */
export const getCollectorLabel = (collectorClass: string): string => {
    return getCollectorMeta(collectorClass).label;
};

/**
 * Returns the Material Icons name for a collector.
 */
export const getCollectorIcon = (collectorClass: string): string => {
    return getCollectorMeta(collectorClass).icon;
};

/**
 * Sort comparator for collector class names based on predefined weight.
 */
export const compareCollectorWeight = (a: string, b: string): number => {
    return getCollectorMeta(a).weight - getCollectorMeta(b).weight;
};
