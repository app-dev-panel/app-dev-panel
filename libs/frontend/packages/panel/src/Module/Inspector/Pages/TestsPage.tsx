import {
    type CommandType,
    useLazyGetCommandsQuery,
    useRunCommandMutation,
} from '@app-dev-panel/panel/Module/Inspector/API/Inspector';
import {CommandErrorAlert} from '@app-dev-panel/panel/Module/Inspector/Component/Command/CommandErrorAlert';
import {extractCommandError} from '@app-dev-panel/panel/Module/Inspector/Component/Command/extractCommandError';
import {ResultDialog} from '@app-dev-panel/panel/Module/Inspector/Component/Command/ResultDialog';
import {FileLink} from '@app-dev-panel/sdk/Component/FileLink';
import {DataTable} from '@app-dev-panel/sdk/Component/Grid';
import {PageHeader} from '@app-dev-panel/sdk/Component/PageHeader';
import {Check, ContentCopy, Error} from '@mui/icons-material';
import CheckIcon from '@mui/icons-material/Check';
import CloseIcon from '@mui/icons-material/Close';
import {Box, Button, CircularProgress, IconButton, styled, Tooltip, Typography} from '@mui/material';
import {GridColDef, GridRenderCellParams, GridValidRowModel} from '@mui/x-data-grid';
import clipboardCopy from 'clipboard-copy';
import {useCallback, useEffect, useState} from 'react';

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
                <FileLink path={params.row.path}>{params.value}</FileLink>
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
        renderCell: (params: GridRenderCellParams) => (
            <span key={params.id}>{params.value ? JSON.stringify(params.value) : ''}</span>
        ),
    },
];

type CommandState = {isSuccessful: boolean | undefined; errors: string[]};
export const TestsPage = ({showHeader = true}: {showHeader?: boolean}) => {
    const [getCommandsQuery] = useLazyGetCommandsQuery();
    const [commandQuery, commandQueryInfo] = useRunCommandMutation();
    const [availableCommands, setAvailableCommands] = useState<CommandType[]>([]);
    const [activeCommand, setActiveCommand] = useState<CommandType | null>(null);
    const [rows, setRows] = useState<any[]>([]);
    const [commandResponse, setCommandResponse] = useState<CommandState | null>(null);
    const [commandError, setCommandError] = useState<string[] | null>(null);
    const [showResultDialog, setShowResultDialog] = useState(false);
    const [dialogResult, setDialogResult] = useState<{status: string; result: any; errors?: string[]} | null>(null);

    useEffect(() => {
        void (async () => {
            const response = await getCommandsQuery();
            if (response.data) {
                setAvailableCommands(response.data.filter((cmd) => cmd.group === 'test'));
            }
        })();
    }, []);

    const runCommand = async (command: CommandType) => {
        setActiveCommand(command);
        setCommandError(null);
        setCommandResponse(null);
        setRows([]);
        const data = await commandQuery(command.name);

        const error = extractCommandError(data);
        if (!('data' in data) || typeof data.data !== 'object') {
            setCommandError(error?.errors ?? ['An unexpected error occurred']);
            return;
        }

        const result = data.data.result;
        if (Array.isArray(result)) {
            let id = 0;
            const resultRows = [];
            for (const event of result) {
                const testName = [event.suite]
                    .concat(event.test)
                    .filter((v: any) => !!v)
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
            setRows(resultRows);
            setCommandResponse({isSuccessful: data.data.status === 'ok', errors: data.data.errors});
        } else {
            setDialogResult({status: data.data.status, result, errors: data.data.errors});
            setShowResultDialog(true);
            setCommandResponse({isSuccessful: data.data.status === 'ok', errors: data.data.errors});
        }

        if (error) {
            setCommandError(error.errors);
        }
    };

    const getRowIdCallback = useCallback((row: any) => row.id, []);

    if (availableCommands.length === 0) {
        return (
            <>
                {showHeader && <PageHeader title="Tests" icon="science" description="Run and inspect test results" />}
                <Typography sx={{color: 'text.secondary', mt: 2}}>
                    No test commands available. Install a test runner (PHPUnit, Codeception, Pest) to enable this
                    feature.
                </Typography>
            </>
        );
    }

    return (
        <>
            {showHeader && <PageHeader title="Tests" icon="science" description="Run and inspect test results" />}
            <Box display="flex" alignItems="center" gap={1}>
                {availableCommands.map((command) => (
                    <Box key={command.name} display="flex" alignItems="center">
                        <Button
                            onClick={() => runCommand(command)}
                            color={
                                activeCommand?.name === command.name && commandResponse !== null
                                    ? commandResponse.isSuccessful
                                        ? 'success'
                                        : 'error'
                                    : 'primary'
                            }
                            disabled={commandQueryInfo.isLoading}
                            endIcon={
                                commandQueryInfo.isLoading && activeCommand?.name === command.name ? (
                                    <CircularProgress size={24} color="info" />
                                ) : null
                            }
                        >
                            Run {command.title}
                        </Button>
                        {!commandQueryInfo.isLoading &&
                            activeCommand?.name === command.name &&
                            commandResponse !== null && (
                                <>
                                    {commandResponse.isSuccessful === true && <Check color="success" />}
                                    {commandResponse.isSuccessful === false && <Error color="error" />}
                                </>
                            )}
                    </Box>
                ))}
            </Box>
            {commandError && (
                <CommandErrorAlert
                    errors={commandError}
                    onRetry={activeCommand ? () => runCommand(activeCommand) : undefined}
                    onDismiss={() => setCommandError(null)}
                />
            )}
            {rows.length > 0 && (
                <DataTable rows={rows as GridValidRowModel[]} getRowId={getRowIdCallback} columns={columns} />
            )}
            {dialogResult && (
                <ResultDialog
                    open={showResultDialog}
                    status={dialogResult.status as 'ok' | 'error' | 'fail'}
                    content={dialogResult.result}
                    errors={dialogResult.errors}
                    onRerun={() => activeCommand && runCommand(activeCommand)}
                    onClose={() => setShowResultDialog(false)}
                />
            )}
        </>
    );
};
