import {createBaseQuery} from '@app-dev-panel/sdk/API/createBaseQuery';
import {createApi} from '@reduxjs/toolkit/query/react';

type ObjectType = {object: object; path: string};
export type InspectorFile = {
    path: string;
    baseName: string;
    extension: string;
    user: {uid: number; gid?: number; name?: string};
    group: {gid: number; name?: string};
    size: number;
    type: string;
    permissions: string;
};
export type InspectorFileContent = {
    directory: string;
    content: string;
    startLine?: number;
    endLine?: number;
    insideRoot?: boolean;
} & InspectorFile;

export type ConfigurationType = Record<string, object | string>;
export type ClassesType = string[];
export type CommandType = {name: string; title: string; group: string; description: string};
export type CommandResponseType<T = any> = {status: 'ok' | 'error' | 'fail'; result: T; errors: string[]};
export type CacheResponseType = any;
export type PutTranslationArgumentType = {category: string; locale: string; translation: string; message: string};

export type TableColumn = {name: string; type: string; [key: string]: unknown};
export type TableQueryParams = {table: string; limit?: number; offset?: number};
export type TableResponse = {
    table: string;
    primaryKeys: string[];
    columns: TableColumn[];
    records: Record<string, unknown>[];
    totalCount: number;
    limit: number;
    offset: number;
};

type ComposerResponse = {
    json: {require: Record<string, string>; 'require-dev': Record<string, string>};
    lock: {packages: {name: string; version: string}[]; 'packages-dev': {name: string; version: string}[]};
};
type OpcacheResponse = {
    configuration: {
        directives: {
            'opcache.enable': boolean;
            'opcache.enable_cli': boolean;
            'opcache.use_cwd': boolean;
            'opcache.validate_timestamps': boolean;
            'opcache.validate_permission': boolean;
            'opcache.validate_root': boolean;
            'opcache.dups_fix': boolean;
            'opcache.revalidate_path': boolean;
            'opcache.log_verbosity_level': number;
            'opcache.memory_consumption': number;
            'opcache.interned_strings_buffer': number;
            'opcache.max_accelerated_files': number;
            'opcache.max_wasted_percentage': number;
            'opcache.force_restart_timeout': number;
            'opcache.revalidate_freq': number;
            'opcache.preferred_memory_model': string;
            'opcache.blacklist_filename': string;
            'opcache.max_file_size': number;
            'opcache.error_log': string;
            'opcache.protect_memory': boolean;
            'opcache.save_comments': boolean;
            'opcache.record_warnings': boolean;
            'opcache.enable_file_override': boolean;
            'opcache.optimization_level': number;
            'opcache.lockfile_path': string;
            'opcache.file_cache': string;
            'opcache.file_cache_only': boolean;
            'opcache.file_cache_consistency_checks': boolean;
            'opcache.file_update_protection': number;
            'opcache.opt_debug_level': number;
            'opcache.restrict_api': string;
            'opcache.huge_code_pages': boolean;
            'opcache.preload': string;
            'opcache.preload_user': string;
            'opcache.jit': string;
            'opcache.jit_buffer_size': number;
            'opcache.jit_debug': number;
            'opcache.jit_bisect_limit': number;
            'opcache.jit_blacklist_root_trace': number;
            'opcache.jit_blacklist_side_trace': number;
            'opcache.jit_hot_func': number;
            'opcache.jit_hot_loop': number;
            'opcache.jit_hot_return': number;
            'opcache.jit_hot_side_exit': number;
            'opcache.jit_max_exit_counters': number;
            'opcache.jit_max_loop_unrolls': number;
            'opcache.jit_max_polymorphic_calls': number;
            'opcache.jit_max_recursive_calls': number;
            'opcache.jit_max_recursive_returns': number;
            'opcache.jit_max_root_traces': number;
            'opcache.jit_max_side_traces': number;
            'opcache.jit_prof_threshold': number;
            'opcache.jit_max_trace_length': number;
        };
        version: {version: string; opcache_product_name: 'Zend OPcache'};
        blacklist: [];
    };
    status: {
        opcache_enabled: boolean;
        cache_full: boolean;
        restart_pending: boolean;
        restart_in_progress: boolean;
        memory_usage: {
            used_memory: number;
            free_memory: number;
            wasted_memory: number;
            current_wasted_percentage: number;
        };
        interned_strings_usage: {
            buffer_size: number;
            used_memory: number;
            free_memory: number;
            number_of_strings: number;
        };
        opcache_statistics: {
            num_cached_scripts: number;
            num_cached_keys: number;
            max_cached_keys: number;
            hits: number;
            start_time: number;
            last_restart_time: number;
            oom_restarts: number;
            hash_restarts: number;
            manual_restarts: number;
            misses: number;
            blacklist_misses: number;
            blacklist_miss_ratio: number;
            opcache_hit_rate: number;
        };
        scripts: Record<
            string,
            {
                full_path: string;
                hits: number;
                memory_consumption: number;
                last_used: string;
                last_used_timestamp: number;
                timestamp: number;
                revalidate: number;
            }
        >;
        jit: {
            enabled: boolean;
            on: boolean;
            kind: number;
            opt_level: number;
            opt_flags: number;
            buffer_size: number;
            buffer_free: number;
        };
    };
};

