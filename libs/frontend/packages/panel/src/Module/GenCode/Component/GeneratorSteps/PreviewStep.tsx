import {usePostPreviewMutation} from '@app-dev-panel/panel/Module/GenCode/API/GenCode';
import {FormInput} from '@app-dev-panel/panel/Module/GenCode/Component/FormInput';
import {StepProps} from '@app-dev-panel/panel/Module/GenCode/Component/GeneratorSteps/Step.types';
import {mapErrorsToForm} from '@app-dev-panel/panel/Module/GenCode/Component/errorMapper';
import {Context} from '@app-dev-panel/panel/Module/GenCode/Context/Context';
import {GenCodeFile} from '@app-dev-panel/panel/Module/GenCode/Types/FIle.types';
import {createYupValidationSchema} from '@app-dev-panel/sdk/Adapter/yup/yii.validator';
import {yupResolver} from '@hookform/resolvers/yup';
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
    }, [generator, form]);

    const [previewQuery] = usePostPreviewMutation();

    async function previewHandler(data: FieldValues) {
        const response = await previewQuery({generator: generator.id, parameters: data});
        if ('error' in response) {
            mapErrorsToForm(response, form);
            return;
        }

        if ('data' in response && response.data) {
            const result = response.data as unknown as {files: GenCodeFile[]; operations: Record<string, string>};
            context.setFiles(result.files);
            context.setParameters(data);
            context.setOperations(result.operations);
        }
        onComplete();
    }

    return (
        <FormProvider {...form}>
            <Box component="form" onReset={() => form.reset()} onSubmit={form.handleSubmit(previewHandler)} my={2}>
                {Object.entries(attributes).map(([attributeName, attribute]) => (
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
