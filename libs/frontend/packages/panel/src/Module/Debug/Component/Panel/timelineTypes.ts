import {CollectorsMap} from '@app-dev-panel/sdk/Helper/collectors';
import {type Theme} from '@mui/material/styles';

/** Raw timeline event from PHP TimelineCollector: [microtime, reference, collectorClass, additionalData?] */
export type TimelineItem = [number, number, string] | [number, number, string, string];

/** Collector FQCN → theme color key mapping */
export const collectorColorKeyMap: Partial<Record<string, string>> = {
    [CollectorsMap.RequestCollector]: 'request',
    [CollectorsMap.LogCollector]: 'log',
    [CollectorsMap.EventCollector]: 'event',
    [CollectorsMap.DatabaseCollector]: 'database',
    [CollectorsMap.MiddlewareCollector]: 'middleware',
    [CollectorsMap.ExceptionCollector]: 'exception',
    [CollectorsMap.ServiceCollector]: 'service',
    [CollectorsMap.TimelineCollector]: 'timeline',
    [CollectorsMap.VarDumperCollector]: 'varDumper',
    [CollectorsMap.MailerCollector]: 'mailer',
    [CollectorsMap.FilesystemStreamCollector]: 'filesystem',
    [CollectorsMap.HttpClientCollector]: 'filesystem',
    [CollectorsMap.CacheCollector]: 'cache',
    [CollectorsMap.TemplateCollector]: 'template',
    [CollectorsMap.AuthorizationCollector]: 'authorization',
    [CollectorsMap.DeprecationCollector]: 'deprecation',
    [CollectorsMap.EnvironmentCollector]: 'environment',
    [CollectorsMap.TranslatorCollector]: 'translator',
    [CollectorsMap.WebAppInfoCollector]: 'environment',
    [CollectorsMap.ConsoleAppInfoCollector]: 'environment',
    [CollectorsMap.CommandCollector]: 'request',
    [CollectorsMap.QueueCollector]: 'service',
    [CollectorsMap.RouterCollector]: 'middleware',
    [CollectorsMap.ValidatorCollector]: 'service',
    [CollectorsMap.OpenTelemetryCollector]: 'timeline',
    [CollectorsMap.ElasticsearchCollector]: 'database',
    [CollectorsMap.RedisCollector]: 'cache',
};

type CollectorColor = {bg: string; fg: string};

/** Resolve collector FQCN to its theme color pair */
export function getCollectorColor(theme: Theme, collectorClass: string): CollectorColor {
    const key = collectorColorKeyMap[collectorClass] ?? 'default';
    const colors = theme.adp.collectorColors;
    return (colors as Record<string, CollectorColor>)[key] ?? colors.default;
}

/** Parse "[level] message" format from log enrichment preview */
export function parseLogLevel(preview: string): {level: string; message: string} | null {
    const match = preview.match(/^\[(\w+)] (.*)$/);
    return match ? {level: match[1], message: match[2]} : null;
}

/** Strip "[level] " prefix from full log detail */
export function stripLogLevelPrefix(full: string): string {
    return full.replace(/^\[\w+] /, '');
}
