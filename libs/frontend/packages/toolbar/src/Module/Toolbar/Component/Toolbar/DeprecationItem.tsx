import {DebugEntry} from '@app-dev-panel/sdk/API/Debug/Debug';
import {CollectorsMap} from '@app-dev-panel/sdk/Helper/collectors';
import {openInNewTabOnModifier} from '@app-dev-panel/sdk/Helper/openInNewTabOnModifier';
import {panelPagePath} from '@app-dev-panel/sdk/Helper/panelMountPath';
import WarningAmberIcon from '@mui/icons-material/WarningAmber';
import {Chip, Tooltip} from '@mui/material';

type DeprecationItemProps = {data: DebugEntry; iframeUrlHandler: (url: string) => void};

export const DeprecationItem = ({data, iframeUrlHandler}: DeprecationItemProps) => {
    if (!data.deprecation || data.deprecation.total === 0) {
        return null;
    }

    return (
        <Tooltip title={`${data.deprecation.total} deprecation warnings`} arrow>
            <Chip
                icon={<WarningAmberIcon sx={{fontSize: '16px !important'}} />}
                label={`Depr ${data.deprecation.total}`}
                size="small"
                color="warning"
                variant="filled"
                onClick={(e) => {
                    const url = panelPagePath(
                        `/?collector=${encodeURIComponent(CollectorsMap.DeprecationCollector)}&debugEntry=${data.id}`,
                    );
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
