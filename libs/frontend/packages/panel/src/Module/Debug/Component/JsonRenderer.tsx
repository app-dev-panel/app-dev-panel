import {useDebugEntry} from '@app-dev-panel/sdk/API/Debug/Context';
import {useLazyGetObjectQuery} from '@app-dev-panel/sdk/API/Debug/Debug';
import {
    JsonRendererProps,
    JsonRenderer as OriginalJsonRenderer,
    StringRenderContext,
} from '@app-dev-panel/sdk/Component/JsonRenderer';
import {parseObjectId, toObjectReference} from '@app-dev-panel/sdk/Helper/objectString';
import {FileDownload} from '@mui/icons-material';
import {IconButton, Link, Tooltip, Typography} from '@mui/material';
import * as React from 'react';
import {ReactElement, useCallback, useState} from 'react';
import {useNavigate} from 'react-router';

const OBJECT_REFERENCE_PATTERN = /object@[\w\\]+#\d/;

const replaceInTree = (node: unknown, target: string, replacement: unknown): unknown => {
    if (node === target) return replacement;
    if (Array.isArray(node)) {
        let changed = false;
        const result = node.map((item) => {
            const updated = replaceInTree(item, target, replacement);
            if (updated !== item) changed = true;
            return updated;
        });
        return changed ? result : node;
    }
    if (typeof node === 'object' && node !== null) {
        let changed = false;
        const result: Record<string, unknown> = {};
        for (const key of Object.keys(node)) {
            const original = (node as Record<string, unknown>)[key];
            const updated = replaceInTree(original, target, replacement);
            result[key] = updated;
            if (updated !== original) changed = true;
        }
        return changed ? result : node;
    }
    return node;
};

export const JsonRenderer = React.memo((props: JsonRendererProps) => {
    const [objectQuery] = useLazyGetObjectQuery();
    const debugEntry = useDebugEntry();
    const navigate = useNavigate();
    const [data, setData] = useState(props.value);

    if (!debugEntry) {
        return <OriginalJsonRenderer value={data} />;
    }

    const objectLoader = async (objectString: string) => {
        const response = await objectQuery({debugEntryId: debugEntry.id, objectId: parseObjectId(objectString)});
        setData((prev: unknown) => replaceInTree(prev, objectString, response.data.value));
    };

    const renderString = useCallback(
        ({value}: StringRenderContext): ReactElement | undefined => {
            if (!OBJECT_REFERENCE_PATTERN.test(value)) return undefined;

            return (
                <Typography
                    component="span"
                    variant="body2"
                    sx={{display: 'inline-flex', alignItems: 'center', gap: 0.25, wordBreak: 'break-word'}}
                >
                    <Link
                        component="button"
                        onClick={() => navigate(`/debug/object?debugEntry=${debugEntry.id}&id=${parseObjectId(value)}`)}
                        underline="hover"
                        color="primary"
                        sx={{cursor: 'pointer', font: 'inherit', verticalAlign: 'baseline'}}
                    >
                        {toObjectReference(value)}
                    </Link>
                    <Tooltip title="Load object state">
                        <IconButton
                            size="small"
                            onClick={() => objectLoader(value)}
                            sx={{p: 0.25, color: 'text.secondary'}}
                        >
                            <FileDownload sx={{fontSize: 14}} />
                        </IconButton>
                    </Tooltip>
                </Typography>
            );
        },
        [debugEntry.id, navigate],
    );

    return <OriginalJsonRenderer value={data} renderString={renderString} />;
});
