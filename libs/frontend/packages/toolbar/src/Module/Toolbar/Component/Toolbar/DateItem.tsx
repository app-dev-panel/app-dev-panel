import {DebugEntry} from '@app-dev-panel/sdk/API/Debug/Debug';
import ScheduleIcon from '@mui/icons-material/Schedule';
import {Chip} from '@mui/material';
import {format, fromUnixTime} from 'date-fns';

type DateItemProps = {data: DebugEntry};

export const DateItem = ({data}: DateItemProps) => {
    return (
        <Chip
            icon={<ScheduleIcon sx={{fontSize: '16px !important'}} />}
            label={format(fromUnixTime((data.web || data.console).request.startTime), 'HH:mm:ss')}
            size="small"
            variant="outlined"
            sx={{height: 32, borderRadius: 1, fontSize: 12, color: 'text.secondary'}}
        />
    );
};
