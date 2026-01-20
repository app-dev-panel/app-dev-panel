import {ModuleInterface} from '@yiisoft/yii-dev-panel-sdk/Types/Module.types';
import {middlewares, reducers} from '@yiisoft/yii-dev-panel/Module/Frames/api';
import {routes} from '@yiisoft/yii-dev-panel/Module/Frames/router';

export const FramesModule: ModuleInterface = {
    routes: routes,
    reducers: reducers,
    middlewares: middlewares,
    standaloneModule: false,
};
