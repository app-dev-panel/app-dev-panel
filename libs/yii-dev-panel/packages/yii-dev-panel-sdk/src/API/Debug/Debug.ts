import {createApi} from '@reduxjs/toolkit/query/react';
import {createBaseQuery} from '@yiisoft/yii-dev-panel-sdk/API/createBaseQuery';

type Response<T = any> = {data: T};

export type HTTPMethod = 'DELETE' | 'GET' | 'HEAD' | 'PATCH' | 'POST' | 'PUT';

export type CollectorInfo = {id: string; name: string};

export type DebugEntry = {
    id: string;
    collectors: CollectorInfo[];
    logger?: {total: number};
    event?: {total: number};
    service?: {total: number};
    mailer?: {total: number};
    timeline?: {total: number};
    'var-dumper'?: {total: number};
    validator?: {total: number; valid: number; invalid: number};
    queue?: {countPushes: number; countStatuses: number; countProcessingMessages: number};
    http?: {count: number; totalTime: number};
    fs_stream?: {read?: number; write?: number; mkdir?: number};
    http_stream?: [];
    web?: {php: {version: string}; request: {startTime: number; processingTime: number}; memory: {peakUsage: number}};
    console?: {
        php: {version: string};
        request: {startTime: number; processingTime: number};
        memory: {peakUsage: number};
    };
    request?: {url: string; path: string; query: string; method: HTTPMethod; isAjax: boolean; userIp: string};
    command?: {exitCode: number; class: string; input: string; name: string};
    response?: {statusCode: number};
    router?: null | {
        matchTime: number;
        name: string;
        pattern: string;
        arguments: string;
        host: string;
        uri: string;
        action: string | string[];
        middlewares: any[];
    };
    middleware?: {total: number};
    asset?: {bundles: {total: number}};
    exception?: {class: string; message: string; line: string; file: string; code: string};
    db?: {queries: {error: number; total: number}; transactions: {error: number; total: number}};
    [name: string]: any;
};
type SummaryResponseType = {data: DebugEntry[]};

type GetCollectorInfoProps = {id: string; collector: string};

type GetObjectProps = {debugEntryId: string; objectId: number};

type GetObjectResponse = {class: string; value: any} | null;

type CollectorResponseType = any;

function normalizeCollectors(collectors: (string | CollectorInfo)[]): CollectorInfo[] {
    return collectors.map((c) => (typeof c === 'string' ? {id: c, name: c.split('\\').pop() || c} : c));
}

export const debugApi = createApi({
    reducerPath: 'api.debug',
    tagTypes: ['debug/list'],
    baseQuery: createBaseQuery('/debug/api/'),
    endpoints: (builder) => ({
        getDebug: builder.query<DebugEntry[], void>({
            query: () => ``,
            transformResponse: (result: SummaryResponseType) =>
                ((result.data as DebugEntry[]) || []).map((entry) => ({
                    ...entry,
                    collectors: normalizeCollectors(entry.collectors),
                })),
            providesTags: ['debug/list'],
        }),
        getObject: builder.query<GetObjectResponse, GetObjectProps>({
            query: (args) => `object/${args.debugEntryId}/${args.objectId}`,
            transformResponse: (result: Response<GetObjectResponse>) => result.data,
        }),
        getCollectorInfo: builder.query<CollectorResponseType, GetCollectorInfoProps>({
            query: (args) => `view/${args.id}?collector=${args.collector}`,
            transformResponse: (result: SummaryResponseType) => (result.data as CollectorResponseType[]) || [],
            transformErrorResponse: (result) => result.data,
        }),
    }),
});

export const {
    useGetDebugQuery,
    useLazyGetDebugQuery,
    useGetObjectQuery,
    useLazyGetObjectQuery,
    useLazyGetCollectorInfoQuery,
} = debugApi;
