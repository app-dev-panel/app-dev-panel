import {DebugEntry} from '@app-dev-panel/sdk/API/Debug/Debug';
import {CollectorsMap} from '@app-dev-panel/sdk/Helper/collectors';
import {Badge, Button} from '@mui/material';

type ValidatorItemProps = {data: DebugEntry; iframeUrlHandler: (url: string) => void};

export const ValidatorItem = (props: ValidatorItemProps) => {
    const {data, iframeUrlHandler, ...others} = props;

    if (!data.validator || data.validator.total === 0) {
        return null;
    }

    return (
        <Badge color="secondary" badgeContent={String(data.validator.total)}>
            <Button
                href={`/debug?collector=${CollectorsMap.ValidatorCollector}&debugEntry=${data.id}`}
                onClick={(e) => {
                    iframeUrlHandler(`/debug?collector=${CollectorsMap.ValidatorCollector}&debugEntry=${data.id}`);
                    e.stopPropagation();
                    e.preventDefault();
                }}
                color={data.validator.invalid === 0 ? 'info' : 'warning'}
                variant="contained"
                sx={{whiteSpace: 'nowrap', textTransform: 'none', borderRadius: 0}}
            >
                Validator
            </Button>
        </Badge>
    );
};
