import {FilePresent} from '@mui/icons-material';
import {IconButton, Tooltip} from '@mui/material';
import {FullScreenCircularProgress} from '@yiisoft/yii-dev-panel-sdk/Component/FullScreenCircularProgress';
import {JsonRenderer} from '@yiisoft/yii-dev-panel-sdk/Component/JsonRenderer';
import {useGetObjectQuery} from '@yiisoft/yii-dev-panel/Module/Inspector/API/Inspector';
import {useSearchParams} from 'react-router-dom';
import {useBreadcrumbs} from '@yiisoft/yii-dev-panel/Application/Context/BreadcrumbsContext';

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
