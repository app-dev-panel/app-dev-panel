import {CodeHighlight} from '@app-dev-panel/sdk/Component/CodeHighlight';
import {StackTrace} from '@app-dev-panel/sdk/Component/StackTrace';
import {parseFilePath, parseFilename} from '@app-dev-panel/sdk/Helper/filePathParser';
import {useEditorUrl} from '@app-dev-panel/sdk/Helper/useEditorUrl';
import BugReportIcon from '@mui/icons-material/BugReport';
import ExpandMoreIcon from '@mui/icons-material/ExpandMore';
import FmdGoodIcon from '@mui/icons-material/FmdGood';
import {Accordion, AccordionDetails, AccordionSummary, Box, Button, Divider, Stack, Typography} from '@mui/material';
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
    const getEditorUrl = useEditorUrl();
    const cleanFile = parseFilePath(props.file);
    const lineNumber = +props.line;
    const cleanClass = parseFilePath(props.class);
    const sourceEditorUrl = getEditorUrl(cleanFile, lineNumber);
    const sourceFallbackUrl = `/inspector/files?path=${encodeURIComponent(cleanFile)}#L${props.line}`;
    const classExplorerUrl = `/inspector/files?class=${encodeURIComponent(cleanClass)}`;
    const hasCode = props.code != null && String(props.code) !== '' && String(props.code) !== '0';

    useEffect(() => {
        if (!fetchFileContent) {
            return;
        }
        let cancelled = false;
        (async () => {
            const result = await fetchFileContent(cleanFile);
            if (!cancelled) {
                setFile(result);
            }
        })();
        return () => {
            cancelled = true;
        };
    }, [cleanFile, fetchFileContent]);

    return (
        <Box sx={{textAlign: 'left'}}>
            <Stack direction="row" alignItems="center" spacing={1} flexWrap="wrap" useFlexGap>
                <Typography
                    sx={{
                        fontFamily: "'JetBrains Mono', monospace",
                        fontSize: 13,
                        fontWeight: 700,
                        color: 'error.main',
                        wordBreak: 'break-all',
                    }}
                >
                    {props.class}
                </Typography>
                {hasCode && (
                    <Typography variant="caption" sx={{color: 'text.disabled'}}>
                        code {props.code}
                    </Typography>
                )}
                <Box sx={{flexGrow: 1}} />
                <Typography sx={{fontFamily: "'JetBrains Mono', monospace", fontSize: 12, color: 'text.secondary'}}>
                    {parseFilename(cleanFile)}:{props.line}
                </Typography>
            </Stack>
            <Typography
                sx={{
                    fontSize: 13,
                    color: 'text.primary',
                    mt: 1,
                    whiteSpace: 'pre-wrap',
                    wordBreak: 'break-word',
                    textAlign: 'left',
                }}
            >
                {props.message}
            </Typography>
            <Stack direction="row" spacing={1} sx={{mt: 1.5, justifyContent: 'flex-start'}} flexWrap="wrap" useFlexGap>
                <Button
                    size="small"
                    variant="outlined"
                    startIcon={<BugReportIcon />}
                    component="a"
                    href={classExplorerUrl}
                    target="_top"
                >
                    Exception
                </Button>
                <Button
                    size="small"
                    variant="outlined"
                    startIcon={<FmdGoodIcon />}
                    component="a"
                    href={sourceEditorUrl ?? sourceFallbackUrl}
                    target={sourceEditorUrl ? undefined : '_top'}
                >
                    Source
                </Button>
            </Stack>
            {file && (
                <Box sx={{mt: 2, borderRadius: 1, overflow: 'hidden', border: 1, borderColor: 'divider'}}>
                    <CodeHighlight
                        language={file.extension}
                        code={file.content}
                        highlightLines={[lineNumber]}
                        wrappedLines={[lineNumber - 5, lineNumber + 5]}
                        filePath={cleanFile}
                    />
                </Box>
            )}
            {props.traceAsString && (
                <>
                    <Divider sx={{mt: 2}} />
                    <Accordion disableGutters elevation={0} square sx={{background: 'transparent'}}>
                        <AccordionSummary expandIcon={<ExpandMoreIcon />} sx={{px: 0, minHeight: 40}}>
                            <Typography variant="body2" sx={{fontWeight: 600, color: 'text.secondary'}}>
                                Stack trace
                            </Typography>
                        </AccordionSummary>
                        <AccordionDetails sx={{px: 0, pt: 0}}>
                            <StackTrace trace={props.traceAsString} fontSize={10} />
                        </AccordionDetails>
                    </Accordion>
                </>
            )}
        </Box>
    );
};
