import {CodeHighlight} from '@app-dev-panel/sdk/Component/CodeHighlight';
import {StackTrace} from '@app-dev-panel/sdk/Component/StackTrace';
import type {EditorPreset} from '@app-dev-panel/sdk/Helper/editorUrl';
import {parseFilePath, parseFilename} from '@app-dev-panel/sdk/Helper/filePathParser';
import {panelPagePath} from '@app-dev-panel/sdk/Helper/panelMountPath';
import {formatFqcn} from '@app-dev-panel/sdk/Helper/phpClassName';
import {useEditorUrl} from '@app-dev-panel/sdk/Helper/useEditorUrl';
import {usePathMapper} from '@app-dev-panel/sdk/Helper/usePathMapper';
import BugReportIcon from '@mui/icons-material/BugReport';
import FmdGoodIcon from '@mui/icons-material/FmdGood';
import {Box, Button, Divider, Stack, Tooltip, Typography} from '@mui/material';
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
    /**
     * Fallback editor preset used when `application.editorConfig.editor`
     * is still `'none'`. Typically set by the toolbar so "Source" opens
     * an IDE out of the box.
     */
    defaultEditorPreset?: EditorPreset;
};

export const ExceptionPreview = (props: ExceptionPreviewProps) => {
    const [file, setFile] = useState<ExceptionFileContent | null>(null);
    const {fetchFileContent} = props;
    const getEditorUrl = useEditorUrl(props.defaultEditorPreset);
    const pathMapper = usePathMapper();
    const cleanFile = parseFilePath(props.file);
    const localFile = pathMapper.toLocal(cleanFile);
    const lineNumber = +props.line;
    const cleanClass = parseFilePath(props.class);
    const fullClass = formatFqcn(props.class);
    const sourceEditorUrl = getEditorUrl(localFile, lineNumber);
    const sourceFallbackUrl = panelPagePath(`/inspector/files?path=${encodeURIComponent(cleanFile)}#L${props.line}`);
    const classExplorerUrl = panelPagePath(`/inspector/files?class=${encodeURIComponent(cleanClass)}`);
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
                    {fullClass}
                </Typography>
                {hasCode && (
                    <Typography variant="caption" sx={{color: 'text.disabled'}}>
                        code {props.code}
                    </Typography>
                )}
                <Box sx={{flexGrow: 1}} />
                <Tooltip title={`${localFile}:${props.line}`} arrow placement="top">
                    <Typography sx={{fontFamily: "'JetBrains Mono', monospace", fontSize: 12, color: 'text.secondary'}}>
                        {parseFilename(localFile)}:{props.line}
                    </Typography>
                </Tooltip>
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
                <Box sx={{mt: 2}}>
                    <Divider sx={{mb: 1}} />
                    <Typography variant="body2" sx={{fontWeight: 600, color: 'text.secondary', mb: 1}}>
                        Stack trace
                    </Typography>
                    <Box sx={{maxHeight: 320, overflow: 'auto', border: 1, borderColor: 'divider', borderRadius: 1}}>
                        <StackTrace trace={props.traceAsString} fontSize={10} />
                    </Box>
                </Box>
            )}
        </Box>
    );
};
