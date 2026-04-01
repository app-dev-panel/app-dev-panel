import {useDebugEntry} from '@app-dev-panel/sdk/API/Debug/Context';
import {useLazyGetObjectQuery} from '@app-dev-panel/sdk/API/Debug/Debug';
import {JsonRendererProps, JsonRenderer as OriginalJsonRenderer} from '@app-dev-panel/sdk/Component/JsonRenderer';
import {parseObjectId, toObjectReference} from '@app-dev-panel/sdk/Helper/objectString';
import {FileDownload} from '@mui/icons-material';
import {IconButton, Link, Tooltip, Typography} from '@mui/material';
import {DataType} from '@textea/json-viewer';
import {deepUpdate} from 'immupdate';
import * as React from 'react';
import {useState} from 'react';
import {useNavigate} from 'react-router';

export const JsonRenderer = React.memo((props: JsonRendererProps) => {
    const [objectQuery] = useLazyGetObjectQuery();
    const debugEntry = useDebugEntry();
    const navigate = useNavigate();
    const [data, setData] = useState(props.value);

    if (!debugEntry) {
        return <OriginalJsonRenderer value={data} />;
    }

    const objectLoader = async (objectString: string, pathes: (string | number)[]) => {
        const response = await objectQuery({debugEntryId: debugEntry.id, objectId: parseObjectId(objectString)});
        let pointer = deepUpdate(data);

        for (const path of pathes) {
            pointer = pointer.at(path);
        }
        const newData = pointer.set(response.data.value);
        setData(newData);
    };
    const valueTypes: DataType<string>[] = [
        {
            is: (value: unknown) => typeof value === 'string' && !!value.match(/object@[\w\\]+#\d/),
            Component: (props) => {
                return (
                    <Typography
                        component="span"
                        variant="body2"
                        sx={{display: 'inline-flex', alignItems: 'center', gap: 0.25, wordBreak: 'break-word'}}
                    >
                        <Link
                            component="button"
                            onClick={() =>
                                navigate(`/debug/object?debugEntry=${debugEntry.id}&id=${parseObjectId(props.value)}`)
                            }
                            underline="hover"
                            color="primary"
                            sx={{cursor: 'pointer', font: 'inherit', verticalAlign: 'baseline'}}
                        >
                            {toObjectReference(props.value)}
                        </Link>
                        <Tooltip title="Load object state">
                            <IconButton
                                size="small"
                                key={props.path.join(',')}
                                onClick={() => objectLoader(props.value, props.path)}
                                sx={{p: 0.25, color: 'text.secondary'}}
                            >
                                <FileDownload sx={{fontSize: 14}} />
                            </IconButton>
                        </Tooltip>
                    </Typography>
                );
            },
        },
    ];
    return <OriginalJsonRenderer value={data} valueTypes={valueTypes} />;
});
