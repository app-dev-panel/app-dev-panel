import {GiiGenerator} from '@yiisoft/yii-dev-panel/Module/Gii/API/Gii';

export type StepProps = {
    generator: GiiGenerator;
    onComplete: () => void;
};
