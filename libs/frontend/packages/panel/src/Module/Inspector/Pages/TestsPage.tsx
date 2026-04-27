import {
    type CommandType,
    useLazyGetCommandsQuery,
    useRunCommandMutation,
} from '@app-dev-panel/panel/Module/Inspector/API/Inspector';
import {
    CommandButton,
    type CommandRunStatus,
} from '@app-dev-panel/panel/Module/Inspector/Component/Command/CommandButton';
import {CommandErrorAlert} from '@app-dev-panel/panel/Module/Inspector/Component/Command/CommandErrorAlert';
import {extractCommandError} from '@app-dev-panel/panel/Module/Inspector/Component/Command/extractCommandError';
import {ResultDialog} from '@app-dev-panel/panel/Module/Inspector/Component/Command/ResultDialog';
import {EmptyState} from '@app-dev-panel/sdk/Component/EmptyState';
import {FileLink} from '@app-dev-panel/sdk/Component/FileLink';
import {DataTable} from '@app-dev-panel/sdk/Component/Grid';
import {PageHeader} from '@app-dev-panel/sdk/Component/PageHeader';
import {ContentCopy} from '@mui/icons-material';
import CheckIcon from '@mui/icons-material/Check';
import CloseIcon from '@mui/icons-material/Close';
import {Box, IconButton, styled, Tooltip} from '@mui/material';
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
                <EmptyState
                    icon="science"
                    title="No test commands available"
                    description="Install a test runner (PHPUnit, Codeception, Pest) to enable this feature."
                />
            </>
        );
    }

    const buttonStatus = (command: CommandType): CommandRunStatus => {
        if (commandQueryInfo.isLoading && activeCommand?.name === command.name) return 'loading';
        if (activeCommand?.name === command.name && commandResponse !== null) {
            return commandResponse.isSuccessful ? 'success' : 'error';
        }
        return 'idle';
    };

    return (
        <>
            {showHeader && <PageHeader title="Tests" icon="science" description="Run and inspect test results" />}
            <Box sx={{display: 'flex', flexWrap: 'wrap', gap: 1.5, mb: 2}}>
                {availableCommands.map((command) => (
                    <CommandButton
                        key={command.name}
                        title={command.title}
                        description={command.description}
                        group={command.group}
                        status={buttonStatus(command)}
                        disabled={commandQueryInfo.isLoading && activeCommand?.name !== command.name}
                        onClick={() => runCommand(command)}
                    />
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
