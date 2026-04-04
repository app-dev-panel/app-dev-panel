import {llmApi} from '@app-dev-panel/sdk/API/Llm/Llm';

export const reducers = {[llmApi.reducerPath]: llmApi.reducer};
export const middlewares = [llmApi.middleware];
