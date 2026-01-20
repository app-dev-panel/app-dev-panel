import {ChatBubble} from '@mui/icons-material';
import {Badge, Button} from '@mui/material';
import {DebugEntry} from '@yiisoft/yii-dev-panel-sdk/API/Debug/Debug';
import {CollectorsMap} from '@yiisoft/yii-dev-panel-sdk/Helper/collectors';
import {forwardRef} from 'react';

type LogsItemProps = {
    data: DebugEntry;
    iframeUrlHandler: (url: string) => void;
};

export const LogsItem = forwardRef<HTMLButtonElement, LogsItemProps>((props, ref) => {
    const {data, iframeUrlHandler, ...others} = props;

    return (
        <Badge color="secondary" badgeContent={String(data.logger?.total)}>
            <Button
                ref={ref}
                href={`/debug?collector=${CollectorsMap.LogCollector}&debugEntry=${data.id}`}
                onClick={(e) => {
                    iframeUrlHandler(`/debug?collector=${CollectorsMap.LogCollector}&debugEntry=${data.id}`);
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
                Logs
            </Button>
        </Badge>
    );
});
