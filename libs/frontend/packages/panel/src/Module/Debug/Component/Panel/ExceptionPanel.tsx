import {InspectorFileContent, useLazyGetFilesQuery} from '@app-dev-panel/panel/Module/Inspector/API/Inspector';
import {CodeHighlight} from '@app-dev-panel/sdk/Component/CodeHighlight';
import {EmptyState} from '@app-dev-panel/sdk/Component/EmptyState';
import {SectionTitle} from '@app-dev-panel/sdk/Component/SectionTitle';
import {primitives} from '@app-dev-panel/sdk/Component/Theme/tokens';
import {parseFilename, parseFilePath} from '@app-dev-panel/sdk/Helper/filePathParser';
import {Box, Chip, Collapse, Icon, Typography} from '@mui/material';
import {alpha, styled, useTheme} from '@mui/material/styles';
import {useEffect, useState} from 'react';

type ExceptionData = {
    class: string;
    message: string;
    line: string;
    file: string;
    code: string;
    trace: any[];
    traceAsString: string;
};
type ExceptionPanelProps = {exceptions: ExceptionData[]};

const ExceptionRow = styled(Box, {shouldForwardProp: (p) => p !== 'expanded'})<{expanded?: boolean}>(
    ({theme, expanded}) => ({
        display: 'flex',
        alignItems: 'flex-start',
        gap: theme.spacing(1.5),
        padding: theme.spacing(1.5, 2),
        borderBottom: `1px solid ${theme.palette.divider}`,
        cursor: 'pointer',
        transition: 'background-color 0.1s ease',
        backgroundColor: expanded ? theme.palette.action.hover : 'transparent',
        '&:hover': {backgroundColor: theme.palette.action.hover},
    }),
);

const IndexBadge = styled(Box)(({theme}) => ({
    width: 24,
    height: 24,
    borderRadius: '50%',
    backgroundColor: theme.palette.error.main,
    color: theme.palette.common.white,
    display: 'flex',
    alignItems: 'center',
    justifyContent: 'center',
    fontSize: '11px',
    fontWeight: 700,
    flexShrink: 0,
    marginTop: 2,
}));

const ClassName = styled(Typography)(({theme}) => ({
    fontFamily: primitives.fontFamilyMono,
    fontSize: '13px',
    fontWeight: 600,
    color: theme.palette.error.main,
    wordBreak: 'break-word',
}));

const Message = styled(Typography)(({theme}) => ({
    fontSize: '13px',
    flex: 1,
    wordBreak: 'break-word',
    color: theme.palette.text.secondary,
}));

const FileLink = styled(Typography)({
    fontFamily: primitives.fontFamilyMono,
    fontSize: '11px',
    flexShrink: 0,
    whiteSpace: 'nowrap',
}) as typeof Typography;

const DetailBox = styled(Box)(({theme}) => ({
    padding: theme.spacing(2),
    backgroundColor: theme.palette.action.hover,
    borderBottom: `1px solid ${theme.palette.divider}`,
}));

