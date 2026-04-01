import {setPreferredPageSize} from '@app-dev-panel/sdk/API/Application/ApplicationContext';
import {DataGrid, GridColDef, GridValidRowModel} from '@mui/x-data-grid';
import {GridSortModel} from '@mui/x-data-grid/models/gridSortModel';
import {useCallback, useState} from 'react';
import {useDispatch, useSelector} from 'react-redux';
import {useSearchParams} from 'react-router';

type GridProps = {
    rows: GridValidRowModel[];
    columns: GridColDef[];
    rowsPerPage?: number[];
    getRowId?: (row: GridValidRowModel) => string | number;
    pageSize?: number;
    rowHeight?: number | 'auto';
    sortModel?: GridSortModel;
};
const defaultRowsPerPage = [20, 50, 100];
const voidCallback = () => {};
const defaultStyle = {'& .MuiDataGrid-cell': {alignItems: 'flex-start', flexDirection: 'column'}};
const defaultGetRowId = (row: GridValidRowModel) => row.id as string | number;

export function DataTable(props: GridProps) {
    const {
        rows,
        sortModel,
        columns,
        rowHeight = 'auto',
        getRowId = defaultGetRowId,
        rowsPerPage = defaultRowsPerPage,
    } = props;

    const dispatch = useDispatch();
    const preferredPageSize = useSelector(
        (state: {application: {preferredPageSize: number}}) => state.application.preferredPageSize,
    );

    const [searchParams, setSearchParams] = useSearchParams({page: '0'});
    const [pageSize, setPageSize] = useState(preferredPageSize || Math.min(...rowsPerPage));

    const getRowHeightCallback = useCallback(() => rowHeight, [rowHeight]);
    const handlePageChange = useCallback((page: number) => setSearchParams({page: String(page)}), [setSearchParams]);
    const handlePageSizeChange = useCallback(
        (value: number) => {
            setPageSize(value);
            dispatch(setPreferredPageSize(value));
        },
        [dispatch],
    );

    return (
        <DataGrid
            disableDensitySelector
            disableColumnSelector
            disableVirtualization
            disableRowSelectionOnClick
            sortModel={sortModel}
            rows={rows}
            getRowId={getRowId}
            columns={columns}
            pageSizeOptions={rowsPerPage}
            paginationModel={{page: Number(searchParams.get('page')), pageSize}}
            onPaginationModelChange={(model) => {
                if (model.page !== Number(searchParams.get('page'))) handlePageChange(model.page);
                if (model.pageSize !== pageSize) handlePageSizeChange(model.pageSize);
            }}
            hideFooterSelectedRowCount
            autoHeight
            sx={defaultStyle}
            getRowHeight={getRowHeightCallback}
        />
    );
}
