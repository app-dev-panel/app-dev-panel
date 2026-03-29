import {DebugEntry} from '@app-dev-panel/sdk/API/Debug/Debug';
import {CollectorsMap} from '@app-dev-panel/sdk/Helper/collectors';

export const getCollectedCountByCollector = (collector: CollectorsMap, data: DebugEntry): number | undefined => {
    switch (collector) {
        case CollectorsMap.AssetBundleCollector:
            return Number(data.asset?.bundles?.total) || Number(data.assets?.bundleCount);
        case CollectorsMap.DatabaseCollector:
            return (
                (Number(data.db?.queries?.total) || Number(data.db?.queryCount) || 0) +
                Number(data.db?.transactions?.total || 0)
            );
        case CollectorsMap.ExceptionCollector:
            return Object.values(data.exception ?? []).length > 0 ? 1 : 0;
        case CollectorsMap.EventCollector:
            return Number(data.event?.total);
        case CollectorsMap.LogCollector:
            return Number(data.logger?.total);
        case CollectorsMap.ServiceCollector:
            return Number(data.service?.total);
        case CollectorsMap.VarDumperCollector:
            return Number(data['var-dumper']?.total);
        case CollectorsMap.ValidatorCollector:
            return Number(data.validator?.total);
        case CollectorsMap.MiddlewareCollector:
            return Number(data.middleware?.total);
        case CollectorsMap.QueueCollector:
            return (
                Number(data.queue?.countPushes) +
                Number(data.queue?.countStatuses) +
                Number(data.queue?.countProcessingMessages) +
                Number(data.queue?.messageCount)
            );
        case CollectorsMap.HttpClientCollector:
            return Number(data.http?.count);
        case CollectorsMap.HttpStreamCollector:
            return Number(data.http_stream?.length);
        case CollectorsMap.MailerCollector:
            return data.mailer?.total != null ? Number(data.mailer.total) : 0;
        case CollectorsMap.CacheCollector:
            return Number(data.cache?.totalOperations);
        case CollectorsMap.FilesystemStreamCollector:
            return Object.values(data.fs_stream ?? []).reduce((acc, value) => acc + Number(value), 0);
        case CollectorsMap.ConsoleAppInfoCollector:
            return 0;
        case CollectorsMap.DeprecationCollector:
            return Number(data.deprecation?.total);
        case CollectorsMap.TimelineCollector:
            return data.timeline?.total != null ? Number(data.timeline.total) : 0;
        case CollectorsMap.OpenTelemetryCollector:
            return Number(data.opentelemetry?.spans) || 0;
        default:
            return undefined;
    }
};
