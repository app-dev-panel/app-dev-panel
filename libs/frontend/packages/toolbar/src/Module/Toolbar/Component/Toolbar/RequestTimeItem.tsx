import {DebugEntry} from '@app-dev-panel/sdk/API/Debug/Debug';
import {CollectorsMap} from '@app-dev-panel/sdk/Helper/collectors';
import AccessTimeIcon from '@mui/icons-material/AccessTime';
import {Chip, Tooltip} from '@mui/material';

type RequestTimeItemProps = {data: DebugEntry; iframeUrlHandler: (url: string) => void};
export const RequestTimeItem = ({data, iframeUrlHandler}: RequestTimeItemProps) => {
    const time = (data.web || data.console).request.processingTime;
    const ms = time * 1000;

    return (
        <Tooltip title={`${ms.toFixed(1)} ms`} arrow>
            <Chip
                icon={<AccessTimeIcon sx={{fontSize: '16px !important'}} />}
                label={`${time.toFixed(3)} s`}
                size="small"
                variant="outlined"
                onClick={(e) => {
                    iframeUrlHandler(`/debug?collector=${CollectorsMap.TimelineCollector}&debugEntry=${data.id}`);
                    e.stopPropagation();
                    e.preventDefault();
                }}
                sx={{
                    height: 32,
                    borderRadius: 1,
                    fontSize: 12,
                    fontFamily: "'JetBrains Mono', monospace",
                    cursor: 'pointer',
                }}
            />
        </Tooltip>
    );
};
