import {Badge, Button} from '@mui/material';
import {DebugEntry} from '@yiisoft/yii-dev-panel-sdk/API/Debug/Debug';
import {format, fromUnixTime} from 'date-fns';
import {ForwardedRef, forwardRef} from 'react';

type DateItemProps = {
    data: DebugEntry;
};

const DateItem = forwardRef((props: DateItemProps, ref: ForwardedRef<HTMLButtonElement>) => {
    const {data, ...others} = props;
    return (
        <Badge color="secondary" badgeContent={String(data.event?.total)}>
            <Button
                ref={ref}
                color="info"
                variant="contained"
                sx={{
                    whiteSpace: 'nowrap',
                    textTransform: 'none',
                    borderRadius: 0,
                }}
            >
                {format(fromUnixTime((data.web || data.console).request.startTime), 'do MMM HH:mm:ss')}
            </Button>
        </Badge>
    );
});
DateItem.displayName = Button.name;
export {DateItem};
