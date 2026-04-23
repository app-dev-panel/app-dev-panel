import {ClassName} from '@app-dev-panel/panel/Application/Component/ClassName';
import {JsonRenderer} from '@app-dev-panel/panel/Module/Debug/Component/JsonRenderer';
import {useGetObjectQuery} from '@app-dev-panel/sdk/API/Debug/Debug';
import {EmptyState} from '@app-dev-panel/sdk/Component/EmptyState';
import {FullScreenCircularProgress} from '@app-dev-panel/sdk/Component/FullScreenCircularProgress';
import {Typography} from '@mui/material';
import Box from '@mui/material/Box';
import {useSearchParams} from 'react-router';

export const ObjectPage = () => {
    const [searchParams] = useSearchParams();
    const objectId = searchParams.get('id');
    const debugEntryId = searchParams.get('debugEntry') || '';

    const {data, isLoading, isError} = useGetObjectQuery(
        {debugEntryId, objectId: +(objectId || 0)},
        {skip: !debugEntryId || !objectId},
    );

    if (!debugEntryId || !objectId) {
        return (
            <EmptyState icon="data_object" title="Missing parameters" description="debugEntry and id are required" />
        );
    }

    if (isLoading) {
        return <FullScreenCircularProgress />;
    }

    if (isError || !data) {
        return (
            <EmptyState
                icon="error_outline"
                title="Object not found"
                description={`Object #${objectId} could not be loaded`}
            />
        );
    }

    return (
        <Box>
            <Typography variant="h6" my={1} component="div">
                <ClassName value={data.class}>
                    <span>
                        {data.class}#{objectId}
                    </span>
                </ClassName>
            </Typography>
            <JsonRenderer value={data.value} />
        </Box>
    );
};
