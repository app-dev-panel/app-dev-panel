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

/**
 * Local-only secrets — sensitive values returned MASKED (`apiKey: '...wxyz'`).
 * `hasApiKey` / `hasAcpArgs` boolean flags let the UI render
 * "configured / empty" without ever loading the real key.
 */
export type SecretsDocument = {
    apiKey?: string | null;
    hasApiKey?: boolean;
    provider?: string;
    model?: string | null;
    timeout?: number;
    customPrompt?: string;
    acpCommand?: string;
    acpArgs?: string[];
    hasAcpArgs?: boolean;
    acpEnv?: Record<string, string>;
};

export type SecretsResponse = {secrets: SecretsDocument; configDir: string};

/**
 * PATCH semantics: only fields present are updated, `null` deletes a key,
 * missing keys are left alone. The masked GET document is intentionally
 * non-roundtrippable so we never PUT — every save is a PATCH.
 */
export type SecretsPatchPayload = {
    llm?: {
        apiKey?: string | null;
        provider?: string | null;
        model?: string | null;
        timeout?: number | null;
        customPrompt?: string | null;
        acpCommand?: string | null;
        acpArgs?: string[] | null;
        acpEnv?: Record<string, string> | null;
    };
};

export const projectApi = createApi({
    reducerPath: 'api.project',
    baseQuery: createBaseQuery('/debug/api/project'),
    tagTypes: ['project/config', 'project/secrets'],
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
        getSecrets: builder.query<SecretsResponse, void>({
            query: () => '/secrets',
            transformResponse: (result: {data: SecretsResponse}) => result.data,
            providesTags: ['project/secrets'],
        }),
        patchSecrets: builder.mutation<SecretsResponse, SecretsPatchPayload>({
            query: (body) => ({url: '/secrets', method: 'PATCH', body}),
            transformResponse: (result: {data: SecretsResponse}) => result.data,
            invalidatesTags: ['project/secrets'],
        }),
    }),
});

export const {useGetProjectConfigQuery, useUpdateProjectConfigMutation, useGetSecretsQuery, usePatchSecretsMutation} =
    projectApi;
