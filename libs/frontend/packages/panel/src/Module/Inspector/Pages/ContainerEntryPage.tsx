import {useBreadcrumbs} from '@app-dev-panel/panel/Application/Context/BreadcrumbsContext';
import {useGetObjectQuery} from '@app-dev-panel/panel/Module/Inspector/API/Inspector';
import {FullScreenCircularProgress} from '@app-dev-panel/sdk/Component/FullScreenCircularProgress';
import {JsonRenderer} from '@app-dev-panel/sdk/Component/JsonRenderer';
import {FilePresent} from '@mui/icons-material';
import {IconButton, Tooltip} from '@mui/material';
import {useSearchParams} from 'react-router-dom';

export const ContainerEntryPage = () => {
    const [searchParams] = useSearchParams();
    const objectClass = searchParams.get('class') || '';
    const {data, isLoading} = useGetObjectQuery(objectClass);

    if (isLoading) {
        return <FullScreenCircularProgress />;
    }

    useBreadcrumbs(() => ['Inspector', 'Container Entry']);

    return (
        <pre>
            <h2>
                {objectClass}{' '}
                <Tooltip title="Examine as a file">
                    <IconButton size="small" href={'/inspector/files?path=' + data?.path}>
                        <FilePresent fontSize="small" />
                    </IconButton>
                </Tooltip>
            </h2>
            <JsonRenderer value={data?.object} />
        </pre>
    );
};
