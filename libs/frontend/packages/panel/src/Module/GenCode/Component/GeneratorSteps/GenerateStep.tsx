import {
    GenCodeGenerator,
    usePostDiffMutation,
    usePostGenerateMutation,
} from '@app-dev-panel/panel/Module/GenCode/API/GenCode';
import {FileDiffDialog} from '@app-dev-panel/panel/Module/GenCode/Component/FileDiffDialog';
import {FilePreviewDialog} from '@app-dev-panel/panel/Module/GenCode/Component/FilePreviewDialog';
import {StepProps} from '@app-dev-panel/panel/Module/GenCode/Component/GeneratorSteps/Step.types';
import {mapErrorsToForm} from '@app-dev-panel/panel/Module/GenCode/Component/errorMapper';
import {matchSeverityByFileState} from '@app-dev-panel/panel/Module/GenCode/Component/matchSeverity';
import {Context} from '@app-dev-panel/panel/Module/GenCode/Context/Context';
import {FileOperationEnum, FileStateEnum, GenCodeFile} from '@app-dev-panel/panel/Module/GenCode/Types/FIle.types';
import {GenCodeResult} from '@app-dev-panel/panel/Module/GenCode/Types/Result.types';
import {
    Box,
    Button,
    ButtonGroup,
    List,
    ListItem,
    ListItemSecondaryAction,
    ListItemText,
    ListSubheader,
    ToggleButton,
    ToggleButtonGroup,
    Typography,
} from '@mui/material';
import * as React from 'react';
import {useContext, useMemo, useState} from 'react';
import {FieldValues, FormProvider, useForm, useFormContext} from 'react-hook-form';

function getStateLabel(state: FileStateEnum) {
    let result = 'Unknown state';
    switch (state) {
        case FileStateEnum.PRESENT_SAME:
            result = 'Present same';
            break;
        case FileStateEnum.PRESENT_DIFFERENT:
            result = 'Present different';
            break;
        case FileStateEnum.NOT_EXIST:
            result = 'Does not exist';
            break;
    }
    return result;
}

function FileAction({file, generator}: {file: GenCodeFile; generator: GenCodeGenerator}) {
    const context = useContext(Context);
    const form = useFormContext();
    const [value, setValue] = useState(form.getValues(file.id));
    const [openPreviewDialog, setOpenPreviewDialog] = React.useState(false);
    const [openDiffDialog, setOpenDiffDialog] = React.useState(false);
    const [diffQuery] = usePostDiffMutation();
    const [diff, setDiff] = useState('');

    const handlePreviewDialogOpen = () => {
        setOpenPreviewDialog(true);
    };
    const handlePreviewDialogClose = () => {
        setOpenPreviewDialog(false);
    };
    const handleDiffDialogOpen = () => {
        setOpenDiffDialog(true);
    };
    const handleDiffDialogClose = () => {
        setOpenDiffDialog(false);
    };

    const handleDiff = async () => {
        const response = await diffQuery({generator: generator.id, parameters: context.parameters, fileId: file.id});
        if ('data' in response && response.data) {
            setDiff(response.data.diff ?? '');
        }
        handleDiffDialogOpen();
    };

    return (
        <>
            <ListItem>
                <ListItemText
                    primary={file.relativePath}
                    secondary={
                        <Typography component="span" color={matchSeverityByFileState(file.state) + '.main'}>
                            {getStateLabel(file.state)}
                        </Typography>
                    }
                />
                <ListItemSecondaryAction>
                    <Box mr={2} display="inline-block">
                        {file.state === FileStateEnum.NOT_EXIST ? (
                            <Button size="large" variant="contained" onClick={handlePreviewDialogOpen}>
                                Preview
                            </Button>
                        ) : file.state === FileStateEnum.PRESENT_DIFFERENT ? (
                            <Button size="large" variant="contained" onClick={handleDiff}>
                                Diff
                            </Button>
                        ) : null}
                    </Box>
                    <ToggleButtonGroup
                        value={value}
                        disabled={file.operation === FileOperationEnum.SKIP}
                        exclusive
                        onChange={(_, value) => {
                            setValue(value);
                            form.setValue(file.id, value);
                        }}
                    >
                        {Object.entries(context.operations).map(([index, operation]) => (
                            <ToggleButton key={index} value={index}>
                                {operation}
                            </ToggleButton>
                        ))}
                    </ToggleButtonGroup>
                </ListItemSecondaryAction>
            </ListItem>
            <FilePreviewDialog file={file} open={openPreviewDialog} onClose={handlePreviewDialogClose} />
            <FileDiffDialog file={file} content={diff} open={openDiffDialog} onClose={handleDiffDialogClose} />
        </>
    );
}

export function GenerateStep({generator, onComplete}: StepProps) {
    const context = useContext(Context);
    // TODO: add validation
    // const validationSchema = createValidationSchema(context.files);

    const defaultValues = useMemo(() => {
        return Object.fromEntries(context.files.map((file) => [file.id, String(file.operation)]));
    }, [context.files]);

    const form = useForm({
        // mode: "onBlur",
        // resolver: yupResolver(validationSchema),
        defaultValues: defaultValues,
    });
    const [generateQuery] = usePostGenerateMutation();

    async function generateHandler(data: FieldValues) {
        const response = await generateQuery({generator: generator.id, parameters: context.parameters, answers: data});
        if ('error' in response) {
            mapErrorsToForm(response, form);
            return;
        }

        if ('data' in response && response.data) {
            context.setResults(response.data as unknown as GenCodeResult[]);
        }

        onComplete();
    }

    return (
        <FormProvider {...form}>
            <Box component="form" onReset={() => form.reset()} onSubmit={form.handleSubmit(generateHandler)} my={2}>
                <List subheader={<ListSubheader>Operations</ListSubheader>}>
                    {context.files.map((file) => (
                        <FileAction key={file.id} file={file} generator={generator} />
                    ))}
                </List>

                <Box my={2}>
                    <ButtonGroup>
                        <Button type="submit" name="generate" variant="contained">
                            Generate
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
