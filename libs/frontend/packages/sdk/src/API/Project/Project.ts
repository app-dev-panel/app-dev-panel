import {createBaseQuery} from '@app-dev-panel/sdk/API/createBaseQuery';
import {createApi} from '@reduxjs/toolkit/query/react';

/**
 * Project-level configuration shared via VCS (`config/adp/project.json`).
 * Frames and OpenAPI specs travel with the codebase so every developer
 * picks up the same setup after `git pull`.
 */
export type ProjectConfigDocument = {version: number; frames: Record<string, string>; openapi: Record<string, string>};

export type ProjectConfigResponse = {config: ProjectConfigDocument; configDir: string};

export type ProjectConfigPayload = {frames: Record<string, string>; openapi: Record<string, string>};

export const projectApi = createApi({
    reducerPath: 'api.project',
    baseQuery: createBaseQuery('/debug/api/project'),
    tagTypes: ['project/config'],
    endpoints: (builder) => ({
        getProjectConfig: builder.query<ProjectConfigResponse, void>({
            query: () => '/config',
            transformResponse: (result: {data: ProjectConfigResponse}) => result.data,
            providesTags: ['project/config'],
        }),
        updateProjectConfig: builder.mutation<ProjectConfigResponse, ProjectConfigPayload>({
            query: (body) => ({url: '/config', method: 'PUT', body}),
            transformResponse: (result: {data: ProjectConfigResponse}) => result.data,
            invalidatesTags: ['project/config'],
        }),
    }),
});

export const {useGetProjectConfigQuery, useUpdateProjectConfigMutation} = projectApi;
