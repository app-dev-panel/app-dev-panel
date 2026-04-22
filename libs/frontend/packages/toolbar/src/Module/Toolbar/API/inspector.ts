import {createBaseQuery} from '@app-dev-panel/sdk/API/createBaseQuery';
import {createApi} from '@reduxjs/toolkit/query/react';

type Response<T = unknown> = {data: T};

export type CurlBuilderResponse = {command: string};

export const toolbarInspectorApi = createApi({
    reducerPath: 'api.toolbar.inspector',
    baseQuery: createBaseQuery('/debug/api/inspector/'),
    endpoints: (builder) => ({
        doRequest: builder.mutation<unknown, {id: string}>({
            query: (args) => ({method: 'PUT', url: `request?debugEntryId=${args.id}`}),
            transformResponse: (result: Response) => result.data ?? null,
        }),
        postCurlBuild: builder.mutation<CurlBuilderResponse, string>({
            query: (debugEntryId) => ({method: 'POST', url: `curl/build?debugEntryId=${debugEntryId}`}),
            transformResponse: (result: Response<CurlBuilderResponse>) => result.data,
        }),
    }),
});

export const {useDoRequestMutation, usePostCurlBuildMutation} = toolbarInspectorApi;
