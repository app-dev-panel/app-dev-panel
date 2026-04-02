import {DebugEntry} from '@app-dev-panel/sdk/API/Debug/Debug';
import {CollectorsMap} from '@app-dev-panel/sdk/Helper/collectors';
import ErrorIcon from '@mui/icons-material/Error';
import {Chip, Tooltip} from '@mui/material';

type ExceptionItemProps = {data: DebugEntry; iframeUrlHandler: (url: string) => void};

export const ExceptionItem = ({data, iframeUrlHandler}: ExceptionItemProps) => {
    if (!data.exception) {
        return null;
    }

    const shortClass = data.exception.class.split('\\').pop() || data.exception.class;

    return (
        <Tooltip title={`${data.exception.class}: ${data.exception.message}`} arrow>
            <Chip
                icon={<ErrorIcon sx={{fontSize: '16px !important'}} />}
                label={shortClass}
                size="small"
                color="error"
                variant="filled"
                onClick={(e) => {
                    iframeUrlHandler(`/debug?collector=${CollectorsMap.ExceptionCollector}&debugEntry=${data.id}`);
                    e.stopPropagation();
                    e.preventDefault();
                }}
                sx={{height: 32, borderRadius: 1, fontSize: 12, cursor: 'pointer'}}
            />
        </Tooltip>
    );
};
