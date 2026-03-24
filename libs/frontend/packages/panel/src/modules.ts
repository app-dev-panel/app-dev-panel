import {ApplicationModule} from '@app-dev-panel/panel/Application';
import {DebugModule} from '@app-dev-panel/panel/Module/Debug';
import {FramesModule} from '@app-dev-panel/panel/Module/Frames';
import {GenCodeModule} from '@app-dev-panel/panel/Module/GenCode';
import {InspectorModule} from '@app-dev-panel/panel/Module/Inspector';
import {OpenApiModule} from '@app-dev-panel/panel/Module/OpenApi';

export const modules = [ApplicationModule, DebugModule, GenCodeModule, InspectorModule, OpenApiModule, FramesModule];
