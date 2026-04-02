import {DebugEntry} from '@app-dev-panel/sdk/API/Debug/Debug';
import {CollectorsMap} from '@app-dev-panel/sdk/Helper/collectors';
import CheckCircleOutlineIcon from '@mui/icons-material/CheckCircleOutline';
import ErrorOutlineIcon from '@mui/icons-material/ErrorOutline';
import {Chip, Tooltip} from '@mui/material';

type ValidatorItemProps = {data: DebugEntry; iframeUrlHandler: (url: string) => void};

export const ValidatorItem = ({data, iframeUrlHandler}: ValidatorItemProps) => {
    if (!data.validator || data.validator.total === 0) {
        return null;
    }

    const hasErrors = data.validator.invalid > 0;

    return (
        <Tooltip title={hasErrors ? `${data.validator.invalid} validation errors` : 'All valid'} arrow>
            <Chip
                icon={
                    hasErrors ? (
                        <ErrorOutlineIcon sx={{fontSize: '16px !important'}} />
                    ) : (
                        <CheckCircleOutlineIcon sx={{fontSize: '16px !important'}} />
                    )
                }
                label={`Valid ${data.validator.total}`}
                size="small"
                color={hasErrors ? 'warning' : 'default'}
                variant={hasErrors ? 'filled' : 'outlined'}
                onClick={(e) => {
                    iframeUrlHandler(`/debug?collector=${CollectorsMap.ValidatorCollector}&debugEntry=${data.id}`);
                    e.stopPropagation();
                    e.preventDefault();
                }}
                sx={{height: 32, borderRadius: 1, fontSize: 12, cursor: 'pointer'}}
            />
        </Tooltip>
    );
};
