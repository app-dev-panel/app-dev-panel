import {DataObject, OpenInNew} from '@mui/icons-material';
import TimelineContent from '@mui/lab/TimelineContent';
import {IconButton, Tooltip} from '@mui/material';
import Box from '@mui/material/Box';
import Typography from '@mui/material/Typography';
import {useDebugEntry} from '@yiisoft/yii-dev-panel-sdk/API/Debug/Context';
import {parseFilePath} from '@yiisoft/yii-dev-panel-sdk/Helper/filePathParser';
import {parseObjectId} from '@yiisoft/yii-dev-panel-sdk/Helper/objectString';
import * as React from 'react';
import {PropsWithChildren} from 'react';

type TimelineContentWrapperProps = {
    name: string;
    file?: string;
    payload: undefined | string;
};

export const TimelineContentWrapper = React.memo((props: PropsWithChildren<TimelineContentWrapperProps>) => {
    const {name, file, payload, children} = props;
    const shortName = name.split('\\').splice(-1).join('');
    const objectId = parseObjectId(payload || '');
    const debugEntry = useDebugEntry();

    return (
        <TimelineContent sx={{py: '12px', px: 2, display: 'flex', flexDirection: 'column'}}>
            <Box sx={{wordBreak: 'break-word'}}>
                <Tooltip title={name}>
                    <Typography component="span">{shortName}</Typography>
                </Tooltip>
                <Tooltip title="Examine an object">
                    <IconButton size="small" href={`/debug/object?debugEntry=${debugEntry!.id}&id=${objectId}`}>
                        <DataObject color="secondary" fontSize="small" />
                    </IconButton>
                </Tooltip>
                {file && (
                    <Tooltip title="Open in File Explorer">
                        <IconButton size="small" href={`/inspector/files?path=${parseFilePath(file)}`}>
                            <OpenInNew fontSize="small" />
                        </IconButton>
                    </Tooltip>
                )}
            </Box>
            <Box>{children}</Box>
        </TimelineContent>
    );
});
