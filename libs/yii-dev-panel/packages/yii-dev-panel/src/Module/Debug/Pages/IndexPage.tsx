import {GridColDef, GridRenderCellParams, GridValidRowModel} from '@mui/x-data-grid';
import {useDebugEntry} from '@yiisoft/yii-dev-panel-sdk/API/Debug/Context';
import {DataTable} from '@yiisoft/yii-dev-panel-sdk/Component/Grid';
import {JsonRenderer} from '@yiisoft/yii-dev-panel/Module/Debug/Component/JsonRenderer';

const columns: GridColDef[] = [
    {field: '0', headerName: 'Name', width: 130},
    {
        field: '1',
        headerName: 'Value',
        flex: 1,
        renderCell: (params: GridRenderCellParams) => <JsonRenderer key={params.id} value={params.value} />,
    },
];

export const IndexPage = () => {
    const data = useDebugEntry();

    const isArray = Array.isArray(data);
    let rows = Object.entries(data || ([] as any));
    rows = rows.map((el) => ({0: el[0], 1: isArray ? Object.assign({}, el[1]) : el[1]})) as any;

    return (
        <div style={{height: 400, width: '100%'}}>
            <DataTable rows={rows as GridValidRowModel[]} getRowId={(row) => row[0]} columns={columns} />
        </div>
    );
};
