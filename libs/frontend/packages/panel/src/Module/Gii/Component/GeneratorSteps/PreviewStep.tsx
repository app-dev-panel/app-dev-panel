import {usePostPreviewMutation} from '@app-dev-panel/panel/Module/Gii/API/Gii';
import {FormInput} from '@app-dev-panel/panel/Module/Gii/Component/FormInput';
import {StepProps} from '@app-dev-panel/panel/Module/Gii/Component/GeneratorSteps/Step.types';
import {mapErrorsToForm} from '@app-dev-panel/panel/Module/Gii/Component/errorMapper';
import {Context} from '@app-dev-panel/panel/Module/Gii/Context/Context';
import {createYupValidationSchema} from '@app-dev-panel/sdk/Adapter/yup/yii.validator';
import {yupResolver} from '@hookform/resolvers/yup/dist/yup';
import {Box, Button, ButtonGroup} from '@mui/material';
import {useContext, useEffect} from 'react';
import {FieldValues, FormProvider, useForm} from 'react-hook-form';

export function PreviewStep({generator, onComplete}: StepProps) {
    const attributes = generator.attributes;
    const validationSchema = createYupValidationSchema(attributes);
    const context = useContext(Context);

    const form = useForm({
        mode: 'onBlur',
        // todo: fix typ
        resolver: yupResolver(validationSchema as any),
    });

    useEffect(() => {
        form.reset();
    }, [generator]);

    const [previewQuery] = usePostPreviewMutation();

    async function previewHandler(data: FieldValues) {
        console.log('preview', data);
        const response = await previewQuery({generator: generator.id, parameters: data});
        console.log(response);
        if ('error' in response) {
            mapErrorsToForm(response, form);
            return;
        }

        // TODO: fix types
        // @ts-ignore
        context.setFiles(response.data.files);
        // @ts-ignore
        context.setParameters(data);
        // @ts-ignore
        context.setOperations(response.data.operations);
        onComplete();
    }

    return (
        <FormProvider {...form}>
            <Box component="form" onReset={form.reset} onSubmit={form.handleSubmit(previewHandler)} my={2}>
                {Object.entries(attributes).map(([attributeName, attribute], index) => (
                    <Box mb={1} key={attributeName}>
                        <FormInput attributeName={attributeName} attribute={attribute} />
                    </Box>
                ))}
                <Box my={2}>
                    <ButtonGroup>
                        <Button type="submit" name="preview" variant="contained">
                            Preview
                        </Button>
                        <Button type="reset" color="warning">
                            Reset
                        </Button>
                    </ButtonGroup>
                </Box>
            </Box>
        </FormProvider>
    );
}
