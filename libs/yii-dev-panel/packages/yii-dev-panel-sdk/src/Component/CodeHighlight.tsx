import {useTheme} from '@mui/material';
import React from 'react';
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
    } = props;

    const theme = useTheme();

    const startLine = Math.max(wrappedLines[0], 1);
    const endLine = Math.max(wrappedLines[1], 0);
    let wrappedCode = code;
    if (startLine !== 0 || endLine !== 0) {
        wrappedCode = code
            .split('\n')
            .slice(startLine - 1, endLine === 0 ? undefined : endLine)
            .join('\n');
    }

    return (
        <Prism
            style={theme.palette.mode === 'dark' ? darcula : undefined}
            startingLineNumber={startLine}
            showLineNumbers={showLineNumbers}
            wrapLines
            customStyle={{fontSize: `${fontSize}pt`}}
            useInlineStyles
            lineProps={(lineNumber) => ({
                id: `L${lineNumber}`,
                ...(highlightLines &&
                    isNumberInRange(lineNumber, highlightLines) && {
                        style: {
                            backgroundColor: highlightColor,
                            display: 'block',
                        },
                    }),
            })}
            language={language}
        >
            {wrappedCode}
        </Prism>
    );
});
