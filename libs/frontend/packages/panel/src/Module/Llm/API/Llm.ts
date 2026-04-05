// Re-export session helpers
export {clearAcpSessionId, getAcpSessionId} from '@app-dev-panel/sdk/API/Llm/acpSession';

// Re-export everything from the shared SDK LLM API
export {
    llmApi,
    useAddHistoryMutation,
    useAnalyzeMutation,
    useChatMutation,
    useClearHistoryMutation,
    useConnectMutation,
    useDeleteHistoryMutation,
    useDisconnectMutation,
    useGetHistoryQuery,
    useGetModelsQuery,
    useGetStatusQuery,
    useOauthExchangeMutation,
    useOauthInitiateMutation,
    useSetCustomPromptMutation,
    useSetModelMutation,
    useSetTimeoutMutation,
} from '@app-dev-panel/sdk/API/Llm/Llm';
export type {
    ChatMessage,
    ChatRequest,
    ChatResponse,
    HistoryEntry,
    LlmModel,
    LlmProvider,
    LlmStatus,
} from '@app-dev-panel/sdk/API/Llm/Llm';
