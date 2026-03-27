import {llmApi} from '@app-dev-panel/panel/Module/Llm/API/Llm';

export const reducers = {[llmApi.reducerPath]: llmApi.reducer};
export const middlewares = [llmApi.middleware];
