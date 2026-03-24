import {CodeHighlight} from '@app-dev-panel/sdk/Component/CodeHighlight';
import {FileLink} from '@app-dev-panel/sdk/Component/FileLink';
import {isClassString} from '@app-dev-panel/sdk/Helper/classMatcher';
import {useMediaQuery} from '@mui/material';
import Typography from '@mui/material/Typography';
import {DataType, JsonViewer, JsonViewerOnChange, JsonViewerTheme} from '@textea/json-viewer';
import * as React from 'react';

const REGEXP_PHP_FUNCTION = /(static )?(function |fn )\(.*\).*((\{.*})|(=>.*))/s;

export type JsonRendererProps = {
    value: any;
    depth?: number;
    editable?: boolean;
    onChange?: JsonViewerOnChange;
    valueTypes?: DataType<any>[];
};
export const JsonRenderer = React.memo(
    ({value, depth = 5, editable = false, onChange = undefined, valueTypes = []}: JsonRendererProps) => {
        const prefersDarkMode = useMediaQuery('(prefers-color-scheme: dark)');
        const mode: JsonViewerTheme = prefersDarkMode ? 'dark' : 'light';

        if (typeof value == 'string' && value.match(REGEXP_PHP_FUNCTION)?.length) {
            return <CodeHighlight language={'php'} code={value} showLineNumbers={false} fontSize={10} />;
        }

        return (
            <JsonViewer
                rootName={false}
                value={value}
                editable={editable}
                onChange={onChange}
                displayDataTypes={false}
                quotesOnKeys={false}
                enableClipboard={true}
                defaultInspectDepth={depth}
                groupArraysAfterLength={50}
                theme={mode}
                style={{
                    // height: '100%',
                    width: '100%',
                }}
                collapseStringsAfterLength={50}
                valueTypes={[
                    {
                        is: (value: any) => typeof value === 'string' && value.startsWith('@'),
                        Component: (props) => {
                            return <>alias: {props.value}</>;
                        },
                    },
                    {
                        is: (value: any) => Array.isArray(value) && value.length === 0,
                        Component: () => {
                            return <>[]</>;
                        },
                    },
                    {
                        is: (value: any) => typeof value === 'string' && isClassString(value),
                        Component: (props) => {
                            return (
                                <FileLink className={props.value}>
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
                                        {props.value}
                                    </Typography>
                                </FileLink>
                            );
                        },
                    },
                    ...valueTypes,
                ]}
            />
        );
    },
);