export type AuthorizationGuard = {name: string; provider: string; config: Record<string, unknown>};
export type AuthorizationVoter = {name: string; type: string; priority: number | null};
export type AuthorizationResponse = {
    guards: AuthorizationGuard[];
    roleHierarchy: Record<string, string[]>;
    voters: AuthorizationVoter[];
    config: Record<string, unknown>;
};

type CoverageFileInfo = {coveredLines: number; executableLines: number; percentage: number};
type CoverageResponse = {
    driver: string | null;
    error?: string;
    files: Record<string, CoverageFileInfo>;
    summary: {totalFiles: number; coveredLines: number; executableLines: number; percentage: number};
};

export type HttpMockStatus = {running: boolean; host: string; port: number};
export type HttpMockExpectation = {
    request: {
        method?: string;
        url?: {isEqualTo?: string; matches?: string; contains?: string};
        body?: {isEqualTo?: string; matches?: string; contains?: string};
        headers?: Record<string, {isEqualTo?: string; matches?: string; contains?: string}>;
    };
    response: {statusCode: number; body?: string; headers?: Record<string, string>; delayMillis?: number};
    proxyTo?: string | null;
    priority?: number;
    scenarioName?: string | null;
    scenarioStateIs?: string | null;
    newScenarioState?: string | null;
};
export type HttpMockHistoryEntry = {method: string; url: string; headers: Record<string, string>; body: string | null};

type CurlBuilderResponse = {command: string};

type CheckRouteResponse = {result: boolean; action: string[]};

export type ClosureDescriptor = {
    __closure: true;
    source: string;
    file: string | null;
    startLine: number | null;
    endLine: number | null;
};

export type EventListener = string | [string, string] | ClosureDescriptor;

export type EventEntry = {name: string; class: string | null; listeners: EventListener[]};

export type EventListenersType = EventEntry[] | Record<string, EventListener[]>;

export type EventsResponse = {
    common: EventListenersType | null;
    console: EventListenersType | null;
    web: EventListenersType | null;
};

type Response<T = any> = {data: T};

