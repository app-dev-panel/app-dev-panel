import {CodeHighlight} from '@app-dev-panel/sdk/Component/CodeHighlight';
import {FileLink} from '@app-dev-panel/sdk/Component/FileLink';
import {isClassString} from '@app-dev-panel/sdk/Helper/classMatcher';
import {useMediaQuery} from '@mui/material';
import Typography from '@mui/material/Typography';
import JsonView from '@uiw/react-json-view';
import * as React from 'react';
import {ReactElement} from 'react';
import {jsonViewDarkTheme, jsonViewLightTheme} from './Theme/jsonViewTheme';

const REGEXP_PHP_FUNCTION = /(static )?(function |fn )\(.*\).*((\{.*})|(=>.*))/s;

type PrimitiveValueProps = {
    value: any;
    renderString?: JsonRendererProps['renderString'];
};

const PrimitiveValue = ({value, renderString}: PrimitiveValueProps) => {
    if (typeof value === 'string') {
        if (renderString) {
            const result = renderString({}, {type: 'value', value, keyName: ''});
            if (result) return result;
        }
        if (value.startsWith('@')) {
            return <Typography sx={{fontFamily: 'monospace', fontSize: '13px'}}>alias: {value}</Typography>;
        }
        if (isClassString(value)) {
            return (
                <FileLink className={value}>
                    <Typography
                        component="span"
                        sx={{
                            display: 'inline',
                            wordBreak: 'break-word',
                            color: 'primary.main',
                            cursor: 'pointer',
                            fontFamily: 'monospace',
                            fontSize: '13px',
                            '&:hover': {textDecoration: 'underline'},
                        }}
                    >
                        {value}
                    </Typography>
                </FileLink>
            );
        }
        return <Typography sx={{fontFamily: 'monospace', fontSize: '13px'}}>{value}</Typography>;
    }
    return (
        <Typography sx={{fontFamily: 'monospace', fontSize: '13px', color: 'text.secondary'}}>
            {String(value)}
        </Typography>
    );
};

export type StringRenderContext = {type: 'type' | 'value'; value: string; keyName: string};

export type JsonRendererProps = {
    value: any;
    depth?: number;
    editable?: boolean;
    onChange?: (path: (string | number)[], oldValue: any, newValue: any) => void;
    renderString?: (props: Record<string, any>, context: StringRenderContext) => ReactElement | undefined;
};
export const JsonRenderer = React.memo(({value, depth = 5, renderString}: JsonRendererProps) => {
    const prefersDarkMode = useMediaQuery('(prefers-color-scheme: dark)');
    const theme = prefersDarkMode ? jsonViewDarkTheme : jsonViewLightTheme;

    if (typeof value == 'string' && value.match(REGEXP_PHP_FUNCTION)?.length) {
        return <CodeHighlight language={'php'} code={value} showLineNumbers={false} fontSize={10} />;
    }

    // @uiw/react-json-view only accepts objects/arrays — render primitives inline
    if (typeof value !== 'object' || value === null) {
        return <PrimitiveValue value={value} renderString={renderString} />;
    }

    return (
        <JsonView
            value={value}
            collapsed={depth}
            displayDataTypes={false}
            displayObjectSize={true}
            enableClipboard={true}
            shortenTextAfterLength={50}
            style={{...theme, width: '100%'}}
        >
            <JsonView.Quote render={() => <span />} />
            <JsonView.String
                render={(props, {type, value: strValue, keyName}) => {
                    if (type === 'type') return undefined;

                    const strVal = String(strValue);

                    // Custom external render (from Panel's JsonRenderer)
                    if (renderString) {
                        const result = renderString(props, {type, value: strVal, keyName: String(keyName)});
                        if (result) return result;
                    }

                    // Alias values (starting with '@')
                    if (strVal.startsWith('@')) {
                        return <span {...props}>alias: {strVal}</span>;
                    }

                    // Class strings as clickable file links
                    if (isClassString(strVal)) {
                        return (
                            <FileLink className={strVal}>
                                <Typography
                                    component="span"
                                    sx={{
                                        display: 'inline',
                                        wordBreak: 'break-word',
                                        color: 'primary.main',
                                        cursor: 'pointer',
                                        '&:hover': {textDecoration: 'underline'},
                                    }}
                                >
                                    {strVal}
                                </Typography>
                            </FileLink>
                        );
                    }

                    return undefined;
                }}
            />
        </JsonView>
    );
});
