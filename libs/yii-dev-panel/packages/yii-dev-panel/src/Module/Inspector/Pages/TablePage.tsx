import {GridColDef, GridRenderCellParams, GridValidRowModel} from '@mui/x-data-grid';
import {FullScreenCircularProgress} from '@yiisoft/yii-dev-panel-sdk/Component/FullScreenCircularProgress';
import {DataTable} from '@yiisoft/yii-dev-panel-sdk/Component/Grid';
import {useGetTableQuery} from '@yiisoft/yii-dev-panel/Module/Inspector/API/Inspector';
import {useCallback, useEffect, useState} from 'react';
import {useParams} from 'react-router-dom';
import {useBreadcrumbs} from '@yiisoft/yii-dev-panel/Application/Context/BreadcrumbsContext';

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
            <h2>Tables</h2>
            <DataTable rows={records as GridValidRowModel[]} getRowId={getRowIdCallback} columns={columns} />
        </>
    );
};
