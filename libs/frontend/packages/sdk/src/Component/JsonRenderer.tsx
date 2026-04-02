import {CodeHighlight} from '@app-dev-panel/sdk/Component/CodeHighlight';
import {FileLink} from '@app-dev-panel/sdk/Component/FileLink';
import {isClassString} from '@app-dev-panel/sdk/Helper/classMatcher';
import {useMediaQuery} from '@mui/material';
import Typography from '@mui/material/Typography';
import JsonView from '@uiw/react-json-view';
import * as React from 'react';
import {ReactElement, useMemo} from 'react';
import {monoFontFamily} from './Theme/DefaultTheme';
import {jsonViewDarkTheme, jsonViewLightTheme} from './Theme/jsonViewTheme';

const REGEXP_PHP_FUNCTION = /(static )?(function |fn )\(.*\).*((\{.*})|(=>.*))/s;

const monoSx = {fontFamily: monoFontFamily, fontSize: '13px'} as const;

const renderClassLink = (value: string) => (
    <FileLink className={value}>
        <Typography
            component="span"
            sx={{
                display: 'inline',
                wordBreak: 'break-word',
                color: 'primary.main',
                cursor: 'pointer',
                ...monoSx,
                '&:hover': {textDecoration: 'underline'},
            }}
        >
            {value}
        </Typography>
    </FileLink>
);

const renderCustomString = (
    value: string,
    keyName: string,
    renderString?: JsonRendererProps['renderString'],
): ReactElement | undefined => {
    if (renderString) {
        const result = renderString({value, keyName});
        if (result) return result;
    }
    if (value.startsWith('@')) {
        return <Typography sx={monoSx}>alias: {value}</Typography>;
    }
    if (isClassString(value)) {
        return renderClassLink(value);
    }
    return undefined;
};

type PrimitiveValueProps = {value: any; renderString?: JsonRendererProps['renderString']};

const PrimitiveValue = ({value, renderString}: PrimitiveValueProps) => {
    if (typeof value === 'string') {
        const custom = renderCustomString(value, '', renderString);
        if (custom) return custom;
        return <Typography sx={monoSx}>{value}</Typography>;
    }
    return <Typography sx={{...monoSx, color: 'text.secondary'}}>{String(value)}</Typography>;
};

export type StringRenderContext = {value: string; keyName: string};

export type JsonRendererProps = {
    value: any;
    depth?: number;
    editable?: boolean;
    onChange?: (path: (string | number)[], oldValue: any, newValue: any) => void;
    renderString?: (context: StringRenderContext) => ReactElement | undefined;
};
export const JsonRenderer = React.memo(({value, depth = 5, renderString}: JsonRendererProps) => {
    const prefersDarkMode = useMediaQuery('(prefers-color-scheme: dark)');
    const style = useMemo(
        () => ({...(prefersDarkMode ? jsonViewDarkTheme : jsonViewLightTheme), width: '100%'}),
        [prefersDarkMode],
    );

    if (typeof value == 'string' && value.match(REGEXP_PHP_FUNCTION)?.length) {
        return <CodeHighlight language={'php'} code={value} showLineNumbers={false} fontSize={10} />;
    }

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
            style={style}
        >
            <JsonView.Quote render={() => <span />} />
            <JsonView.String
                render={(props, {type, value: strValue, keyName}) => {
                    if (type === 'type') return undefined;

                    const strVal = String(strValue);
                    const custom = renderCustomString(strVal, String(keyName), renderString);
                    if (custom) return custom;

                    return undefined;
                }}
            />
        </JsonView>
    );
});
