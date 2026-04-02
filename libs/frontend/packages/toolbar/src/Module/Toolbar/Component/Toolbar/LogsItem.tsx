import {DebugEntry} from '@app-dev-panel/sdk/API/Debug/Debug';
import {CollectorsMap} from '@app-dev-panel/sdk/Helper/collectors';
import DescriptionOutlinedIcon from '@mui/icons-material/DescriptionOutlined';
import {Chip, Tooltip} from '@mui/material';

type LogsItemProps = {data: DebugEntry; iframeUrlHandler: (url: string) => void};

export const LogsItem = ({data, iframeUrlHandler}: LogsItemProps) => {
    const total = data.logger?.total ?? 0;
    if (total === 0) {
        return null;
    }

    return (
        <Tooltip title={`${total} log entries`} arrow>
            <Chip
                icon={<DescriptionOutlinedIcon sx={{fontSize: '16px !important'}} />}
                label={`Logs ${total}`}
                size="small"
                variant="outlined"
                onClick={(e) => {
                    iframeUrlHandler(`/debug?collector=${CollectorsMap.LogCollector}&debugEntry=${data.id}`);
                    e.stopPropagation();
                    e.preventDefault();
                }}
                sx={{height: 32, borderRadius: 1, fontSize: 12, cursor: 'pointer'}}
            />
        </Tooltip>
    );
};
