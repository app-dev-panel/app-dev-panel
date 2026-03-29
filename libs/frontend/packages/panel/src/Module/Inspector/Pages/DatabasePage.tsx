import {useGetTableQuery} from '@app-dev-panel/panel/Module/Inspector/API/Inspector';
import {FullScreenCircularProgress} from '@app-dev-panel/sdk/Component/FullScreenCircularProgress';
import {DataTable} from '@app-dev-panel/sdk/Component/Grid';
import {PageHeader} from '@app-dev-panel/sdk/Component/PageHeader';
import {Button, Typography} from '@mui/material';
import {GridColDef, GridRenderCellParams, GridValidRowModel} from '@mui/x-data-grid';
import {useEffect, useState} from 'react';

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
        if (data && Array.isArray(data)) {
            const rows = [];
            for (const table of data as {table: string; columns: unknown[]; records: number}[]) {
                rows.push({name: table.table, columns: table.columns.length, records: table.records});
            }
            setTables(rows);
        }
    }, [data]);

    if (isLoading) {
        return <FullScreenCircularProgress />;
    }
    return (
        <>
            <PageHeader title="Database" icon="storage" description="Browse database tables and records" />
            <DataTable
                rows={tables as GridValidRowModel[]}
                getRowId={(row) => row.name}
                columns={columns}
                // rowHeight={30}
            />
        </>
    );
};