export const inspectorApi = createApi({
    reducerPath: 'api.inspector',
    keepUnusedDataFor: 0,
    tagTypes: [
        'inspector/composer',
        'inspector/opcache',
        'inspector/mcp',
        'inspector/elasticsearch',
        'inspector/redis',
        'inspector/http-mock',
    ],
    baseQuery: createBaseQuery('/inspect/api/'),
    endpoints: (builder) => ({
        getParameters: builder.query<Response, void>({
            query: () => `params`,
            transformResponse: (result: Response) => result.data || [],
        }),
        getConfiguration: builder.query<ConfigurationType, string>({
            query: (group = 'di') => `config?group=${group}`,
            transformResponse: (result: Response<ConfigurationType>) => result.data,
        }),
        getClasses: builder.query<ClassesType, string>({
            query: () => `classes`,
            transformResponse: (result: Response<ClassesType>) => result.data || [],
        }),
        getObject: builder.query<ObjectType, string>({
            query: (classname) => `object?classname=${classname}`,
            transformResponse: (result: Response<ObjectType>) => result.data,
        }),
        getCommands: builder.query<CommandType[], void>({
            query: (_command) => 'command',
            transformResponse: (result: Response<CommandType[]>) => result.data || [],
        }),
        runCommand: builder.mutation<CommandResponseType, string>({
            query: (command) => ({url: `command?command=${command}`, method: 'POST'}),
            transformResponse: (result: Response<CommandResponseType>) => result.data,
        }),
        getFiles: builder.query<InspectorFile[], string>({
            query: (command) => `files?path=${command}`,
            transformResponse: (result: Response<InspectorFile[]>) => result.data || [],
        }),
        getClass: builder.query<InspectorFile[], {className: string; methodName: string}>({
            query: ({className, methodName = ''}) => `files?class=${className}&method=${methodName}`,
            transformResponse: (result: Response<InspectorFile[]>) => result.data || [],
        }),
        getTranslations: builder.query<Response, void>({
            query: () => `translations`,
            transformResponse: (result: Response) => result.data || [],
        }),
        putTranslations: builder.mutation<Response, PutTranslationArgumentType>({
            query: (body) => ({method: 'PUT', url: `translations`, body: body}),
            transformResponse: (result: Response) => result.data || [],
        }),
        getTable: builder.query<Response, void>({
            query: () => `table`,
            transformResponse: (result: Response) => result.data || [],
        }),
        getTableData: builder.query<TableResponse, TableQueryParams>({
            query: ({table, limit = 50, offset = 0}) => `table/${table}?limit=${limit}&offset=${offset}`,
            transformResponse: (result: Response<TableResponse>) => result.data,
        }),
        explainQuery: builder.mutation<any[], {sql: string; params?: Record<string, any>; analyze?: boolean}>({
            query: ({sql, params, analyze}) => ({
                url: `table/explain`,
                method: 'POST',
                body: {sql, params: params ?? {}, analyze: analyze ?? false},
            }),
            transformResponse: (result: Response<any[]>) => result.data,
        }),
        executeQuery: builder.mutation<Record<string, unknown>[], {sql: string; params?: Record<string, any>}>({
            query: ({sql, params}) => ({url: `table/query`, method: 'POST', body: {sql, params: params ?? {}}}),
            transformResponse: (result: Response<Record<string, unknown>[]>) => result.data,
        }),
        doRequest: builder.mutation<Response, {id: string}>({
            query: (args) => ({method: 'PUT', url: `request?debugEntryId=${args.id}`}),
            transformResponse: (result: Response) => result.data || [],
        }),
        postCurlBuild: builder.mutation<CurlBuilderResponse, string>({
            query: (debugEntryId) => ({method: 'POST', url: `curl/build?debugEntryId=${debugEntryId}`}),
            transformResponse: (result: Response<CurlBuilderResponse>) => result.data,
        }),
        getRoutes: builder.query<Response, void>({
            query: () => `routes`,
            transformResponse: (result: Response) => result.data || [],
        }),
        getCheckRoute: builder.query<CheckRouteResponse, string>({
            query: (route) => `route/check?route=${route}`,
            transformResponse: (result: Response<CheckRouteResponse>) => result.data,
        }),
        getEvents: builder.query<EventsResponse, void>({
            query: () => `events`,
            transformResponse: (result: Response<EventsResponse>) => result.data,
        }),
        getPhpInfo: builder.query<string, void>({
            query: () => `phpinfo`,
            transformResponse: (result: Response) => result.data || [],
        }),
        getComposer: builder.query<ComposerResponse, void>({
            query: () => `composer`,
            transformResponse: (result: Response<ComposerResponse>) => result.data,
            providesTags: ['inspector/composer'],
        }),
        getComposerInspect: builder.query<CommandResponseType, string>({
            query: (packageName) => `composer/inspect?package=${packageName}`,
            transformResponse: (result: Response<CommandResponseType>) => result.data,
            providesTags: ['inspector/composer'],
        }),
        getOpcache: builder.query<OpcacheResponse, void>({
            query: () => `opcache`,
            transformResponse: (result: Response<OpcacheResponse>) => result.data,
            providesTags: ['inspector/opcache'],
        }),
        getCache: builder.query<CacheResponseType, string>({
            query: (key) => `cache?key=${key}`,
            transformResponse: (result: Response<CacheResponseType>) => result.data,
        }),
        deleteCache: builder.mutation<CacheResponseType, string>({
            query: (key) => ({url: `cache?key=${key}`, method: 'DELETE'}),
            transformResponse: (result: Response<CacheResponseType>) => result.data,
        }),
        clearCache: builder.mutation<CacheResponseType, void>({
            query: () => ({url: `cache/clear`, method: 'POST'}),
            transformResponse: (result: Response<CacheResponseType>) => result.data,
        }),
        postComposerRequirePackage: builder.mutation<
            CommandResponseType,
            {packageName: string; version: string | null; isDev: boolean}
        >({
            query: ({packageName, version, isDev}) => ({
                url: `composer/require`,
                method: 'POST',
                body: {package: packageName, version, isDev},
            }),
            transformResponse: (result: Response<CommandResponseType>) => result.data,
            invalidatesTags: ['inspector/composer'],
        }),
        getAuthorization: builder.query<AuthorizationResponse, void>({
            query: () => `authorization`,
            transformResponse: (result: Response<AuthorizationResponse>) => result.data,
        }),
        getElasticsearchHealth: builder.query<Response, void>({
            query: () => `elasticsearch`,
            transformResponse: (result: Response) => result.data || [],
            providesTags: ['inspector/elasticsearch'],
        }),
        getElasticsearchIndex: builder.query<Response, string>({
            query: (name) => `elasticsearch/${name}`,
            transformResponse: (result: Response) => result.data,
            providesTags: ['inspector/elasticsearch'],
        }),
        searchElasticsearch: builder.mutation<
            Response,
            {index: string; query?: Record<string, any>; limit?: number; offset?: number}
        >({
            query: ({index, query, limit, offset}) => ({
                url: `elasticsearch/search`,
                method: 'POST',
                body: {index, query: query ?? {}, limit: limit ?? 50, offset: offset ?? 0},
            }),
            transformResponse: (result: Response) => result.data,
        }),
        executeElasticsearchQuery: builder.mutation<
            Response,
            {method: string; endpoint: string; body?: Record<string, any>}
        >({
            query: ({method, endpoint, body}) => ({
                url: `elasticsearch/query`,
                method: 'POST',
                body: {method, endpoint, body: body ?? {}},
            }),
            transformResponse: (result: Response) => result.data,
        }),
        getRedisPing: builder.query<{result: any}, void>({
            query: () => `redis/ping`,
            transformResponse: (result: Response<{result: any}>) => result.data,
            providesTags: ['inspector/redis'],
        }),
        getRedisInfo: builder.query<Record<string, any>, string | void>({
            query: (section) => (section ? `redis/info?section=${section}` : `redis/info`),
            transformResponse: (result: Response<Record<string, any>>) => result.data,
            providesTags: ['inspector/redis'],
        }),
        getRedisDbSize: builder.query<{size: number}, void>({
            query: () => `redis/db-size`,
            transformResponse: (result: Response<{size: number}>) => result.data,
            providesTags: ['inspector/redis'],
        }),
        getRedisKeys: builder.query<
            {keys: string[]; cursor: number},
            {pattern?: string; limit?: number; cursor?: number}
        >({
            query: ({pattern = '*', limit = 100, cursor = 0} = {}) =>
                `redis/keys?pattern=${encodeURIComponent(pattern)}&limit=${limit}&cursor=${cursor}`,
            transformResponse: (result: Response<{keys: string[]; cursor: number}>) => result.data,
            providesTags: ['inspector/redis'],
        }),
        getRedisKey: builder.query<{key: string; type: string; ttl: number; value: any}, string>({
            query: (key) => `redis/get?key=${encodeURIComponent(key)}`,
            transformResponse: (result: Response<{key: string; type: string; ttl: number; value: any}>) => result.data,
            providesTags: ['inspector/redis'],
        }),
        deleteRedisKey: builder.mutation<{result: any}, string>({
            query: (key) => ({url: `redis/delete?key=${encodeURIComponent(key)}`, method: 'DELETE'}),
            transformResponse: (result: Response<{result: any}>) => result.data,
            invalidatesTags: ['inspector/redis'],
        }),
        flushRedisDb: builder.mutation<{result: any}, void>({
            query: () => ({url: `redis/flush-db`, method: 'POST'}),
            transformResponse: (result: Response<{result: any}>) => result.data,
            invalidatesTags: ['inspector/redis'],
        }),
        getCoverage: builder.query<CoverageResponse, void>({
            query: () => `coverage`,
            transformResponse: (result: Response<CoverageResponse>) => result.data,
        }),
        getMcpSettings: builder.query<{enabled: boolean}, void>({
            query: () => `mcp/settings`,
            transformResponse: (result: Response<{enabled: boolean}>) => result.data,
            providesTags: ['inspector/mcp'],
        }),
        updateMcpSettings: builder.mutation<{enabled: boolean}, {enabled: boolean}>({
            query: (body) => ({url: `mcp/settings`, method: 'PUT', body}),
            transformResponse: (result: Response<{enabled: boolean}>) => result.data,
            invalidatesTags: ['inspector/mcp'],
        }),
        getHttpMockStatus: builder.query<HttpMockStatus, void>({
            query: () => `http-mock/status`,
            transformResponse: (result: Response<HttpMockStatus>) => result.data,
            providesTags: ['inspector/http-mock'],
        }),
        getHttpMockExpectations: builder.query<HttpMockExpectation[], void>({
            query: () => `http-mock/expectations`,
            transformResponse: (result: Response<HttpMockExpectation[]>) => result.data ?? [],
            providesTags: ['inspector/http-mock'],
        }),
        createHttpMockExpectation: builder.mutation<{success: boolean}, HttpMockExpectation>({
            query: (body) => ({url: `http-mock/expectations`, method: 'POST', body}),
            transformResponse: (result: Response<{success: boolean}>) => result.data,
            invalidatesTags: ['inspector/http-mock'],
        }),
        clearHttpMockExpectations: builder.mutation<{success: boolean}, void>({
            query: () => ({url: `http-mock/expectations`, method: 'DELETE'}),
            transformResponse: (result: Response<{success: boolean}>) => result.data,
            invalidatesTags: ['inspector/http-mock'],
        }),
        verifyHttpMockRequest: builder.mutation<{count: number}, Record<string, unknown>>({
            query: (body) => ({url: `http-mock/verify`, method: 'POST', body}),
            transformResponse: (result: Response<{count: number}>) => result.data,
        }),
        getHttpMockHistory: builder.query<HttpMockHistoryEntry[], void>({
            query: () => `http-mock/history`,
            transformResponse: (result: Response<HttpMockHistoryEntry[]>) => result.data ?? [],
            providesTags: ['inspector/http-mock'],
        }),
        resetHttpMock: builder.mutation<{success: boolean}, void>({
            query: () => ({url: `http-mock/reset`, method: 'POST'}),
            transformResponse: (result: Response<{success: boolean}>) => result.data,
            invalidatesTags: ['inspector/http-mock'],
        }),
    }),
});

