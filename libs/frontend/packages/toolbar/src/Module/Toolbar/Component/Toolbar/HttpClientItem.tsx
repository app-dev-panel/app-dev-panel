import {DebugEntry} from '@app-dev-panel/sdk/API/Debug/Debug';
import {CollectorsMap} from '@app-dev-panel/sdk/Helper/collectors';
import {openInNewTabOnModifier} from '@app-dev-panel/sdk/Helper/openInNewTabOnModifier';
import HttpIcon from '@mui/icons-material/Http';
import {Chip, Tooltip} from '@mui/material';

type HttpClientItemProps = {data: DebugEntry; iframeUrlHandler: (url: string) => void};

export const HttpClientItem = ({data, iframeUrlHandler}: HttpClientItemProps) => {
    if (!data.http || data.http.count === 0) {
        return null;
    }

    const totalTime = data.http.totalTime;
    const timeStr = totalTime >= 1 ? `${totalTime.toFixed(1)}s` : `${(totalTime * 1000).toFixed(0)}ms`;

    return (
        <Tooltip title={`${data.http.count} HTTP requests, total ${timeStr}`} arrow>
            <Chip
                icon={<HttpIcon sx={{fontSize: '16px !important'}} />}
                label={`HTTP ${data.http.count}`}
                size="small"
                variant="outlined"
                onClick={(e) => {
                    const url = `/debug?collector=${CollectorsMap.HttpClientCollector}&debugEntry=${data.id}`;
                    if (openInNewTabOnModifier(e, url)) return;
                    iframeUrlHandler(url);
                    e.stopPropagation();
                    e.preventDefault();
                }}
                sx={{height: 32, borderRadius: 1, fontSize: 12, cursor: 'pointer'}}
            />
        </Tooltip>
    );
};
