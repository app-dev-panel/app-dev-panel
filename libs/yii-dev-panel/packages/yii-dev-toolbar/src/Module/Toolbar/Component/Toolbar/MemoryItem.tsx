import {Button, Tooltip} from '@mui/material';
import {DebugEntry} from '@yiisoft/yii-dev-panel-sdk/API/Debug/Debug';
import {CollectorsMap} from '@yiisoft/yii-dev-panel-sdk/Helper/collectors';
import {isDebugEntryAboutWeb} from '@yiisoft/yii-dev-panel-sdk/Helper/debugEntry';
import {formatBytes} from '@yiisoft/yii-dev-panel-sdk/Helper/formatBytes';

type MemoryItemProps = {data: DebugEntry; iframeUrlHandler: (url: string) => void};

export const MemoryItem = (props: MemoryItemProps) => {
    const {data, iframeUrlHandler, ...others} = props;
    const collector = isDebugEntryAboutWeb(data)
        ? CollectorsMap.WebAppInfoCollector
        : CollectorsMap.ConsoleAppInfoCollector;

    return (
        <Tooltip title={`${(data.web || data.console).memory.peakUsage.toLocaleString(undefined)} bytes`} arrow>
            <Button
                href={`/debug?collector=${collector}&debugEntry=${data.id}`}
                onClick={(e) => {
                    iframeUrlHandler(`/debug?collector=${collector}&debugEntry=${data.id}`);
                    e.stopPropagation();
                    e.preventDefault();
                }}
                color="info"
                variant="contained"
                sx={{whiteSpace: 'nowrap', textTransform: 'none', borderRadius: 0}}
            >
                {formatBytes((data.web || data.console).memory.peakUsage)}
            </Button>
        </Tooltip>
    );
};
