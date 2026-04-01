import {useDebugEntry} from '@app-dev-panel/sdk/API/Debug/Context';
import {FileLink} from '@app-dev-panel/sdk/Component/FileLink';
import {parseObjectId} from '@app-dev-panel/sdk/Helper/objectString';
import {DataObject} from '@mui/icons-material';
import TimelineContent from '@mui/lab/TimelineContent';
import {IconButton, Tooltip} from '@mui/material';
import Box from '@mui/material/Box';
import Typography from '@mui/material/Typography';
import * as React from 'react';
import {PropsWithChildren} from 'react';
import {useNavigate} from 'react-router';

type TimelineContentWrapperProps = {name: string; file?: string; payload: undefined | string};

export const TimelineContentWrapper = React.memo((props: PropsWithChildren<TimelineContentWrapperProps>) => {
    const {name, file, payload, children} = props;
    const shortName = name.split('\\').splice(-1).join('');
    const objectId = parseObjectId(payload || '');
    const debugEntry = useDebugEntry();
    const navigate = useNavigate();

    return (
        <TimelineContent sx={{py: '12px', px: 2, display: 'flex', flexDirection: 'column'}}>
            <Box sx={{wordBreak: 'break-word'}}>
                <Tooltip title={name}>
                    <Typography component="span">{shortName}</Typography>
                </Tooltip>
                {debugEntry && (
                    <Tooltip title="Examine an object">
                        <IconButton
                            size="small"
                            onClick={() => navigate(`/debug/object?debugEntry=${debugEntry.id}&id=${objectId}`)}
                        >
                            <DataObject color="secondary" fontSize="small" />
                        </IconButton>
                    </Tooltip>
                )}
                {file && <FileLink path={file} />}
            </Box>
            <Box>{children}</Box>
        </TimelineContent>
    );
});
