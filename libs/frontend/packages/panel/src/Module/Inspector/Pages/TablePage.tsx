import {useBreadcrumbs} from '@app-dev-panel/panel/Application/Context/BreadcrumbsContext';
import {useGetTableDataQuery} from '@app-dev-panel/panel/Module/Inspector/API/Inspector';
import {setPreferredPageSize} from '@app-dev-panel/sdk/API/Application/ApplicationContext';
import {FullScreenCircularProgress} from '@app-dev-panel/sdk/Component/FullScreenCircularProgress';
import {PageHeader} from '@app-dev-panel/sdk/Component/PageHeader';
import {DataGrid, GridColDef, GridRenderCellParams, GridValidRowModel} from '@mui/x-data-grid';
import {useCallback, useMemo, useState} from 'react';
import {useDispatch, useSelector} from 'react-redux';
import {useParams, useSearchParams} from 'react-router-dom';

const rowsPerPageOptions = [20, 50, 100];

export const TablePage = () => {
    const {table} = useParams();
    const dispatch = useDispatch();
    // @ts-ignore
    const preferredPageSize = useSelector((state) => state.application.preferredPageSize) as number;

    const [searchParams, setSearchParams] = useSearchParams({page: '0'});
    const [pageSize, setPageSize] = useState(preferredPageSize || rowsPerPageOptions[0]);
    const page = Number(searchParams.get('page') || '0');

    const {data, isLoading, isFetching} = useGetTableDataQuery(
        {table: table!, limit: pageSize, offset: page * pageSize},
        {skip: !table},
    );

    const columns = useMemo<GridColDef[]>(() => {
        if (!data?.columns) return [];
        return data.columns.map((column) => ({
            field: column.name,
            headerName: column.name,
            flex: 1,
            renderCell: (params: GridRenderCellParams) => (
                <span style={{wordBreak: 'break-all', maxHeight: '100px', overflowY: 'hidden'}}>{params.value}</span>
            ),
        }));
    }, [data?.columns]);

    const primaryKey = data?.primaryKeys?.[0];
    const getRowIdCallback = useCallback((row: any) => row[primaryKey ?? 'id'], [primaryKey]);

    const handlePageChange = useCallback(
        (newPage: number) => {
            setSearchParams({page: String(newPage)});
        },
        [setSearchParams],
    );

    const handlePageSizeChange = useCallback(
        (newPageSize: number) => {
            setPageSize(newPageSize);
            dispatch(setPreferredPageSize(newPageSize));
            setSearchParams({page: '0'});
        },
        [dispatch, setSearchParams],
    );

    useBreadcrumbs(() => ['Inspector', 'Database', table]);

    if (isLoading) {
        return <FullScreenCircularProgress />;
    }

    return (
        <>
            <PageHeader title="Tables" icon="table_chart" description="Database table viewer" />
            <DataGrid
                rows={(data?.records ?? []) as GridValidRowModel[]}
                columns={columns}
                getRowId={getRowIdCallback}
                loading={isFetching}
                paginationMode="server"
                rowCount={data?.totalCount ?? 0}
                page={page}
                pageSize={pageSize}
                rowsPerPageOptions={rowsPerPageOptions}
                onPageChange={handlePageChange}
                onPageSizeChange={handlePageSizeChange}
                autoHeight
                disableSelectionOnClick
                disableDensitySelector
                disableColumnSelector
                hideFooterSelectedRowCount
                getRowHeight={() => 'auto'}
                sx={{'& .MuiDataGrid-cell': {alignItems: 'flex-start', flexDirection: 'column'}}}
            />
        </>
    );
};
