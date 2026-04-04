import {
    type CommandType,
    useLazyGetCommandsQuery,
    useRunCommandMutation,
} from '@app-dev-panel/panel/Module/Inspector/API/Inspector';
import {CommandErrorAlert} from '@app-dev-panel/panel/Module/Inspector/Component/Command/CommandErrorAlert';
import {extractCommandError} from '@app-dev-panel/panel/Module/Inspector/Component/Command/extractCommandError';
import {ResultDialog} from '@app-dev-panel/panel/Module/Inspector/Component/Command/ResultDialog';
import {EmptyState} from '@app-dev-panel/sdk/Component/EmptyState';
import {FileLink} from '@app-dev-panel/sdk/Component/FileLink';
import {DataTable} from '@app-dev-panel/sdk/Component/Grid';
import {PageHeader} from '@app-dev-panel/sdk/Component/PageHeader';
import {Check, ContentCopy, Error} from '@mui/icons-material';
import ExpandMoreIcon from '@mui/icons-material/ExpandMore';
import {
    Accordion,
    AccordionDetails,
    AccordionSummary,
    Box,
    Button,
    CircularProgress,
    IconButton,
    Link,
    Tooltip,
    Typography,
} from '@mui/material';
import {GridColDef, GridRenderCellParams, GridValidRowModel} from '@mui/x-data-grid';
import clipboardCopy from 'clipboard-copy';
import * as React from 'react';
import {useEffect, useState} from 'react';

const columns: GridColDef[] = [
    {
        field: 'file_name',
        headerName: 'File',
        width: 200,
        renderCell: (params: GridRenderCellParams) => {
            let filePath = params.value + ':' + params.row.line_from;

            if (params.row.line_from !== params.row.line_to) {
                filePath += '-' + params.row.line_to;
            }

            return (
                <span style={{wordBreak: 'break-all'}}>
                    <Tooltip title="Copy">
                        <IconButton size="small" onClick={() => clipboardCopy(filePath)}>
                            <ContentCopy fontSize="small" />
                        </IconButton>
                    </Tooltip>
                    <FileLink path={filePath}>{filePath}</FileLink>
                </span>
            );
        },
    },
    {
        field: 'message',
        headerName: 'Message',
        flex: 1,
        renderCell: (params: GridRenderCellParams) => {
            return (
                <>
                    <b>
                        <Link href={params.row.link}>{params.row.type}</Link>
                    </b>
                    {params.row.message}
                </>
            );
        },
    },
];

function renderGrid(data: AnalyseRow[]) {
    return <DataTable rows={data as GridValidRowModel[]} columns={columns} />;
}

type AnalyseRow = {
    id: number;
    file_name: string;
    file_path: string;
    line_from: string;
    line_to: string;
    type: string;
    message: string;
    link: string;
};
type CommandState = {isSuccessful: boolean | undefined; errors: string[]};
export const AnalysePage = ({showHeader = true}: {showHeader?: boolean}) => {
    const [getCommandsQuery] = useLazyGetCommandsQuery();
    const [commandQuery, commandQueryInfo] = useRunCommandMutation();
    const [availableCommands, setAvailableCommands] = useState<CommandType[]>([]);
    const [activeCommand, setActiveCommand] = useState<CommandType | null>(null);
    const [errorRows, setErrorRows] = useState<AnalyseRow[]>([]);
    const [infoRows, setInfoRows] = useState<AnalyseRow[]>([]);
    const [commandResponse, setCommandResponse] = useState<CommandState | null>(null);
    const [commandError, setCommandError] = useState<string[] | null>(null);
    const [showResultDialog, setShowResultDialog] = useState(false);
    const [dialogResult, setDialogResult] = useState<{status: string; result: any; errors?: string[]} | null>(null);

    useEffect(() => {
        void (async () => {
            const response = await getCommandsQuery();
            if (response.data) {
                setAvailableCommands(response.data.filter((cmd) => cmd.group === 'analyse'));
            }
        })();
    }, []);

    const runCommand = async (command: CommandType) => {
        setActiveCommand(command);
        setCommandError(null);
        setCommandResponse(null);
        setErrorRows([]);
        setInfoRows([]);
        const data = await commandQuery(command.name);

        const error = extractCommandError(data);
        if (!('data' in data) || typeof data.data !== 'object') {
            setCommandError(error?.errors ?? ['An unexpected error occurred']);
            return;
        }

        const result = data.data.result;
        if (Array.isArray(result)) {
            const resultInfoRows: AnalyseRow[] = [];
            const resultErrorRows: AnalyseRow[] = [];

            let id = 0;
            for (const event of result) {
                id++;
                const row: AnalyseRow = {
                    id,
                    file_name: event.file_name,
                    file_path: event.file_path,
                    line_from: event.line_from,
                    line_to: event.line_to,
                    type: event.type,
                    message: event.message,
                    link: event.link,
                };
                if (event.severity === 'info') {
                    resultInfoRows.push(row);
                    continue;
                }
                if (event.severity === 'error') {
                    resultErrorRows.push(row);
                }
            }
            setInfoRows(resultInfoRows);
            setErrorRows(resultErrorRows);
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

    const [expanded, setExpanded] = React.useState<string[]>([]);

    const handleChange = (panel: string) => (_event: React.SyntheticEvent) => {
        setExpanded((v) => (v.includes(panel) ? v.filter((v) => v !== panel) : v.concat(panel)));
    };

    if (availableCommands.length === 0) {
        return (
            <>
                {showHeader && <PageHeader title="Analyse" icon="analytics" description="Static analysis results" />}
                <EmptyState
                    icon="analytics"
                    title="No analyse commands available"
                    description="Install a static analyser (Mago, Psalm, PHPStan) to enable this feature."
                />
            </>
        );
    }

    return (
        <>
            {showHeader && <PageHeader title="Analyse" icon="analytics" description="Static analysis results" />}
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
            {(infoRows.length > 0 || errorRows.length > 0) && (
                <>
                    <Accordion key="panel1" expanded={expanded.includes('panel1')} onChange={handleChange('panel1')}>
                        <AccordionSummary expandIcon={<ExpandMoreIcon />}>
                            <Typography sx={{width: '33%', flexShrink: 0}}>Info ({infoRows.length})</Typography>
                        </AccordionSummary>
                        <AccordionDetails>{renderGrid(infoRows)}</AccordionDetails>
                    </Accordion>
                    <Accordion key="panel2" expanded={expanded.includes('panel2')} onChange={handleChange('panel2')}>
                        <AccordionSummary expandIcon={<ExpandMoreIcon />}>
                            <Typography sx={{width: '33%', flexShrink: 0}}>Errors ({errorRows.length})</Typography>
                        </AccordionSummary>
                        <AccordionDetails>{renderGrid(errorRows)}</AccordionDetails>
                    </Accordion>
                </>
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
