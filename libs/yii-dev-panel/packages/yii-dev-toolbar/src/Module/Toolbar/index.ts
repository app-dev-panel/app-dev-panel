import {ModuleInterface} from '@yiisoft/yii-dev-panel-sdk/Types/Module.types';
import {middlewares, reducers} from '@yiisoft/yii-dev-toolbar/Module/Toolbar/api';
import {routes} from '@yiisoft/yii-dev-toolbar/Module/Toolbar/router';

export const ToolbarModule: ModuleInterface = {
    routes: routes,
    reducers: reducers,
    middlewares: middlewares,
    standaloneModule: true,
};
