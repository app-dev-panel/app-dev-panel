import {Check, ContentCopy, Error, FilePresent} from '@mui/icons-material';
import CheckIcon from '@mui/icons-material/Check';
import CloseIcon from '@mui/icons-material/Close';
import {Button, CircularProgress, IconButton, styled, Tooltip} from '@mui/material';
import Box from '@mui/material/Box';
import {GridColDef, GridColumns, GridRenderCellParams, GridValidRowModel} from '@mui/x-data-grid';
import {DataTable} from '@yiisoft/yii-dev-panel-sdk/Component/Grid';
import {JsonRenderer} from '@yiisoft/yii-dev-panel-sdk/Component/JsonRenderer';
import {parseFilePathWithLineAnchor} from '@yiisoft/yii-dev-panel-sdk/Helper/filePathParser';
import {useRunCommandMutation} from '@yiisoft/yii-dev-panel/Module/Inspector/API/Inspector';
import clipboardCopy from 'clipboard-copy';
import {useCallback, useState} from 'react';
import {useBreadcrumbs} from '@yiisoft/yii-dev-panel/Application/Context/BreadcrumbsContext';

const CenteredBox = styled(Box)({
    height: '100%',
    width: '100%',
    display: 'flex',
    alignItems: 'center',
    justifyContent: 'center',
});

const columns: GridColDef[] = [
    {
        field: 'name',
        headerName: 'Name',
        width: 200,
        renderCell: (params: GridRenderCellParams) => (
            <span key={params.id} style={{wordBreak: 'break-all'}}>
                <Tooltip title="Copy">
                    <IconButton size="small" onClick={() => clipboardCopy(params.row.path)}>
                        <ContentCopy fontSize="small" />
                    </IconButton>
                </Tooltip>
                <Tooltip title="Examine as a file in new window">
                    <IconButton
                        size="small"
                        href={'/inspector/files?path=' + parseFilePathWithLineAnchor(params.row.path)}
                    >
                        <FilePresent fontSize="small" />
                    </IconButton>
                </Tooltip>
                {params.value}
            </span>
        ),
    },
    {
        field: 'status',
        headerName: 'Status',
        width: 80,
        renderCell: (params: GridRenderCellParams) => (
            <CenteredBox key={params.id}>
                {params.value === 'ok' ? <CheckIcon color="success" /> : <CloseIcon color="error" />}
            </CenteredBox>
        ),
    },
    {
        field: 'time',
        headerName: 'Time (ms)',
        width: 100,
        renderCell: (params: GridRenderCellParams) => (
            <CenteredBox key={params.id}>{params.value?.toFixed(2)}</CenteredBox>
        ),
    },
    {
        field: 'stacktrace',
        headerName: 'Stacktrace',
        flex: 1,
        renderCell: (params: GridRenderCellParams) => <JsonRenderer key={params.id} value={params.value} depth={0} />,
    },
];

type CommandResponseType = {
    isSuccessful: boolean | undefined;
    errors: string[];
};
export const TestsPage = () => {
    const [commandQuery, commandQueryInfo] = useRunCommandMutation();
    const [rows, setRows] = useState<any[]>([]);
    const [commandResponse, setCommandResponse] = useState<CommandResponseType | null>(null);

    async function runCodeceptionHandler() {
        const data = await commandQuery('test/codeception');
        if (!('data' in data) || typeof data.data !== 'object') {
            console.error(data);
            return;
        }

        let id = 0;
        const resultRows = [];
        for (const event of data.data.result) {
            const testName = [event.suite]
                .concat(event.test)
                .filter((v) => !!v)
                .join('::');

            id++;
            resultRows.push({
                id,
                name: testName,
                status: event.status,
                stacktrace: event.stacktrace,
                path: event.file,
                time: event.time,
            });
        }
        setCommandResponse({
            isSuccessful: data.data.status === 'ok',
            errors: data.data.errors,
        });
        setRows(resultRows);
    }

    const getRowIdCallback = useCallback((row: any) => row.id, []);

    useBreadcrumbs(() => ['Inspector', 'Tests']);

    return (
        <>
            <h2>{'Tests'}</h2>
            <Box display="flex" alignItems="center">
                <Button
                    onClick={runCodeceptionHandler}
                    color={commandResponse === null ? 'primary' : commandResponse.isSuccessful ? 'success' : 'error'}
                    disabled={commandQueryInfo.isLoading}
                    endIcon={commandQueryInfo.isLoading ? <CircularProgress size={24} color="info" /> : null}
                >
                    Run Codeception
                </Button>
                {!commandQueryInfo.isLoading && commandResponse && (
                    <>
                        {commandResponse.isSuccessful === true && <Check color="success" />}
                        {commandResponse.isSuccessful === false && <Error color="error" />}
                    </>
                )}
            </Box>

            {commandQueryInfo.isSuccess && (
                <DataTable
                    rows={rows as GridValidRowModel[]}
                    getRowId={getRowIdCallback}
                    columns={columns as GridColumns}
                />
            )}
        </>
    );
};
