import {ApplicationModule} from '@app-dev-panel/panel/Application';
import {DebugModule} from '@app-dev-panel/panel/Module/Debug';
import {FramesModule} from '@app-dev-panel/panel/Module/Frames';
import {GiiModule} from '@app-dev-panel/panel/Module/Gii';
import {InspectorModule} from '@app-dev-panel/panel/Module/Inspector';
import {OpenApiModule} from '@app-dev-panel/panel/Module/OpenApi';

export const modules = [ApplicationModule, DebugModule, GiiModule, InspectorModule, OpenApiModule, FramesModule];