export const {
    useGetParametersQuery,
    useLazyGetParametersQuery,
    useGetConfigurationQuery,
    useGetObjectQuery,
    useGetClassesQuery,
    useLazyGetObjectQuery,
    useLazyGetFilesQuery,
    useLazyGetClassQuery,
    useLazyGetCommandsQuery,
    useRunCommandMutation,
    useGetTranslationsQuery,
    usePutTranslationsMutation,
    useDoRequestMutation,
    useGetRoutesQuery,
    useLazyGetCheckRouteQuery,
    useGetTableQuery,
    useGetTableDataQuery,
    useGetPhpInfoQuery,
    useGetComposerQuery,
    useGetCacheQuery,
    useDeleteCacheMutation,
    useLazyGetCacheQuery,
    useClearCacheMutation,
    useLazyGetComposerInspectQuery,
    useGetComposerInspectQuery,
    usePostComposerRequirePackageMutation,
    usePostCurlBuildMutation,
    useGetEventsQuery,
    useGetOpcacheQuery,
    useExplainQueryMutation,
    useExecuteQueryMutation,
    useGetMcpSettingsQuery,
    useGetAuthorizationQuery,
    useUpdateMcpSettingsMutation,
    useGetElasticsearchHealthQuery,
    useGetElasticsearchIndexQuery,
    useSearchElasticsearchMutation,
    useExecuteElasticsearchQueryMutation,
    useGetRedisPingQuery,
    useGetRedisInfoQuery,
    useGetRedisDbSizeQuery,
    useGetRedisKeysQuery,
    useLazyGetRedisKeyQuery,
    useDeleteRedisKeyMutation,
    useFlushRedisDbMutation,
    useGetCoverageQuery,
    useGetHttpMockStatusQuery,
    useGetHttpMockExpectationsQuery,
    useCreateHttpMockExpectationMutation,
    useClearHttpMockExpectationsMutation,
    useVerifyHttpMockRequestMutation,
    useGetHttpMockHistoryQuery,
    useResetHttpMockMutation,
} = inspectorApi;
