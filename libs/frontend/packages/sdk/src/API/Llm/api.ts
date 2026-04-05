import {AiChatSlice} from '@app-dev-panel/sdk/API/Llm/AiChatSlice';
import {llmApi} from '@app-dev-panel/sdk/API/Llm/Llm';

export const reducers = {[llmApi.reducerPath]: llmApi.reducer, [AiChatSlice.name]: AiChatSlice.reducer};
export const middlewares = [llmApi.middleware];
