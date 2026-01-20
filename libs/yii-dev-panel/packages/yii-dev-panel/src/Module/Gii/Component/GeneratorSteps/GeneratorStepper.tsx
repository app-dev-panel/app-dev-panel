import {StepContent} from '@mui/material';
import Step from '@mui/material/Step';
import StepLabel from '@mui/material/StepLabel';
import Stepper from '@mui/material/Stepper';
import {GiiGenerator} from '@yiisoft/yii-dev-panel/Module/Gii/API/Gii';
import {GenerateStep} from '@yiisoft/yii-dev-panel/Module/Gii/Component/GeneratorSteps/GenerateStep';
import {PreviewStep} from '@yiisoft/yii-dev-panel/Module/Gii/Component/GeneratorSteps/PreviewStep';
import {ResultStep} from '@yiisoft/yii-dev-panel/Module/Gii/Component/GeneratorSteps/ResultStep';
import {Context} from '@yiisoft/yii-dev-panel/Module/Gii/Context/Context';
import * as React from 'react';
import {useContext, useEffect} from 'react';
import {useDispatch} from 'react-redux';

const steps = [
    {
        component: PreviewStep,
        label: 'Preview',
    },
    {
        component: GenerateStep,
        label: 'Generate',
    },
    {
        component: ResultStep,
        label: 'Result',
    },
];

type GeneratorStepperProps = {
    generator: GiiGenerator
};

export const GeneratorStepper = ({generator}: GeneratorStepperProps) => {
    const [activeStepIndex, setActiveStepIndex] = React.useState(0);
    const context = useContext(Context);

    const handleNext = async () => {
        setActiveStepIndex((prev) => prev + 1);
    };

    const handleReset = () => {
        setActiveStepIndex(0);
    };
    useEffect(() => {
        handleReset();
        // @ts-ignore
        context.reset();
        // dispatch(context.reset())
    }, [generator]);

    return (
        <Stepper activeStep={activeStepIndex} orientation='vertical'>
            {Object.values(steps).map((step, index) => (
                <Step key={index}>
                    <StepLabel>{step.label}</StepLabel>
                    <StepContent>
                        <step.component
                            generator={generator}
                            onComplete={() => {
                                if (index === steps.length - 1) {
                                    return handleReset();
                                }
                                return handleNext();
                            }}
                        />
                    </StepContent>
                </Step>
            ))}
        </Stepper>
    );
};
