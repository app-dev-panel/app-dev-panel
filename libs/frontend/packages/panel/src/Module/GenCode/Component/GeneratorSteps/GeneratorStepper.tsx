import {GenCodeGenerator} from '@app-dev-panel/panel/Module/GenCode/API/GenCode';
import {GenerateStep} from '@app-dev-panel/panel/Module/GenCode/Component/GeneratorSteps/GenerateStep';
import {PreviewStep} from '@app-dev-panel/panel/Module/GenCode/Component/GeneratorSteps/PreviewStep';
import {ResultStep} from '@app-dev-panel/panel/Module/GenCode/Component/GeneratorSteps/ResultStep';
import {Context} from '@app-dev-panel/panel/Module/GenCode/Context/Context';
import {StepContent} from '@mui/material';
import Step from '@mui/material/Step';
import StepLabel from '@mui/material/StepLabel';
import Stepper from '@mui/material/Stepper';
import * as React from 'react';
import {useContext, useEffect} from 'react';

const steps = [
    {component: PreviewStep, label: 'Preview'},
    {component: GenerateStep, label: 'Generate'},
    {component: ResultStep, label: 'Result'},
];

type GeneratorStepperProps = {generator: GenCodeGenerator};

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
        context.reset();
    }, [generator]);

    return (
        <Stepper activeStep={activeStepIndex} orientation="vertical">
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
