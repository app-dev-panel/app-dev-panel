import {ModuleInterface} from '@yiisoft/yii-dev-panel-sdk/Types/Module.types';
import {middlewares, reducers} from '@yiisoft/yii-dev-panel/Module/Gii/api';
import {routes} from '@yiisoft/yii-dev-panel/Module/Gii/router';

export const GiiModule: ModuleInterface = {
    routes: routes,
    reducers: reducers,
    middlewares: middlewares,
    standaloneModule: false,
};
