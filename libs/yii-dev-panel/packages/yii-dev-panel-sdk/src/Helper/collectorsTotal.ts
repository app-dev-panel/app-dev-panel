import {DebugEntry} from '@yiisoft/yii-dev-panel-sdk/API/Debug/Debug';
import {CollectorsMap} from '@yiisoft/yii-dev-panel-sdk/Helper/collectors';

export const getCollectedCountByCollector = (collector: CollectorsMap, data: DebugEntry): number | undefined => {
    switch (collector) {
        case CollectorsMap.AssetCollector:
            return Number(data.asset?.bundles?.total);
        case CollectorsMap.DatabaseCollector:
            return Number(data.db?.queries?.total) + Number(data.db?.transactions?.total);
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
                Number(data.queue?.countProcessingMessages)
            );
        case CollectorsMap.HttpClientCollector:
            return Number(data.http?.count);
        case CollectorsMap.HttpStreamCollector:
            return Number(data.http_stream?.length);
        case CollectorsMap.MailerCollector:
            return Number(data.mailer?.total);
        case CollectorsMap.FilesystemStreamCollector:
            return Object.values(data.fs_stream ?? []).reduce((acc, value) => acc + value, 0);
        case CollectorsMap.ConsoleAppInfoCollector:
            return 0;
        case CollectorsMap.TimelineCollector:
            return Number(data.timeline?.total);
        default:
            return undefined;
    }
};
