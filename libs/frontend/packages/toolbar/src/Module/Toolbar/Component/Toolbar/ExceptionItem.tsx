import {DebugEntry} from '@app-dev-panel/sdk/API/Debug/Debug';
import {CollectorsMap} from '@app-dev-panel/sdk/Helper/collectors';
import ErrorIcon from '@mui/icons-material/Error';
import {Chip, Tooltip} from '@mui/material';

type ExceptionItemProps = {data: DebugEntry; iframeUrlHandler: (url: string) => void};

export const ExceptionItem = ({data, iframeUrlHandler}: ExceptionItemProps) => {
    if (!data.exception) {
        return null;
    }

    const exceptionClass = data.exception.class ?? 'Exception';
    const shortClass = exceptionClass.split('\\').pop() || exceptionClass;
    const exceptionMessage = data.exception.message ?? '';
    const message = exceptionMessage.length > 120 ? exceptionMessage.slice(0, 120) + '...' : exceptionMessage;

    return (
        <Tooltip title={message ? `${exceptionClass}: ${message}` : exceptionClass} arrow>
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
