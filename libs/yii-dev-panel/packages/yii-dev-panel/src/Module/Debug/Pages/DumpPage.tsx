import {GridColDef, GridValidRowModel} from '@mui/x-data-grid';
import {DataTable} from '@yiisoft/yii-dev-panel-sdk/Component/Grid';
import {JsonRenderer} from '@yiisoft/yii-dev-panel/Module/Debug/Component/JsonRenderer';
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

export const DumpPage = ({data}: any) => {
    const isArray = Array.isArray(data);
    const rows = useMemo(() => {
        const rows = Object.entries(data || []);
        return rows.map((el) => ({
            0: el[0],
            1: Array.isArray(el[1]) ? Object.assign({}, el[1]) : el[1],
        })) as any;
    }, [data]);

    return (
        <DataTable
            getRowId={() => Math.random() * 1000}
            rows={rows as GridValidRowModel[]}
            // @ts-ignore
            columns={isArray ? [[...columns].pop()] : columns}
        />
    );
};
