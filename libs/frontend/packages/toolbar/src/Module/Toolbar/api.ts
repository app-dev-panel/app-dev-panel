import {toolbarInspectorApi} from '@app-dev-panel/toolbar/Module/Toolbar/API/inspector';

export const reducers = {[toolbarInspectorApi.reducerPath]: toolbarInspectorApi.reducer};

export const middlewares = [toolbarInspectorApi.middleware];
