import {DebugEntry} from '@app-dev-panel/sdk/API/Debug/Debug';
import {CollectorsMap} from '@app-dev-panel/sdk/Helper/collectors';
import BoltIcon from '@mui/icons-material/Bolt';
import {Chip, Tooltip} from '@mui/material';

type EventsItemProps = {data: DebugEntry; iframeUrlHandler: (url: string) => void};

export const EventsItem = ({data, iframeUrlHandler}: EventsItemProps) => {
    const total = data.event?.total ?? 0;
    if (total === 0) {
        return null;
    }

    return (
        <Tooltip title={`${total} events`} arrow>
            <Chip
                icon={<BoltIcon sx={{fontSize: '16px !important'}} />}
                label={`Events ${total}`}
                size="small"
                variant="outlined"
                onClick={(e) => {
                    const url = `/debug?collector=${CollectorsMap.EventCollector}&debugEntry=${data.id}`;
                    if (e.ctrlKey || e.metaKey) {
                        window.open(url, '_blank', 'noopener,noreferrer');
                    } else {
                        iframeUrlHandler(url);
                    }
                    e.stopPropagation();
                    e.preventDefault();
                }}
                sx={{height: 32, borderRadius: 1, fontSize: 12, cursor: 'pointer'}}
            />
        </Tooltip>
    );
};
