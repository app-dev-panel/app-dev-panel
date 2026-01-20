import {middlewares, reducers} from '@yiisoft/yii-dev-panel-sdk/API/Debug/api';
import {ModuleInterface} from '@yiisoft/yii-dev-panel-sdk/Types/Module.types';
import {routes} from '@yiisoft/yii-dev-panel/Module/Debug/router';

export const DebugModule: ModuleInterface = {
    routes: routes,
    reducers: reducers,
    middlewares: middlewares,
    standaloneModule: false,
};
