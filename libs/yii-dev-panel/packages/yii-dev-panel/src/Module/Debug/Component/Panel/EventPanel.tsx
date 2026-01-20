import {ArrowDownward, OpenInNew} from '@mui/icons-material';
import Timeline from '@mui/lab/Timeline';
import TimelineConnector from '@mui/lab/TimelineConnector';
import TimelineDot from '@mui/lab/TimelineDot';
import TimelineItem from '@mui/lab/TimelineItem';
import TimelineOppositeContent, { timelineOppositeContentClasses } from '@mui/lab/TimelineOppositeContent';
import TimelineSeparator from '@mui/lab/TimelineSeparator';
import {Alert, AlertTitle, IconButton, Tooltip} from '@mui/material';
import Typography from '@mui/material/Typography';
import {parseFilename, parseFilePathWithLineAnchor} from '@yiisoft/yii-dev-panel-sdk/Helper/filePathParser';
import {formatMicrotime} from '@yiisoft/yii-dev-panel-sdk/Helper/formatDate';
import {TimelineContentWrapper} from '@yiisoft/yii-dev-panel/Module/Debug/Component/Timeline/TimelineContentWrapper';
import Box from '@mui/material/Box';
import React from 'react';

type EventType = {
    event: string;
    file: string;
    line: string;
    name: string;
    time: number;
};

const Line = ({event}: {event: EventType}) => {
    const line = parseFilename(event.line);
    return (
        <Typography sx={{whiteSpace: 'nowrap'}}>
            <Tooltip title={event.line}>
                <Typography component="span" sx={{fontSize: 'calc(100% - 2px)'}}>
                    {line}
                </Typography>
            </Tooltip>
            <Tooltip title="Open in File Explorer">
                <IconButton size="small" href={`/inspector/files?path=${parseFilePathWithLineAnchor(event.line)}`}>
                    <OpenInNew fontSize="small" />
                </IconButton>
            </Tooltip>
        </Typography>
    );
};

type EventTimelineProps = {
    events: EventType[];
};
export const EventPanel = ({events}: EventTimelineProps) => {
    if (!events || events.length === 0) {
        return (
            <Box m={2}>
                <Alert severity="info">
                    <AlertTitle>No dispatched events found during the process</AlertTitle>
                </Alert>
            </Box>
        );
    }

    return (
        <Timeline sx={{ [`& .${timelineOppositeContentClasses.root}`]: { flex: 0 } }}>
            {events &&
                events.map((event, index) => (
                    <TimelineItem key={index}>
                        <TimelineOppositeContent sx={{m: 'auto 0'}} color="text.secondary">
                            <Tooltip title={event.time}>
                                <Typography component="span">{formatMicrotime(event.time)}</Typography>
                            </Tooltip>
                        </TimelineOppositeContent>
                        <TimelineSeparator>
                            <TimelineDot sx={{m: '22px 0 6px'}} color="info">
                                <ArrowDownward />
                            </TimelineDot>
                            {index !== events.length - 1 && <TimelineConnector />}
                        </TimelineSeparator>
                        <TimelineContentWrapper name={event.name} file={event.file} payload={event.event}>
                            <Line event={event} />
                        </TimelineContentWrapper>
                    </TimelineItem>
                ))}
        </Timeline>
    );
};
