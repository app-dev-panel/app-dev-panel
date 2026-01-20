import {OpenInNew} from '@mui/icons-material';
import {IconButton, Tooltip, useMediaQuery} from '@mui/material';
import Typography from '@mui/material/Typography';
import {DataType, JsonViewer, JsonViewerOnChange, JsonViewerTheme} from '@textea/json-viewer';
import {CodeHighlight} from '@yiisoft/yii-dev-panel-sdk/Component/CodeHighlight';
import {isClassString} from '@yiisoft/yii-dev-panel-sdk/Helper/classMatcher';
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
            const html = value.replaceAll('\n', '<br/>').replaceAll(' ', '&nbsp');
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
                        Component: (props) => {
                            return <>[]</>;
                        },
                    },
                    {
                        is: (value: any) => typeof value === 'string' && isClassString(value),
                        Component: (props) => {
                            return (
                                <Typography sx={{display: 'inline', wordBreak: 'break-word'}}>
                                    {props.value}
                                    <Tooltip title="Examine as a container entry">
                                        <IconButton
                                            size="small"
                                            href={'/inspector/container/view?class=' + props.value}
                                        >
                                            <OpenInNew fontSize="small" />
                                        </IconButton>
                                    </Tooltip>
                                </Typography>
                            );
                        },
                    },
                    ...valueTypes,
                ]}
            />
        );
    },
);
