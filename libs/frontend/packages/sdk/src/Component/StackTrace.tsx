import {parseFilePath, parseFilename} from '@app-dev-panel/sdk/Helper/filePathParser';
import {useEditorUrl} from '@app-dev-panel/sdk/Helper/useEditorUrl';
import {Code} from '@mui/icons-material';
import {IconButton, Tooltip} from '@mui/material';
import {styled} from '@mui/material/styles';
import React, {type ReactNode} from 'react';

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

const classMethodRegex = /^((?:[A-Z][a-zA-Z0-9_]*\\)+[A-Z][a-zA-Z0-9_]*)(->|::)([a-zA-Z_][a-zA-Z0-9_]*)\((.*)\)$/s;
const objectArgRegex = /Object\(((?:[A-Z][a-zA-Z0-9_]*\\)+[A-Z][a-zA-Z0-9_]*)\)/g;

function buildClassHref(className: string, methodName?: string): string {
    const params = new URLSearchParams({class: className});
    if (methodName) params.set('method', methodName);
    return `/inspector/files?${params.toString()}`;
}

function renderCallText(call: string): ReactNode {
    const match = call.match(classMethodRegex);
    if (!match) return call;

    const [, className, , methodName, argsStr] = match;

    return (
        <>
            <CallLink href={buildClassHref(className)}>
                {className}
            </CallLink>
            {'->'}
            <CallLink href={buildClassHref(className, methodName)}>
                {methodName}
            </CallLink>
            ({renderArgs(argsStr)})
        </>
    );
}

function renderArgs(argsStr: string): ReactNode {
    if (!argsStr) return null;

    const parts: ReactNode[] = [];
    let lastIndex = 0;

    for (const match of argsStr.matchAll(objectArgRegex)) {
        const before = argsStr.slice(lastIndex, match.index);
        if (before) parts.push(before);

        const objClassName = match[1];
        parts.push(
            <span key={match.index}>
                Object(<CallLink href={buildClassHref(objClassName)}>{objClassName}</CallLink>)
            </span>,
        );
        lastIndex = match.index + match[0].length;
    }

    const rest = argsStr.slice(lastIndex);
    if (rest) parts.push(rest);

    return parts.length === 1 && typeof parts[0] === 'string' ? parts[0] : <>{parts}</>;
}

const isVendorFrame = (file: string): boolean => file.includes('/vendor/') || file.includes('/node_modules/');

const TraceContainer = styled('div')(({theme}) => ({
    fontFamily: "'JetBrains Mono', monospace",
    backgroundColor: theme.palette.mode === 'dark' ? theme.palette.background.default : theme.palette.grey[50],
    borderRadius: theme.shape.borderRadius,
    overflow: 'auto',
}));

const FrameRow = styled('div', {shouldForwardProp: (p) => p !== 'dimmed'})<{dimmed?: boolean}>(({theme, dimmed}) => ({
    display: 'flex',
    alignItems: 'flex-start',
    gap: theme.spacing(1),
    padding: theme.spacing(0.75, 1.5),
    borderBottom: `1px solid ${theme.palette.divider}`,
    opacity: dimmed ? 0.5 : 1,
    transition: 'opacity 0.15s, background-color 0.15s',
    '&:hover': {opacity: 1, backgroundColor: theme.palette.action.hover},
    '&:last-child': {borderBottom: 'none'},
}));

const FrameIndex = styled('span')(({theme}) => ({
    color: theme.palette.text.disabled,
    minWidth: '2.5em',
    flexShrink: 0,
    paddingTop: 1,
}));

const FrameBody = styled('div')({flex: 1, minWidth: 0});

const FileRow = styled('div')({display: 'flex', alignItems: 'center', gap: 4});

const FrameFileLink = styled('a')(({theme}) => ({
    color: theme.palette.primary.main,
    textDecoration: 'none',
    fontWeight: 600,
    '&:hover': {textDecoration: 'underline'},
}));

const CallText = styled('div')(({theme}) => ({
    color: theme.palette.text.secondary,
    marginTop: 1,
    wordBreak: 'break-all',
}));

const CallLink = styled('a')(({theme}) => ({
    color: theme.palette.primary.main,
    textDecoration: 'none',
    '&:hover': {textDecoration: 'underline'},
}));

const PlainLine = styled('div')(({theme}) => ({
    padding: theme.spacing(0.75, 1.5),
    color: theme.palette.text.disabled,
    borderBottom: `1px solid ${theme.palette.divider}`,
    '&:last-child': {borderBottom: 'none'},
}));

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
                const shortFile = parseFilename(frame.file);
                const vendor = isVendorFrame(frame.file);

                return (
                    <FrameRow key={index} dimmed={vendor}>
                        <FrameIndex>#{index}</FrameIndex>
                        <FrameBody>
                            <FileRow>
                                <Tooltip title={frame.file} placement="top-start">
                                    <FrameFileLink href={explorerHref}>
                                        {shortFile}:{frame.line}
                                    </FrameFileLink>
                                </Tooltip>
                                {editorUrl && (
                                    <Tooltip title="Open in Editor">
                                        <IconButton
                                            size="small"
                                            component="a"
                                            href={editorUrl}
                                            aria-label="Open in Editor"
                                            onClick={(e: React.MouseEvent) => e.stopPropagation()}
                                            sx={{p: 0.25}}
                                        >
                                            <Code sx={{fontSize: 12}} />
                                        </IconButton>
                                    </Tooltip>
                                )}
                            </FileRow>
                            {frame.call && <CallText>{renderCallText(frame.call)}</CallText>}
                        </FrameBody>
                    </FrameRow>
                );
            })}
        </TraceContainer>
    );
});
