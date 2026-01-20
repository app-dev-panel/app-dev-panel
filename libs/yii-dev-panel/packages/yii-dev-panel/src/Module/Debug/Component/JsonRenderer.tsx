import {DataObject} from '@mui/icons-material';
import {IconButton, Tooltip, Typography} from '@mui/material';
import {DataType} from '@textea/json-viewer';
import {useDebugEntry} from '@yiisoft/yii-dev-panel-sdk/API/Debug/Context';
import {useLazyGetObjectQuery} from '@yiisoft/yii-dev-panel-sdk/API/Debug/Debug';
import {
    JsonRendererProps,
    JsonRenderer as OriginalJsonRenderer,
} from '@yiisoft/yii-dev-panel-sdk/Component/JsonRenderer';
import {parseObjectId, toObjectReference} from '@yiisoft/yii-dev-panel-sdk/Helper/objectString';
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
                    <Typography sx={{display: 'inline', wordBreak: 'break-word'}}>
                        {toObjectReference(props.value)}
                        <Tooltip title="Load object state">
                            <IconButton
                                key={props.path.join(',')}
                                onClick={(e) => objectLoader(props.value, props.path)}
                            >
                                <DataObject />
                            </IconButton>
                        </Tooltip>
                        <Tooltip title="Examine an object">
                            <IconButton
                                size="small"
                                href={`/debug/object?debugEntry=${debugEntry!.id}&id=${parseObjectId(props.value)}`}
                            >
                                <DataObject color="secondary" fontSize="small" />
                            </IconButton>
                        </Tooltip>
                    </Typography>
                );
            },
        },
    ];
    return <OriginalJsonRenderer value={data} valueTypes={valueTypes} />;
});
