import {InspectorFileContent, useLazyGetFilesQuery} from '@app-dev-panel/panel/Module/Inspector/API/Inspector';
import {CodeHighlight} from '@app-dev-panel/sdk/Component/CodeHighlight';
import {FileLink} from '@app-dev-panel/sdk/Component/FileLink';
import {StackTrace} from '@app-dev-panel/sdk/Component/StackTrace';
import {parseFilePath, parseFilename} from '@app-dev-panel/sdk/Helper/filePathParser';
import ExpandMoreIcon from '@mui/icons-material/ExpandMore';
import {
    Accordion,
    AccordionDetails,
    AccordionSummary,
    Alert,
    AlertTitle,
    Button,
    Stack,
    Typography,
} from '@mui/material';
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
                            <FileLink className={parseFilePath(props.class)}>
                                <Button size="small" fullWidth>
                                    Exception
                                </Button>
                            </FileLink>
                            <FileLink path={props.file} line={+props.line}>
                                <Button size="small" fullWidth>
                                    Place
                                </Button>
                            </FileLink>
                        </Stack>
                    </Stack>
                    {file && (
                        <CodeHighlight
                            language={file.extension}
                            code={file.content}
                            highlightLines={[lineNumber]}
                            wrappedLines={[lineNumber - 5, lineNumber + 5]}
                            filePath={parseFilePath(props.file)}
                        />
                    )}
                    <Accordion>
                        <AccordionSummary expandIcon={<ExpandMoreIcon />}>Trace</AccordionSummary>
                        <AccordionDetails>
                            <StackTrace trace={props.traceAsString || ''} fontSize={10} />
                        </AccordionDetails>
                    </Accordion>
                </AccordionDetails>
            </Accordion>
        </>
    );
};
