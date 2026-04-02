import {setPreferredPageSize} from '@app-dev-panel/sdk/API/Application/ApplicationContext';
import {DataGrid, GridColDef, GridValidRowModel} from '@mui/x-data-grid';
import {useCallback, useState} from 'react';
import {useDispatch, useSelector} from 'react-redux';
import {useSearchParams} from 'react-router';

type GridSortModel = readonly {field: string; sort: 'asc' | 'desc' | null | undefined}[];

type GridProps = {
    rows: GridValidRowModel[];
    columns: GridColDef[];
    rowsPerPage?: number[];
    getRowId?: (row: GridValidRowModel) => string | number;
    pageSize?: number;
    rowHeight?: number | 'auto';
    sortModel?: GridSortModel;
    /** Override the initial page size instead of reading from Redux. */
    initialPageSize?: number;
    /** Called when the user changes the page size. When omitted, the component persists the value to Redux. */
    onPageSizeChange?: (size: number) => void;
};
const defaultRowsPerPage = [20, 50, 100];
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
        initialPageSize,
        onPageSizeChange,
    } = props;

    const dispatch = useDispatch();
    const preferredPageSize = useSelector(
        (state: {application: {preferredPageSize: number}}) => state.application.preferredPageSize,
    );

    const [searchParams, setSearchParams] = useSearchParams({page: '0'});
    const [pageSize, setPageSize] = useState((initialPageSize ?? preferredPageSize) || Math.min(...rowsPerPage));

    const getRowHeightCallback = useCallback(() => rowHeight, [rowHeight]);
    const handlePageChange = useCallback((page: number) => setSearchParams({page: String(page)}), [setSearchParams]);
    const handlePageSizeChange = useCallback(
        (value: number) => {
            setPageSize(value);
            if (onPageSizeChange) {
                onPageSizeChange(value);
            } else {
                dispatch(setPreferredPageSize(value));
            }
        },
        [dispatch, onPageSizeChange],
    );

    return (
        <DataGrid
            disableDensitySelector
            disableColumnSelector
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
