import {JsonRenderer} from '@app-dev-panel/panel/Module/Debug/Component/JsonRenderer';
import {useGetObjectQuery} from '@app-dev-panel/sdk/API/Debug/Debug';
import {FullScreenCircularProgress} from '@app-dev-panel/sdk/Component/FullScreenCircularProgress';
import {Typography} from '@mui/material';
import Box from '@mui/material/Box';
import {useSearchParams} from 'react-router-dom';

export const ObjectPage = () => {
    const [searchParams, setSearchParams] = useSearchParams();
    const objectId = searchParams.get('id');
    const debugEntryId = searchParams.get('debugEntry') || '';

    const {data, isLoading} = useGetObjectQuery({debugEntryId: debugEntryId, objectId: +(objectId || 0)});
    if (isLoading) {
        return <FullScreenCircularProgress />;
    }
    return (
        <Box>
            <Typography variant="h6" my={1}>
                {data.class}#{objectId}
            </Typography>
            <JsonRenderer value={data.value} />
        </Box>
    );
};
