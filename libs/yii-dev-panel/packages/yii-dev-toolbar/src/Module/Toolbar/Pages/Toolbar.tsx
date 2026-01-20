import {DebugToolbar} from '@yiisoft/yii-dev-toolbar/Module/Toolbar/Component/Toolbar/DebugToolbar';

export const Toolbar = () => {
    return <DebugToolbar activeComponents={{iframe: true}} />;
};
