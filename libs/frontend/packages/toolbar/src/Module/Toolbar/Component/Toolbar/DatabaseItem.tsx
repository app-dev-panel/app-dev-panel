import {DebugEntry} from '@app-dev-panel/sdk/API/Debug/Debug';
import {CollectorsMap} from '@app-dev-panel/sdk/Helper/collectors';
import StorageIcon from '@mui/icons-material/Storage';
import {Chip, Tooltip} from '@mui/material';

type DatabaseItemProps = {data: DebugEntry; iframeUrlHandler: (url: string) => void};

export const DatabaseItem = ({data, iframeUrlHandler}: DatabaseItemProps) => {
    if (!data.db || data.db.queries.total === 0) {
        return null;
    }

    const hasErrors = data.db.queries.error > 0;
    const total = data.db.queries.total;
    const tooltipParts = [`${total} queries`];
    if (data.db.queries.error > 0) {
        tooltipParts.push(`${data.db.queries.error} errors`);
    }
    if (data.db.duplicateGroups && data.db.duplicateGroups > 0) {
        tooltipParts.push(`${data.db.duplicateGroups} duplicate groups`);
    }

    return (
        <Tooltip title={tooltipParts.join(', ')} arrow>
            <Chip
                icon={<StorageIcon sx={{fontSize: '16px !important'}} />}
                label={hasErrors ? `DB ${total} / ${data.db.queries.error} err` : `DB ${total}`}
                size="small"
                color={hasErrors ? 'error' : 'default'}
                variant={hasErrors ? 'filled' : 'outlined'}
                onClick={(e) => {
                    const url = `/debug?collector=${CollectorsMap.DatabaseCollector}&debugEntry=${data.id}`;
                    if (e.ctrlKey || e.metaKey) {
                        window.open(url, '_blank', 'noopener');
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
