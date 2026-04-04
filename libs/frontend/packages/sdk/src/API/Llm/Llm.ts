import {createBaseQuery} from '@app-dev-panel/sdk/API/createBaseQuery';
import {createApi} from '@reduxjs/toolkit/query/react';

export type LlmProvider = 'openrouter' | 'anthropic' | 'openai';

export type LlmStatus = {
    connected: boolean;
    provider: string;
    model: string | null;
    timeout: number;
    customPrompt: string;
};

export type LlmModel = {id: string; name: string; context_length: number; pricing: Record<string, string>};

type OAuthInitiateResponse = {authUrl: string; codeVerifier: string};

type OAuthExchangeResponse = {connected: boolean; error?: string};

type ConnectRequest = {provider: LlmProvider; apiKey: string};

type ConnectResponse = {connected: boolean; provider: string};

type ModelsResponse = {models: LlmModel[]};

export type ChatMessage = {role: 'system' | 'user' | 'assistant'; content: string};

export type ChatRequest = {messages: ChatMessage[]; model?: string; temperature?: number};

export type ChatResponse = {choices?: Array<{message?: {content?: string}}>; error?: unknown};

type AnalyzeRequest = {context: Record<string, unknown>; prompt?: string};

type AnalyzeResponse = {analysis: string; model: string};

export type HistoryEntry = {query: string; response: string; timestamp: number; error?: string};

type AddHistoryRequest = {query: string; response: string; timestamp: number; error?: string};

export const llmApi = createApi({
    reducerPath: 'api.llm',
    baseQuery: createBaseQuery('/debug/api/llm'),
    tagTypes: ['llm/status', 'llm/history'],
    endpoints: (builder) => ({
        getStatus: builder.query<LlmStatus, void>({
            query: () => '/status',
            transformResponse: (result: {data: LlmStatus}) => result.data,
            providesTags: ['llm/status'],
        }),
        connect: builder.mutation<ConnectResponse, ConnectRequest>({
            query: (body) => ({url: '/connect', method: 'POST', body}),
            transformResponse: (result: {data: ConnectResponse}) => result.data,
            invalidatesTags: ['llm/status'],
        }),
        oauthInitiate: builder.mutation<OAuthInitiateResponse, {callbackUrl: string}>({
            query: (body) => ({url: '/oauth/initiate', method: 'POST', body}),
            transformResponse: (result: {data: OAuthInitiateResponse}) => result.data,
        }),
        oauthExchange: builder.mutation<OAuthExchangeResponse, {code: string; codeVerifier: string}>({
            query: (body) => ({url: '/oauth/exchange', method: 'POST', body}),
            transformResponse: (result: {data: OAuthExchangeResponse}) => result.data,
            invalidatesTags: ['llm/status'],
        }),
        disconnect: builder.mutation<{connected: boolean}, void>({
            query: () => ({url: '/disconnect', method: 'POST'}),
            invalidatesTags: ['llm/status'],
        }),
        getModels: builder.query<LlmModel[], void>({
            query: () => '/models',
            transformResponse: (result: {data: ModelsResponse}) => result.data.models,
        }),
        setModel: builder.mutation<LlmStatus, {model: string}>({
            query: (body) => ({url: '/model', method: 'POST', body}),
            transformResponse: (result: {data: LlmStatus}) => result.data,
            invalidatesTags: ['llm/status'],
        }),
        setTimeout: builder.mutation<LlmStatus, {timeout: number}>({
            query: (body) => ({url: '/timeout', method: 'POST', body}),
            transformResponse: (result: {data: LlmStatus}) => result.data,
            invalidatesTags: ['llm/status'],
        }),
        setCustomPrompt: builder.mutation<LlmStatus, {customPrompt: string}>({
            query: (body) => ({url: '/custom-prompt', method: 'POST', body}),
            transformResponse: (result: {data: LlmStatus}) => result.data,
            invalidatesTags: ['llm/status'],
        }),
        chat: builder.mutation<ChatResponse, ChatRequest>({
            query: (body) => ({url: '/chat', method: 'POST', body}),
            transformResponse: (result: {data: ChatResponse}) => result.data,
        }),
        analyze: builder.mutation<AnalyzeResponse, AnalyzeRequest>({
            query: (body) => ({url: '/analyze', method: 'POST', body}),
            transformResponse: (result: {data: AnalyzeResponse}) => result.data,
        }),
        getHistory: builder.query<HistoryEntry[], void>({
            query: () => '/history',
            transformResponse: (result: {data: HistoryEntry[]}) => result.data,
            providesTags: ['llm/history'],
        }),
        addHistory: builder.mutation<HistoryEntry[], AddHistoryRequest>({
            query: (body) => ({url: '/history', method: 'POST', body}),
            transformResponse: (result: {data: HistoryEntry[]}) => result.data,
            invalidatesTags: ['llm/history'],
        }),
        deleteHistory: builder.mutation<HistoryEntry[], number>({
            query: (index) => ({url: `/history/${index}`, method: 'DELETE'}),
            transformResponse: (result: {data: HistoryEntry[]}) => result.data,
            invalidatesTags: ['llm/history'],
        }),
        clearHistory: builder.mutation<HistoryEntry[], void>({
            query: () => ({url: '/history', method: 'DELETE'}),
            transformResponse: (result: {data: HistoryEntry[]}) => result.data,
            invalidatesTags: ['llm/history'],
        }),
    }),
});

export const {
    useGetStatusQuery,
    useConnectMutation,
    useOauthInitiateMutation,
    useOauthExchangeMutation,
    useDisconnectMutation,
    useGetModelsQuery,
    useSetModelMutation,
    useSetTimeoutMutation,
    useSetCustomPromptMutation,
    useChatMutation,
    useAnalyzeMutation,
    useGetHistoryQuery,
    useAddHistoryMutation,
    useDeleteHistoryMutation,
    useClearHistoryMutation,
} = llmApi;
