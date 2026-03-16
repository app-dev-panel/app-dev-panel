import {useBreadcrumbs} from '@app-dev-panel/panel/Application/Context/BreadcrumbsContext';
import {useGetTableQuery} from '@app-dev-panel/panel/Module/Inspector/API/Inspector';
import {FullScreenCircularProgress} from '@app-dev-panel/sdk/Component/FullScreenCircularProgress';
import {DataTable} from '@app-dev-panel/sdk/Component/Grid';
import {PageHeader} from '@app-dev-panel/sdk/Component/PageHeader';
import {GridColDef, GridRenderCellParams, GridValidRowModel} from '@mui/x-data-grid';
import {useCallback, useEffect, useState} from 'react';
import {useParams} from 'react-router-dom';

export const TablePage = () => {
    const {table} = useParams();
    const {data, isLoading} = useGetTableQuery(table);
    const [primaryKey, setPrimaryKey] = useState<string>('');
    const [columns, setColumns] = useState<GridColDef[]>([]);
    const [records, setRecords] = useState<GridValidRowModel[]>([]);

    useEffect(() => {
        if (data) {
            const columns = [];
            console.log(data);
            // @ts-ignore
            for (const column of data.columns) {
                console.log('column', column);
                columns.push({
                    field: column.name,
                    headerName: column.name,
                    flex: 1,
                    renderCell: (params: GridRenderCellParams) => (
                        <span style={{wordBreak: 'break-all', maxHeight: '100px', overflowY: 'hidden'}}>
                            {params.value}
                        </span>
                    ),
                });
            }
            // @ts-ignore
            setPrimaryKey(data.primaryKeys[0]);
            // @ts-ignore
            setRecords(data.records);
            setColumns(columns);
        }
    }, [isLoading]);

    const getRowIdCallback = useCallback((row: any) => row[primaryKey], [primaryKey]);

    useBreadcrumbs(() => ['Inspector', 'Database', table]);

    if (isLoading) {
        return <FullScreenCircularProgress />;
    }

    return (
        <>
            <PageHeader title="Tables" icon="table_chart" description="Database table viewer" />
            <DataTable rows={records as GridValidRowModel[]} getRowId={getRowIdCallback} columns={columns} />
        </>
    );
};
