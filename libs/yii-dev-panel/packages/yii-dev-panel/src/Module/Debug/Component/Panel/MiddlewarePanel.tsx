import {ArrowDownward} from '@mui/icons-material';
import Timeline from '@mui/lab/Timeline';
import TimelineConnector from '@mui/lab/TimelineConnector';
import TimelineDot from '@mui/lab/TimelineDot';
import TimelineItem from '@mui/lab/TimelineItem';
import TimelineOppositeContent from '@mui/lab/TimelineOppositeContent';
import TimelineSeparator from '@mui/lab/TimelineSeparator';
import {Tooltip} from '@mui/material';
import Typography from '@mui/material/Typography';
import {formatMicrotime} from '@yiisoft/yii-dev-panel-sdk/Helper/formatDate';
import {TimelineContentWrapper} from '@yiisoft/yii-dev-panel/Module/Debug/Component/Timeline/TimelineContentWrapper';

type MiddlewareType = {
    memory: number;
    name: string;
    time: number;
};
type BeforeMiddlewareType = {request: string} & MiddlewareType;
type AfterMiddlewareType = {response: string} & MiddlewareType;

type ActionHandlerType = {
    memory: number;
    name: string;
    request: string;
    startTime: number;
    endTime: number;
};
type MiddlewarePanelProps = {
    beforeStack: BeforeMiddlewareType[];
    afterStack: AfterMiddlewareType[];
    actionHandler: ActionHandlerType;
};

export const MiddlewarePanel = (props: MiddlewarePanelProps) => {
    const {beforeStack, afterStack, actionHandler} = props;

    return (
        <Timeline position="alternate">
            {beforeStack &&
                beforeStack.length > 0 &&
                beforeStack.map((middleware, index) => (
                    <TimelineItem key={index}>
                        <TimelineOppositeContent sx={{m: 'auto 0'}} color="text.secondary">
                            <Tooltip title={middleware.time}>
                                <Typography component="span">{formatMicrotime(middleware.time)}</Typography>
                            </Tooltip>
                        </TimelineOppositeContent>
                        <TimelineSeparator>
                            <TimelineDot color="info">
                                <ArrowDownward />
                            </TimelineDot>
                            <TimelineConnector />
                        </TimelineSeparator>
                        <TimelineContentWrapper name={middleware.name} payload={middleware.request} file={'1'} />
                    </TimelineItem>
                ))}
            {typeof actionHandler === 'object' &&
                !Array.isArray(actionHandler) &&
                [actionHandler].map((middleware, index) => (
                    <TimelineItem key={index}>
                        <TimelineOppositeContent sx={{m: 'auto 0'}} align="right" color="text.primary">
                            <Tooltip title={middleware.startTime}>
                                <Typography>{formatMicrotime(middleware.startTime)}</Typography>
                            </Tooltip>
                        </TimelineOppositeContent>
                        <TimelineSeparator>
                            <TimelineDot color="success">
                                <ArrowDownward />
                            </TimelineDot>
                        </TimelineSeparator>
                        <TimelineContentWrapper name={middleware.name} payload={middleware.request} file={'2'} />
                    </TimelineItem>
                ))}
            {afterStack &&
                afterStack.length > 0 &&
                afterStack.map((middleware, index) => (
                    <TimelineItem key={index}>
                        <TimelineOppositeContent sx={{m: 'auto 0'}} color="text.secondary">
                            <Tooltip title={middleware.time || '0'}>
                                <Typography component="span">{formatMicrotime(middleware.time)}</Typography>
                            </Tooltip>
                        </TimelineOppositeContent>
                        <TimelineSeparator>
                            <TimelineConnector />
                            <TimelineDot color="info">
                                <ArrowDownward />
                            </TimelineDot>
                        </TimelineSeparator>
                        <TimelineContentWrapper name={middleware.name} payload={middleware.response} file={'3'} />
                    </TimelineItem>
                ))}
        </Timeline>
    );
};
