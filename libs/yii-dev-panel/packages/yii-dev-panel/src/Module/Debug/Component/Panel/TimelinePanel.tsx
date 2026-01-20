import {ArrowDownward} from '@mui/icons-material';
import Timeline from '@mui/lab/Timeline';
import TimelineConnector from '@mui/lab/TimelineConnector';
import TimelineContent from '@mui/lab/TimelineContent';
import TimelineDot from '@mui/lab/TimelineDot';
import TimelineItem from '@mui/lab/TimelineItem';
import TimelineOppositeContent from '@mui/lab/TimelineOppositeContent';
import TimelineSeparator from '@mui/lab/TimelineSeparator';
import {Alert, AlertTitle, Tooltip} from '@mui/material';
import Typography from '@mui/material/Typography';
import {isClassString} from '@yiisoft/yii-dev-panel-sdk/Helper/classMatcher';
import {formatMicrotime} from '@yiisoft/yii-dev-panel-sdk/Helper/formatDate';
import {toObjectString} from '@yiisoft/yii-dev-panel-sdk/Helper/objectString';
import {JsonRenderer} from '@yiisoft/yii-dev-panel/Module/Debug/Component/JsonRenderer';
import Box from '@mui/material/Box';
import React from 'react';

type Item = [number, number, string] | [number, number, string, string];
type TimelinePanelProps = {
    data: Item[];
};
export const TimelinePanel = ({data}: TimelinePanelProps) => {
    if (!data || !Array.isArray(data) || data.length === 0) {
        return (
            <Box m={2}>
                <Alert severity="info">
                    <AlertTitle>No timeline items found during the process</AlertTitle>
                </Alert>
            </Box>
        );
    }
    return (
        <Timeline position="alternate">
            {data.map((row, index) => (
                <TimelineItem key={index}>
                    <TimelineOppositeContent sx={{m: 'auto 0'}} color="text.secondary">
                        <Tooltip title={row[0]}>
                            <Typography component="span">{formatMicrotime(row[0])}</Typography>
                        </Tooltip>
                    </TimelineOppositeContent>
                    <TimelineSeparator>
                        <TimelineDot color="info">
                            <ArrowDownward />
                        </TimelineDot>
                        {index !== data.length - 1 && <TimelineConnector />}
                    </TimelineSeparator>
                    <TimelineContent sx={{py: '12px', px: 2}}>
                        <Tooltip title={row[2]}>
                            <Typography component="span">{row[2].split('\\').pop()}</Typography>
                        </Tooltip>
                        {!!row[3] && (
                            <JsonRenderer value={isClassString(row[3]) ? toObjectString(row[3], row[1]) : row[3]} />
                        )}
                    </TimelineContent>
                </TimelineItem>
            ))}
        </Timeline>
    );
};
