import {JsonRenderer} from '@app-dev-panel/panel/Module/Debug/Component/JsonRenderer';
import {DataTable} from '@app-dev-panel/sdk/Component/Grid';
import {GridColDef, GridValidRowModel} from '@mui/x-data-grid';
import {useMemo} from 'react';

const columns: GridColDef[] = [
    {field: '0', headerName: 'Name', width: 130},
    {
        field: '1',
        headerName: 'Value',
        flex: 1,
        renderCell: (params) => {
            return <JsonRenderer key={params.id} value={params.value} />;
        },
    },
];

export const DumpPage = ({data}: {data: unknown}) => {
    const isArray = Array.isArray(data);
    const rows = useMemo(() => {
        const entries = Object.entries(data || []);
        return entries.map((el) => ({
            0: el[0],
            1: Array.isArray(el[1]) ? Object.assign({}, el[1]) : el[1],
        })) as GridValidRowModel[];
    }, [data]);

    const displayColumns = useMemo(() => (isArray ? [columns[columns.length - 1]] : columns), [isArray]);

    return (
        <DataTable
            getRowId={(row: GridValidRowModel) => String(row[0])}
            rows={rows}
            columns={displayColumns as GridColDef[]}
        />
    );
};
