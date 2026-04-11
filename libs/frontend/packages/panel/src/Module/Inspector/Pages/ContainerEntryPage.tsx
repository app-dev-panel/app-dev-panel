import {useGetObjectQuery} from '@app-dev-panel/panel/Module/Inspector/API/Inspector';
import {FileLink} from '@app-dev-panel/sdk/Component/FileLink';
import {FullScreenCircularProgress} from '@app-dev-panel/sdk/Component/FullScreenCircularProgress';
import {JsonRenderer} from '@app-dev-panel/sdk/Component/JsonRenderer';
import {PageHeader} from '@app-dev-panel/sdk/Component/PageHeader';
import {QueryErrorState} from '@app-dev-panel/sdk/Component/QueryErrorState';
import {Box} from '@mui/material';
import {useSearchParams} from 'react-router';

export const ContainerEntryPage = () => {
    const [searchParams] = useSearchParams();
    const objectClass = searchParams.get('class') || '';
    const {data, isLoading, isError, error, refetch} = useGetObjectQuery(objectClass);

    if (isLoading) {
        return <FullScreenCircularProgress />;
    }

    if (isError) {
        return (
            <>
                <PageHeader title={objectClass} icon="inventory_2" description="Container entry details" />
                <QueryErrorState
                    error={error}
                    title="Failed to load container entry"
                    fallback={`Failed to load container entry "${objectClass}".`}
                    onRetry={refetch}
                />
            </>
        );
    }

    return (
        <pre>
            <Box sx={{display: 'flex', alignItems: 'center', gap: 1}}>
                <PageHeader title={objectClass} icon="inventory_2" description="Container entry details" />
                {data?.path && <FileLink path={data.path} />}
            </Box>
            <JsonRenderer value={data?.object} />
        </pre>
    );
};
