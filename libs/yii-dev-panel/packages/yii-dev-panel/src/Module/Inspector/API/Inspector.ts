import {createApi} from '@reduxjs/toolkit/query/react';
import {createBaseQuery} from '@yiisoft/yii-dev-panel-sdk/API/createBaseQuery';

type ObjectType = {
    object: object;
    path: string;
};
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
} & InspectorFile;

export type ConfigurationType = Record<string, object | string>;
export type ClassesType = string[];
export type CommandType = {
    name: string;
    title: string;
    group: string;
    description: string;
};
export type CommandResponseType<T = any> = {
    status: 'ok' | 'error' | 'fail';
    result: T;
    errors: string[];
};
export type CacheResponseType = any;
export type PutTranslationArgumentType = {
    category: string;
    locale: string;
    translation: string;
    message: string;
};

type ComposerResponse = {
    json: {require: Record<string, string>; 'require-dev': Record<string, string>};
    lock: {
        packages: {name: string; version: string}[];
        'packages-dev': {name: string; version: string}[];
    };
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
        version: {
            version: string;
            opcache_product_name: 'Zend OPcache';
        };
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

type CurlBuilderResponse = {
    command: string;
};

type CheckRouteResponse = {
    result: boolean;
    action: string[];
};

export type EventListenerType = {
    event: [string, string] | string;
};

export type EventListenersType = Record<string, EventListenerType[]>;

export type EventsResponse = {
    common: EventListenersType;
    console: EventListenersType;
    web: EventListenersType;
};

type Response<T = any> = {
    data: T;
};

export const inspectorApi = createApi({
    reducerPath: 'api.inspector',
    keepUnusedDataFor: 0,
    tagTypes: ['inspector/composer', 'inspector/opcache'],
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
            query: (command) => 'command',
            transformResponse: (result: Response<CommandType[]>) => result.data || [],
        }),
        runCommand: builder.mutation<CommandResponseType, string>({
            query: (command) => ({
                url: `command?command=${command}`,
                method: 'POST',
            }),
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
            query: (body) => ({
                method: 'PUT',
                url: `translations`,
                body: body,
            }),
            transformResponse: (result: Response) => result.data || [],
        }),
        getTable: builder.query<Response, string | void>({
            query: (table) => (table ? `table/${table}` : `table`),
            transformResponse: (result: Response) => result.data || [],
        }),
        doRequest: builder.mutation<Response, {id: string}>({
            query: (args) => ({
                method: 'PUT',
                url: `request?debugEntryId=${args.id}`,
            }),
            transformResponse: (result: Response) => result.data || [],
        }),
        postCurlBuild: builder.mutation<CurlBuilderResponse, string>({
            query: (debugEntryId) => ({
                method: 'POST',
                url: `curl/build?debugEntryId=${debugEntryId}`,
            }),
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
            query: (key) => ({
                url: `cache?key=${key}`,
                method: 'DELETE',
            }),
            transformResponse: (result: Response<CacheResponseType>) => result.data,
        }),
        clearCache: builder.mutation<CacheResponseType, void>({
            query: () => ({
                url: `cache/clear`,
                method: 'POST',
            }),
            transformResponse: (result: Response<CacheResponseType>) => result.data,
        }),
        postComposerRequirePackage: builder.mutation<
            CommandResponseType,
            {packageName: string; version: string | null; isDev: boolean}
        >({
            query: ({packageName, version, isDev}) => ({
                url: `composer/require`,
                method: 'POST',
                body: {
                    package: packageName,
                    version,
                    isDev,
                },
            }),
            transformResponse: (result: Response<CommandResponseType>) => result.data,
            invalidatesTags: ['inspector/composer'],
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
} = inspectorApi;
