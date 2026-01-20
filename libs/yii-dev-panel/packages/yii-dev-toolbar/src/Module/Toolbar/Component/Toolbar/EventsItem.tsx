import {ChatBubble} from '@mui/icons-material';
import {Badge, Button} from '@mui/material';
import {DebugEntry} from '@yiisoft/yii-dev-panel-sdk/API/Debug/Debug';
import {CollectorsMap} from '@yiisoft/yii-dev-panel-sdk/Helper/collectors';
import {ForwardedRef, forwardRef} from 'react';

type EventsItemProps = {
    data: DebugEntry;
    iframeUrlHandler: (url: string) => void;
};

const EventsItem = forwardRef((props: EventsItemProps, ref: ForwardedRef<HTMLButtonElement>) => {
    const {data, iframeUrlHandler, ...others} = props;

    return (
        <Badge color="secondary" badgeContent={String(data.event?.total)}>
            <Button
                ref={ref}
                href={`/debug?collector=${CollectorsMap.EventCollector}&debugEntry=${data.id}`}
                onClick={(e) => {
                    iframeUrlHandler(`/debug?collector=${CollectorsMap.EventCollector}&debugEntry=${data.id}`);
                    e.stopPropagation();
                    e.preventDefault();
                }}
                color="info"
                variant="contained"
                sx={{
                    whiteSpace: 'nowrap',
                    textTransform: 'none',
                    borderRadius: 0,
                }}
            >
                Events
            </Button>
        </Badge>
    );
});
EventsItem.displayName = Button.name;
export {EventsItem};
