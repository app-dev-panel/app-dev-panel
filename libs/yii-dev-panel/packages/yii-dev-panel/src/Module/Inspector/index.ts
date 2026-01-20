import {ModuleInterface} from '@yiisoft/yii-dev-panel-sdk/Types/Module.types';
import {middlewares, reducers} from '@yiisoft/yii-dev-panel/Module/Inspector/api';
import {routes} from '@yiisoft/yii-dev-panel/Module/Inspector/router';

export const InspectorModule: ModuleInterface = {
    routes: routes,
    reducers: reducers,
    middlewares: middlewares,
    standaloneModule: false,
};
