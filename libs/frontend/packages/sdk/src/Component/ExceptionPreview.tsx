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

export type ExceptionFileContent = {content: string; extension: string};

export type ExceptionPreviewProps = {
    class: string;
    message: string;
    line: string;
    file: string;
    code: string;
    trace: unknown[];
    traceAsString: string;
    /**
     * Optional loader for the source file contents. When provided,
     * the file is fetched and rendered with a CodeHighlight window
     * around the failing line. Consumers without access to a file API
     * (e.g. the embeddable toolbar) can omit this prop to skip code preview.
     */
    fetchFileContent?: (path: string) => Promise<ExceptionFileContent | null>;
};

export const ExceptionPreview = (props: ExceptionPreviewProps) => {
    const [file, setFile] = useState<ExceptionFileContent | null>(null);
    const {fetchFileContent} = props;

    useEffect(() => {
        if (!fetchFileContent) {
            return;
        }
        let cancelled = false;
        (async () => {
            const result = await fetchFileContent(parseFilePath(props.file));
            if (!cancelled) {
                setFile(result);
            }
        })();
        return () => {
            cancelled = true;
        };
    }, [props.file, fetchFileContent]);

    const lineNumber = +props.line;

    return (
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
    );
};
