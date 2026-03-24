import {Box, useTheme} from '@mui/material';
import React, {useCallback} from 'react';
import {Prism} from 'react-syntax-highlighter';
import {darcula} from 'react-syntax-highlighter/dist/esm/styles/prism';

type CodeHighlightProps = {
    language: string;
    code: string;
    showLineNumbers?: boolean;
    fontSize?: number;
    highlightLines?: [number, number] | [number];
    highlightColor?: string;
    wrappedLines?: [number, number];
    /** File path (kept for backward compatibility) */
    filePath?: string;
    /** Called when a line number is clicked. Receives line number and whether shift was held. */
    onLineClick?: (lineNumber: number, shiftKey: boolean) => void;
};
const isNumberInRange = (lineNumber: number, range: [number, number] | [number]) => {
    if (range.length === 1) {
        return range[0] === lineNumber;
    }
    return range[0] <= lineNumber && lineNumber <= range[1];
};
export const CodeHighlight = React.memo((props: CodeHighlightProps) => {
    const {
        language,
        code,
        highlightLines,
        fontSize = 12,
        showLineNumbers = true,
        highlightColor = 'rgba(0,0,0, .1)',
        wrappedLines = [1, 0],
        onLineClick,
    } = props;

    const theme = useTheme();

    const handleClick = useCallback(
        (e: React.MouseEvent) => {
            if (!onLineClick) return;
            const target = e.target as HTMLElement;
            if (!target.classList.contains('linenumber')) return;
            const lineNumber = parseInt(target.textContent?.trim() ?? '', 10);
            if (!isNaN(lineNumber)) {
                onLineClick(lineNumber, e.shiftKey);
            }
        },
        [onLineClick],
    );

    const startLine = Math.max(wrappedLines[0], 1);
    const endLine = Math.max(wrappedLines[1], 0);
    let wrappedCode = code;
    if (startLine !== 0 || endLine !== 0) {
        wrappedCode = code
            .split('\n')
            .slice(startLine - 1, endLine === 0 ? undefined : endLine)
            .join('\n');
    }

    const prism = (
        <Prism
            style={theme.palette.mode === 'dark' ? darcula : undefined}
            startingLineNumber={startLine}
            showLineNumbers={showLineNumbers}
            wrapLines
            customStyle={{fontSize: `${fontSize}pt`}}
            useInlineStyles
            lineProps={(lineNumber) => ({
                id: `L${lineNumber}`,
                style: {
                    ...(highlightLines && isNumberInRange(lineNumber, highlightLines)
                        ? {backgroundColor: highlightColor, display: 'block'}
                        : {}),
                },
            })}
            language={language}
        >
            {wrappedCode}
        </Prism>
    );

    if (!onLineClick) return prism;

    return (
        <Box onClick={handleClick} sx={{'& .linenumber': {cursor: 'pointer', '&:hover': {opacity: 0.7}}}}>
            {prism}
        </Box>
    );
});
