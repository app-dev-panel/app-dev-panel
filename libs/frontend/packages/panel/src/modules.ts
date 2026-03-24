import {ApplicationModule} from '@app-dev-panel/panel/Application';
import {DebugModule} from '@app-dev-panel/panel/Module/Debug';
import {FramesModule} from '@app-dev-panel/panel/Module/Frames';
import {InspectorModule} from '@app-dev-panel/panel/Module/Inspector';
import {LlmModule} from '@app-dev-panel/panel/Module/Llm';
import {OpenApiModule} from '@app-dev-panel/panel/Module/OpenApi';

export const modules = [
    ApplicationModule,
    DebugModule,
    InspectorModule,
    LlmModule,
    OpenApiModule,
    FramesModule,
];
