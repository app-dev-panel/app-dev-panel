import {useDebugEntry} from '@app-dev-panel/sdk/API/Debug/Context';
import {useLazyGetObjectQuery} from '@app-dev-panel/sdk/API/Debug/Debug';
import {JsonRendererProps, JsonRenderer as OriginalJsonRenderer} from '@app-dev-panel/sdk/Component/JsonRenderer';
import {parseObjectId, toObjectReference} from '@app-dev-panel/sdk/Helper/objectString';
import {OpenInNew, Sync} from '@mui/icons-material';
import {Box, IconButton, Tooltip, Typography} from '@mui/material';
import {DataType} from '@textea/json-viewer';
import {deepUpdate} from 'immupdate';
import * as React from 'react';
import {useState} from 'react';

export const JsonRenderer = React.memo((props: JsonRendererProps) => {
    const [objectQuery] = useLazyGetObjectQuery();
    const debugEntry = useDebugEntry();
    const [data, setData] = useState(props.value);

    const objectLoader = async (objectString: string, pathes: (string | number)[]) => {
        const response = await objectQuery({debugEntryId: debugEntry!.id, objectId: parseObjectId(objectString)});
        let pointer = deepUpdate(data);

        for (const path of pathes) {
            pointer = pointer.at(path);
        }
        const newData = pointer.set(response.data.value);
        setData(newData);
    };
    const valueTypes: DataType<string>[] = [
        {
            is: (value: any) => typeof value === 'string' && !!value.match(/object@[\w\\]+#\d/),
            Component: (props) => {
                return (
                    <Box
                        component="span"
                        sx={(theme) => ({
                            display: 'inline-flex',
                            alignItems: 'center',
                            gap: 0.25,
                            backgroundColor: theme.palette.action.selected,
                            borderRadius: 1,
                            px: 0.75,
                            py: 0.25,
                            border: `1px solid ${theme.palette.divider}`,
                        })}
                    >
                        <Typography
                            component="span"
                            variant="body2"
                            sx={{fontFamily: (theme) => theme.typography.caption.fontFamily, wordBreak: 'break-word'}}
                        >
                            {toObjectReference(props.value)}
                        </Typography>
                        <Tooltip title="Load object state">
                            <IconButton
                                size="small"
                                key={props.path.join(',')}
                                onClick={() => objectLoader(props.value, props.path)}
                                sx={{p: 0.25}}
                            >
                                <Sync sx={{fontSize: 16}} />
                            </IconButton>
                        </Tooltip>
                        <Tooltip title="Examine object">
                            <IconButton
                                size="small"
                                href={`/debug/object?debugEntry=${debugEntry!.id}&id=${parseObjectId(props.value)}`}
                                sx={{p: 0.25}}
                            >
                                <OpenInNew sx={{fontSize: 16, color: 'primary.main'}} />
                            </IconButton>
                        </Tooltip>
                    </Box>
                );
            },
        },
    ];
    return <OriginalJsonRenderer value={data} valueTypes={valueTypes} />;
});
