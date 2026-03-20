import {useBreadcrumbs} from '@app-dev-panel/panel/Application/Context/BreadcrumbsContext';
import {useGetObjectQuery} from '@app-dev-panel/panel/Module/Inspector/API/Inspector';
import {FullScreenCircularProgress} from '@app-dev-panel/sdk/Component/FullScreenCircularProgress';
import {JsonRenderer} from '@app-dev-panel/sdk/Component/JsonRenderer';
import {PageHeader} from '@app-dev-panel/sdk/Component/PageHeader';
import {FilePresent} from '@mui/icons-material';
import {Box, IconButton, Tooltip} from '@mui/material';
import {useSearchParams} from 'react-router-dom';

export const ContainerEntryPage = () => {
    const [searchParams] = useSearchParams();
    const objectClass = searchParams.get('class') || '';
    const {data, isLoading} = useGetObjectQuery(objectClass);
    useBreadcrumbs(() => ['Inspector', 'Container Entry']);

    if (isLoading) {
        return <FullScreenCircularProgress />;
    }

    return (
        <pre>
            <Box sx={{display: 'flex', alignItems: 'center', gap: 1}}>
                <PageHeader title={objectClass} icon="inventory_2" description="Container entry details" />
                <Tooltip title="Examine as a file">
                    <IconButton size="small" href={'/inspector/files?path=' + data?.path}>
                        <FilePresent fontSize="small" />
                    </IconButton>
                </Tooltip>
            </Box>
            <JsonRenderer value={data?.object} />
        </pre>
    );
};
