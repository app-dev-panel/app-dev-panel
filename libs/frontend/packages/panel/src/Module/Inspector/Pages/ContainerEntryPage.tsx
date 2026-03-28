import {useGetObjectQuery} from '@app-dev-panel/panel/Module/Inspector/API/Inspector';
import {FileLink} from '@app-dev-panel/sdk/Component/FileLink';
import {FullScreenCircularProgress} from '@app-dev-panel/sdk/Component/FullScreenCircularProgress';
import {JsonRenderer} from '@app-dev-panel/sdk/Component/JsonRenderer';
import {PageHeader} from '@app-dev-panel/sdk/Component/PageHeader';
import {Box} from '@mui/material';
import {useSearchParams} from 'react-router-dom';

export const ContainerEntryPage = () => {
    const [searchParams] = useSearchParams();
    const objectClass = searchParams.get('class') || '';
    const {data, isLoading} = useGetObjectQuery(objectClass);

    if (isLoading) {
        return <FullScreenCircularProgress />;
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
