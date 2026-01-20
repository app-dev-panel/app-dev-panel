import {DataGrid, GridColumns, GridValidRowModel} from '@mui/x-data-grid';
import {GridSortModel} from '@mui/x-data-grid/models/gridSortModel';
import {setPreferredPageSize} from '@yiisoft/yii-dev-panel-sdk/API/Application/ApplicationContext';
import {useCallback, useState} from 'react';
import {useDispatch, useSelector} from 'react-redux';
import {useSearchParams} from 'react-router-dom';

type GridProps = {
    rows: GridValidRowModel[];
    columns: GridColumns;
    rowsPerPage?: number[];
    getRowId?: (row: any) => string | number;
    pageSize?: number;
    rowHeight?: number | 'auto';
    sortModel?: GridSortModel;
};
const defaultRowsPerPage = [20, 50, 100];
const voidCallback = () => null;
const defaultStyle = {
    '& .MuiDataGrid-cell': {
        alignItems: 'flex-start',
        flexDirection: 'column',
    },
};

export function DataTable(props: GridProps) {
    const {
        rows,
        sortModel,
        columns,
        rowHeight = 'auto',
        getRowId = useCallback((row) => row.id, []),
        rowsPerPage = defaultRowsPerPage,
    } = props;

    const dispatch = useDispatch();
    // @ts-ignore
    const preferredPageSize = useSelector((state) => state.application.preferredPageSize) as number;

    const [searchParams, setSearchParams] = useSearchParams({page: '0'});
    const [pageSize, setPageSize] = useState(preferredPageSize || Math.min(...rowsPerPage));

    const getRowHeightCallback = useCallback(() => rowHeight, [rowHeight]);

    return (
        <DataGrid
            onCellClick={voidCallback}
            onCellDoubleClick={voidCallback}
            onCellFocusOut={voidCallback}
            onRowClick={voidCallback}
            onColumnHeaderClick={voidCallback}
            disableDensitySelector
            disableColumnSelector
            disableVirtualization
            disableSelectionOnClick
            sortModel={sortModel}
            rows={rows}
            getRowId={getRowId}
            columns={columns}
            rowsPerPageOptions={rowsPerPage}
            pageSize={pageSize}
            page={Number(searchParams.get('page'))}
            onPageChange={useCallback((page) => {
                setSearchParams({page: String(page)});
            }, [])}
            onPageSizeChange={useCallback((value) => {
                setPageSize(value);
                dispatch(setPreferredPageSize(value));
            }, [])}
            rowBuffer={0}
            rowThreshold={0}
            hideFooterSelectedRowCount
            autoHeight
            sx={defaultStyle}
            getRowHeight={getRowHeightCallback}
        />
    );
}
