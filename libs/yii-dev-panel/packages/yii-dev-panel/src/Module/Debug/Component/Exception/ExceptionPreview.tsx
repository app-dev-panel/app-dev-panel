import {OpenInNew} from '@mui/icons-material';
import ExpandMoreIcon from '@mui/icons-material/ExpandMore';
import {
    Accordion,
    AccordionDetails,
    AccordionSummary,
    Alert,
    AlertTitle,
    Button,
    Stack,
    Tooltip,
    Typography,
} from '@mui/material';
import {CodeHighlight} from '@yiisoft/yii-dev-panel-sdk/Component/CodeHighlight';
import {parseFilePath, parseFilename} from '@yiisoft/yii-dev-panel-sdk/Helper/filePathParser';
import {InspectorFileContent, useLazyGetFilesQuery} from '@yiisoft/yii-dev-panel/Module/Inspector/API/Inspector';
import {useEffect, useState} from 'react';

type ExceptionPreview = {
    class: string;
    message: string;
    line: string;
    file: string;
    code: string;
    trace: any[];
    traceAsString: string;
};
export const ExceptionPreview = (props: ExceptionPreview) => {
    const [lazyGetFilesQuery] = useLazyGetFilesQuery();
    const [file, setFile] = useState<InspectorFileContent | null>(null);

    useEffect(() => {
        (async () => {
            const response = await lazyGetFilesQuery(parseFilePath(props.file));

            setFile(response.data as any);
        })();
    }, [props.file]);

    const lineNumber = +props.line;

    return (
        <>
            <Accordion defaultExpanded={true}>
                <AccordionSummary expandIcon={<ExpandMoreIcon />}>
                    <Typography sx={{flex: '1 1 50%'}}>
                        {props.class}: {props.message}
                    </Typography>
                    <Typography>
                        {parseFilename(props.file)}:{props.line}
                    </Typography>
                </AccordionSummary>
                <AccordionDetails>
                    <Stack direction="row">
                        <Alert severity="error" sx={{flexGrow: 1}}>
                            <AlertTitle>{props.class}</AlertTitle>
                            {props.message}
                        </Alert>
                        <Stack>
                            <Tooltip title="Open in File Explorer">
                                <Button
                                    size="small"
                                    fullWidth
                                    href={`/inspector/files?class=${parseFilePath(props.class)}`}
                                    endIcon={<OpenInNew fontSize="small" />}
                                >
                                    Exception
                                </Button>
                            </Tooltip>
                            <Tooltip title="Open in File Explorer">
                                <Button
                                    size="small"
                                    fullWidth
                                    href={`/inspector/files?path=${parseFilePath(props.file)}#L${props.line}`}
                                    endIcon={<OpenInNew fontSize="small" />}
                                >
                                    Place
                                </Button>
                            </Tooltip>
                        </Stack>
                    </Stack>
                    {file && (
                        <CodeHighlight
                            language={file.extension}
                            code={file.content}
                            highlightLines={[lineNumber]}
                            highlightColor={'#ffcccc'}
                            wrappedLines={[lineNumber - 5, lineNumber + 5]}
                        />
                    )}
                    <Accordion>
                        <AccordionSummary expandIcon={<ExpandMoreIcon />}>Trace</AccordionSummary>
                        <AccordionDetails>
                            <CodeHighlight fontSize={10} language={'text/plain'} code={props.traceAsString || ''} />
                        </AccordionDetails>
                    </Accordion>
                </AccordionDetails>
            </Accordion>
        </>
    );
};
