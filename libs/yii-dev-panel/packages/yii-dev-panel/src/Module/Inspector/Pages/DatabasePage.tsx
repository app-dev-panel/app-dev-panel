import {Button, Typography} from '@mui/material';
import {GridColDef, GridRenderCellParams, GridValidRowModel} from '@mui/x-data-grid';
import {FullScreenCircularProgress} from '@yiisoft/yii-dev-panel-sdk/Component/FullScreenCircularProgress';
import {DataTable} from '@yiisoft/yii-dev-panel-sdk/Component/Grid';
import {useGetTableQuery} from '@yiisoft/yii-dev-panel/Module/Inspector/API/Inspector';
import {useEffect, useState} from 'react';
import {useBreadcrumbs} from '@yiisoft/yii-dev-panel/Application/Context/BreadcrumbsContext';

const columns: GridColDef[] = [
    {
        field: 'name',
        headerName: 'Name',
        width: 200,
        renderCell: (params: GridRenderCellParams) => (
            <Typography my={1} sx={{wordBreak: 'break-all'}}>
                {params.value}
            </Typography>
        ),
    },
    {
        field: 'columns',
        headerName: 'Columns count',
        flex: 1,
        renderCell: (params: GridRenderCellParams) => {
            return <Typography my={1}>{params.value}</Typography>;
        },
    },
    {
        field: 'records',
        headerName: 'Records count',
        flex: 1,
        renderCell: (params: GridRenderCellParams) => {
            return <Typography my={1}>{params.value}</Typography>;
        },
    },
    {
        field: 'actions',
        headerName: 'Actions',
        flex: 1,
        renderCell: (params: GridRenderCellParams) => {
            return (
                <Typography my={1}>
                    <Button variant="contained" href={`/inspector/database/${params.row.name}`}>
                        View
                    </Button>
                </Typography>
            );
        },
    },
];

export const DatabasePage = () => {
    const {data, isLoading} = useGetTableQuery();
    const [tables, setTables] = useState<GridValidRowModel[]>([]);

    useEffect(() => {
        if (data) {
            const tables = [];
            // @ts-ignore
            for (const table of data) {
                tables.push({
                    name: table.table,
                    columns: table.columns.length,
                    records: table.records,
                });
            }
            setTables(tables);
        }
    }, [isLoading]);

    useBreadcrumbs(() => ['Inspector', 'Database']);

    if (isLoading) {
        return <FullScreenCircularProgress />;
    }
    return (
        <>
            <h2>Database</h2>
            <DataTable
                rows={tables as GridValidRowModel[]}
                getRowId={(row) => row.name}
                columns={columns}
                // rowHeight={30}
            />
        </>
    );
};
