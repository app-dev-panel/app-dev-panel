import {ContentCopy, OpenInNew} from '@mui/icons-material';
import CheckIcon from '@mui/icons-material/Check';
import {Alert, AlertTitle, FormHelperText, IconButton, InputBase, Paper, Tooltip, Typography} from '@mui/material';
import {GridColDef, GridRenderCellParams, GridValidRowModel} from '@mui/x-data-grid';
import {FullScreenCircularProgress} from '@yiisoft/yii-dev-panel-sdk/Component/FullScreenCircularProgress';
import {DataTable} from '@yiisoft/yii-dev-panel-sdk/Component/Grid';
import {JsonRenderer} from '@yiisoft/yii-dev-panel-sdk/Component/JsonRenderer';
import {serializeCallable} from '@yiisoft/yii-dev-panel-sdk/Helper/callableSerializer';
import {concatClassMethod} from '@yiisoft/yii-dev-panel-sdk/Helper/classMethodConcater';
import {useGetRoutesQuery, useLazyGetCheckRouteQuery} from '@yiisoft/yii-dev-panel/Module/Inspector/API/Inspector';
import clipboardCopy from 'clipboard-copy';
import {useEffect, useState} from 'react';
import {useBreadcrumbs} from '@yiisoft/yii-dev-panel/Application/Context/BreadcrumbsContext';

const columns: GridColDef[] = [
    {
        field: 'name',
        headerName: 'Name',
        width: 150,
        renderCell: (params: GridRenderCellParams) => {
            return (
                <Typography component="span" sx={{wordBreak: 'break-word'}}>
                    {params.value}
                </Typography>
            );
        },
    },
    {
        field: 'method',
        headerName: 'method',
        width: 80,
        renderCell: (params: GridRenderCellParams) => {
            return (
                <Typography component="span" sx={{wordBreak: 'break-word'}}>
                    {params.value}
                </Typography>
            );
        },
    },
    {
        field: 'pattern',
        headerName: 'Pattern',
        width: 300,
        renderCell: (params: GridRenderCellParams) => {
            return (
                <Typography component="span" sx={{wordBreak: 'break-word'}}>
                    {params.value}
                </Typography>
            );
        },
    },
    {
        field: 'action',
        headerName: 'Action',
        flex: 1,
        renderCell: (params: GridRenderCellParams) => {
            const value = params.value;
            if (!Array.isArray(value)) {
                return value;
            }
            const className = value[0] as string;
            const methodName = value[1] as string;

            return (
                <div style={{wordBreak: 'break-all'}}>
                    <Tooltip title="Copy">
                        <IconButton
                            size="small"
                            onClick={() => clipboardCopy(concatClassMethod(className, methodName))}
                        >
                            <ContentCopy fontSize="small" />
                        </IconButton>
                    </Tooltip>
                    <Tooltip title="Examine as a container entry">
                        <IconButton size="small" href={'/inspector/container/view?class=' + className}>
                            <OpenInNew fontSize="small" />
                        </IconButton>
                    </Tooltip>

                    <Typography component="span" sx={{wordBreak: 'break-word'}}>
                        {concatClassMethod(className.split('\\').pop() as string, methodName)}
                    </Typography>
                </div>
            );
        },
    },

    {
        field: 'middlewares',
        headerName: 'Middlewares',
        flex: 1,
        renderCell: (params: GridRenderCellParams) => {
            return <JsonRenderer depth={0} value={params.value} />;
        },
    },
];

function collectGroupsAndRoutes(data: any): RouteType[] {
    const routes: RouteType[] = [];
    let i = 0;
    for (const route of data) {
        let action = undefined;
        if (Array.isArray(route.middlewares)) {
            const lastMiddleware = route.middlewares.at(-1);
            if (Array.isArray(lastMiddleware)) {
                action = [lastMiddleware[0], lastMiddleware[1]];
            }
        }
        for (const method of route.methods.filter((method: string) => !['OPTIONS', 'HEAD'].includes(method))) {
            routes.push({
                id: String(i++),
                name: route.name,
                pattern: route.pattern,
                method: method,
                middlewares: route.middlewares,
                action: action,
            });
        }
    }

    return routes.sort((one, two) => {
        return one.pattern.localeCompare(two.pattern);
    });
}

type RouteType = {
    id: string;
    name: string;
    pattern: string;
    method: string;
    middlewares: any[];
    action: string[] | undefined;
};

export const RoutesPage = () => {
    const {data, isLoading, isSuccess} = useGetRoutesQuery();
    const [checkRouteQuery, checkRouteQueryInfo] = useLazyGetCheckRouteQuery();
    const [routes, setRoutes] = useState<RouteType[]>([]);
    const [url, setUrl] = useState<string>('');

    useEffect(() => {
        if (!isSuccess) {
            return;
        }
        const routes = collectGroupsAndRoutes(data);

        setRoutes(routes);
    }, [isSuccess, data]);

    const onSubmitHandler = async (event: {preventDefault: () => void}) => {
        event.preventDefault();
        console.log('route', url);

        const result = await checkRouteQuery(url);
        console.log(result.data);
    };

    useBreadcrumbs(() => ['Inspector', 'Routes']);

    if (isLoading) {
        return <FullScreenCircularProgress />;
    }

    return (
        <>
            <h2>{'Check route'}</h2>
            <Paper
                component="form"
                onSubmit={onSubmitHandler}
                sx={{p: [0.5, 1], my: 2, display: 'flex', alignItems: 'center'}}
            >
                <InputBase
                    sx={{ml: 1, flex: 1}}
                    placeholder={'/site/index, POST /auth/login, DELETE /user/1'}
                    value={url}
                    onChange={(event) => setUrl(event.target.value)}
                />
                <IconButton type="submit" sx={{p: 2}}>
                    <CheckIcon />
                </IconButton>
            </Paper>
            <FormHelperText variant="outlined">
                Add an HTTP verb in the beginning of the path such as GET, POST, PUT, PATCH and etc. to check different
                methods. <br />
                Default method is GET and it can be omitted.
            </FormHelperText>

            {checkRouteQueryInfo.data && (
                <Alert severity={checkRouteQueryInfo.data.result ? 'success' : 'error'}>
                    {checkRouteQueryInfo.data.result ? (
                        <AlertTitle>{serializeCallable(checkRouteQueryInfo.data.action)}</AlertTitle>
                    ) : (
                        <AlertTitle>{'Route is invalid'}</AlertTitle>
                    )}
                </Alert>
            )}
            <h2>{'Routes'}</h2>
            <DataTable rows={routes as GridValidRowModel[]} getRowId={(row) => row.id} columns={columns} />
        </>
    );
};
