import {DebugEntry} from '@app-dev-panel/sdk/API/Debug/Debug';
import {Route} from '@mui/icons-material';
import {Chip} from '@mui/material';

type RouterItemProps = {data: DebugEntry};

export const RouterItem = ({data}: RouterItemProps) => {
    if (!data.router?.name) {
        return null;
    }
    return (
        <Chip
            icon={<Route sx={{fontSize: '14px !important'}} />}
            label={data.router.name}
            size="small"
            variant="outlined"
            sx={{height: 24, borderRadius: 1, fontSize: 11, fontFamily: "'JetBrains Mono', monospace"}}
        />
    );
};
