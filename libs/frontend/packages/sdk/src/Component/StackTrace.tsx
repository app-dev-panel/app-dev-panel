import {parseFilePath} from '@app-dev-panel/sdk/Helper/filePathParser';
import {useEditorUrl} from '@app-dev-panel/sdk/Helper/useEditorUrl';
import {Icon, IconButton, Tooltip} from '@mui/material';
import {styled} from '@mui/material/styles';
import React from 'react';

type StackFrame = {file: string; line: number; call: string};

type StackTraceProps = {trace: string; fontSize?: number};

const frameRegex = /^#(\d+)\s+(.+?)\((\d+)\):\s*(.*)$/;
const internalFrameRegex = /^#(\d+)\s+\{main\}$/;

function parseTraceString(trace: string): Array<StackFrame | string> {
    return trace
        .split('\n')
        .filter((line) => line.trim().length > 0)
        .map((line) => {
            const match = line.match(frameRegex);
            if (match) {
                return {file: match[2], line: Number(match[3]), call: match[4]};
            }
            if (internalFrameRegex.test(line)) {
                return line;
            }
            return line;
        });
}

const TraceContainer = styled('div')(({theme}) => ({
    fontFamily: "'JetBrains Mono', monospace",
    backgroundColor: theme.palette.mode === 'dark' ? theme.palette.background.default : theme.palette.grey[50],
    borderRadius: theme.shape.borderRadius,
    padding: theme.spacing(1.5),
    overflow: 'auto',
}));

const FrameLine = styled('div')(({theme}) => ({
    display: 'flex',
    alignItems: 'center',
    gap: theme.spacing(0.5),
    padding: '2px 0',
    '&:hover': {backgroundColor: theme.palette.action.hover, borderRadius: 2},
}));

const FrameFileLink = styled('a')(({theme}) => ({
    color: theme.palette.primary.main,
    textDecoration: 'none',
    '&:hover': {textDecoration: 'underline'},
}));

const PlainLine = styled('div')({padding: '2px 0', color: 'inherit'});

export const StackTrace = React.memo(({trace, fontSize = 10}: StackTraceProps) => {
    const getEditorUrl = useEditorUrl();
    const frames = parseTraceString(trace);

    return (
        <TraceContainer style={{fontSize: `${fontSize}pt`}}>
            {frames.map((frame, index) => {
                if (typeof frame === 'string') {
                    return <PlainLine key={index}>{frame}</PlainLine>;
                }

                const explorerHref = `/inspector/files?path=${parseFilePath(frame.file)}#L${frame.line}`;
                const editorUrl = getEditorUrl(frame.file, frame.line);

                return (
                    <FrameLine key={index}>
                        <span style={{color: 'gray', minWidth: '2.5em'}}>#{index}</span>
                        <FrameFileLink href={explorerHref}>
                            {frame.file}({frame.line})
                        </FrameFileLink>
                        {editorUrl && (
                            <Tooltip title="Open in Editor">
                                <IconButton
                                    size="small"
                                    component="a"
                                    href={editorUrl}
                                    onClick={(e: React.MouseEvent) => e.stopPropagation()}
                                    sx={{p: 0.25}}
                                >
                                    <Icon sx={{fontSize: 12}}>edit</Icon>
                                </IconButton>
                            </Tooltip>
                        )}
                        <span style={{opacity: 0.7}}>{frame.call}</span>
                    </FrameLine>
                );
            })}
        </TraceContainer>
    );
});
