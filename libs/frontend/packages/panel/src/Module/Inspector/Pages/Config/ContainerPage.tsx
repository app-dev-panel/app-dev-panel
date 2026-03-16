import {useBreadcrumbs} from '@app-dev-panel/panel/Application/Context/BreadcrumbsContext';
import {useGetClassesQuery, useLazyGetObjectQuery} from '@app-dev-panel/panel/Module/Inspector/API/Inspector';
import {DataContext} from '@app-dev-panel/panel/Module/Inspector/Context/DataContext';
import {LoaderContext, LoaderContextProvider} from '@app-dev-panel/panel/Module/Inspector/Context/LoaderContext';
import {FilterInput} from '@app-dev-panel/sdk/Component/Form/FilterInput';
import {FullScreenCircularProgress} from '@app-dev-panel/sdk/Component/FullScreenCircularProgress';
import {DataTable} from '@app-dev-panel/sdk/Component/Grid';
import {JsonRenderer} from '@app-dev-panel/sdk/Component/JsonRenderer';
import {regexpQuote} from '@app-dev-panel/sdk/Helper/regexpQuote';
import {ContentCopy, OpenInNew} from '@mui/icons-material';
import {Button, IconButton, Tooltip} from '@mui/material';
import {GridColDef, GridRenderCellParams, GridValidRowModel} from '@mui/x-data-grid';
import clipboardCopy from 'clipboard-copy';
import {useCallback, useContext, useEffect, useMemo} from 'react';
import {useSearchParams} from 'react-router-dom';

const TempComponent = (params: GridRenderCellParams) => {
    const {loader} = useContext(LoaderContext);
    if (params.row.value) {
        return <JsonRenderer key={params.id} value={params.value} />;
    }

    return <Button onClick={() => loader(params.row.id)}>Load</Button>;
};
const columns: GridColDef[] = [
    {
        field: 'id',
        headerName: 'Name',
        width: 200,
        renderCell: (params: GridRenderCellParams) => {
            const value = params.value;
            return (
                <div style={{wordBreak: 'break-all'}}>
                    <Tooltip title="Copy">
                        <IconButton size="small" onClick={() => clipboardCopy(value)}>
                            <ContentCopy fontSize="small" />
                        </IconButton>
                    </Tooltip>
                    <Tooltip title="Examine as a container entry">
                        <IconButton size="small" href={'/inspector/container/view?class=' + value}>
                            <OpenInNew fontSize="small" />
                        </IconButton>
                    </Tooltip>
                    {value}
                </div>
            );
        },
    },
    {
        field: 'value',
        headerName: 'Value',
        flex: 1,
        renderCell: (params: GridRenderCellParams) => <TempComponent {...params} />,
    },
];

export const ContainerPage = () => {
    const {data, isLoading} = useGetClassesQuery('');
    const [lazyLoadObject] = useLazyGetObjectQuery();
    const [searchParams, setSearchParams] = useSearchParams();
    const searchString = searchParams.get('filter') || '';

    console.log('data', data, isLoading);

    const {objects, setObjects, insertObject} = useContext(DataContext);

    const handleLoadObject = useCallback(async (id: string) => {
        const result = await lazyLoadObject(id);
        if (result.data) {
            insertObject(id, result.data.object);
        }
    }, []);

    useEffect(() => {
        if (!isLoading && data) {
            console.log(
                'setObjects',
                data.map((row) => ({id: row, value: null})),
            );
            setObjects(data.map((row) => ({id: row, value: null})));
        }
    }, [isLoading]);

    const filteredRows: any = useMemo(() => {
        if (!searchString) {
            return objects;
        }
        const regExp = new RegExp(regexpQuote(searchString), 'i');
        return objects.filter((object: any) => object.id.match(regExp));
    }, [objects, searchString]);
    console.log('filteredRows', filteredRows, objects);

    useBreadcrumbs(() => ['Inspector', 'Container']);

    const onChangeHandler = useCallback(async (value: string) => {
        setSearchParams({filter: value});
    }, []);

    if (isLoading) {
        return <FullScreenCircularProgress />;
    }

    return (
        <>
            <FilterInput value={searchString} onChange={onChangeHandler} />
            <LoaderContextProvider loader={handleLoadObject}>
                <DataTable rows={filteredRows as GridValidRowModel[]} getRowId={(row) => row.id} columns={columns} />
            </LoaderContextProvider>
        </>
    );
};