const ExceptionDetail = ({exception}: {exception: ExceptionData}) => {
    const theme = useTheme();
    const [lazyGetFilesQuery] = useLazyGetFilesQuery();
    const [file, setFile] = useState<InspectorFileContent | null>(null);

    useEffect(() => {
        (async () => {
            const response = await lazyGetFilesQuery(parseFilePath(exception.file));
            setFile(response.data as any);
        })();
    }, [exception.file, lazyGetFilesQuery]);

    const lineNumber = +exception.line;

    return (
        <DetailBox>
            <Box sx={{display: 'flex', gap: 1, mb: 1.5, flexWrap: 'wrap', alignItems: 'center'}}>
                <Chip
                    label={exception.class}
                    size="small"
                    sx={{
                        fontFamily: primitives.fontFamilyMono,
                        fontSize: '11px',
                        fontWeight: 600,
                        backgroundColor: 'error.light',
                        color: 'error.main',
                        borderRadius: 1,
                        height: 22,
                    }}
                />
                {exception.code !== '0' && (
                    <Chip
                        label={`Code: ${exception.code}`}
                        size="small"
                        sx={{fontSize: '11px', backgroundColor: 'action.selected', borderRadius: 1, height: 22}}
                    />
                )}
            </Box>

            <Typography sx={{fontSize: '13px', mb: 2, color: 'text.primary'}}>{exception.message}</Typography>

            <Box sx={{display: 'flex', gap: 1, mb: 2}}>
                <Chip
                    component="a"
                    clickable
                    href={`/inspector/files?class=${parseFilePath(exception.class)}`}
                    label="Open Exception Class"
                    size="small"
                    icon={<Icon sx={{fontSize: '14px !important'}}>open_in_new</Icon>}
                    sx={{fontSize: '11px', height: 24}}
                    variant="outlined"
                />
                <Chip
                    component="a"
                    clickable
                    href={`/inspector/files?path=${parseFilePath(exception.file)}#L${exception.line}`}
                    label="Open Source Location"
                    size="small"
                    icon={<Icon sx={{fontSize: '14px !important'}}>open_in_new</Icon>}
                    sx={{fontSize: '11px', height: 24}}
                    variant="outlined"
                />
            </Box>

            {file && (
                <Box sx={{mb: 2, borderRadius: 1, overflow: 'hidden', border: `1px solid`, borderColor: 'divider'}}>
                    <CodeHighlight
                        language={file.extension}
                        code={file.content}
                        highlightLines={[lineNumber]}
                        highlightColor={alpha(theme.palette.error.main, 0.15)}
                        wrappedLines={[lineNumber - 5, lineNumber + 5]}
                    />
                </Box>
            )}

            {exception.traceAsString && <TraceBlock trace={exception.traceAsString} />}
        </DetailBox>
    );
};

const TraceBlock = ({trace}: {trace: string}) => {
    const [open, setOpen] = useState(true);

    return (
        <Box>
            <Box
                onClick={() => setOpen(!open)}
                sx={{
                    display: 'flex',
                    alignItems: 'center',
                    gap: 0.5,
                    cursor: 'pointer',
                    color: 'text.disabled',
                    fontSize: '12px',
                    fontWeight: 600,
                    '&:hover': {color: 'text.secondary'},
                }}
            >
                <Icon sx={{fontSize: 16}}>{open ? 'expand_less' : 'expand_more'}</Icon>
                Stack Trace
            </Box>
            <Collapse in={open}>
                <Box sx={{mt: 1, borderRadius: 1, overflow: 'hidden', border: `1px solid`, borderColor: 'divider'}}>
                    <CodeHighlight fontSize={10} language={'text/plain'} code={trace} />
                </Box>
            </Collapse>
        </Box>
    );
};

export const ExceptionPanel = ({exceptions}: ExceptionPanelProps) => {
    const [expandedIndex, setExpandedIndex] = useState<number | null>(0);
    const items = exceptions ?? [];

    if (items.length === 0) {
        return <EmptyState icon="bug_report" title="No exceptions found" />;
    }

    return (
        <Box>
            <Box sx={{display: 'flex', alignItems: 'center', gap: 2, mb: 2}}>
                <SectionTitle>{`${items.length} exception${items.length !== 1 ? 's' : ''}`}</SectionTitle>
            </Box>

            {items.map((exception, index) => {
                const expanded = expandedIndex === index;
                return (
                    <Box key={index}>
                        <ExceptionRow expanded={expanded} onClick={() => setExpandedIndex(expanded ? null : index)}>
                            <IndexBadge>{index + 1}</IndexBadge>
                            <Box sx={{flex: 1, minWidth: 0}}>
                                <ClassName>{exception.class}</ClassName>
                                <Message>{exception.message}</Message>
                            </Box>
                            <FileLink component="span" sx={{color: 'text.disabled'}}>
                                {parseFilename(exception.file)}:{exception.line}
                            </FileLink>
                        </ExceptionRow>
                        <Collapse in={expanded}>
                            <ExceptionDetail exception={exception} />
                        </Collapse>
                    </Box>
                );
            })}
        </Box>
    );
};
