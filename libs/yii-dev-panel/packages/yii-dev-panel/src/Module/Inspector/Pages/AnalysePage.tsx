import {Check, ContentCopy, Error, FilePresent} from '@mui/icons-material';
import ExpandMoreIcon from '@mui/icons-material/ExpandMore';
import {
    Accordion,
    AccordionDetails,
    AccordionSummary,
    Button,
    CircularProgress,
    IconButton,
    Link,
    Tooltip,
} from '@mui/material';
import Box from '@mui/material/Box';
import Typography from '@mui/material/Typography';
import {GridColDef, GridColumns, GridRenderCellParams, GridValidRowModel} from '@mui/x-data-grid';
import {DataTable} from '@yiisoft/yii-dev-panel-sdk/Component/Grid';
import {parseFilePathWithLineAnchor} from '@yiisoft/yii-dev-panel-sdk/Helper/filePathParser';
import {useRunCommandMutation} from '@yiisoft/yii-dev-panel/Module/Inspector/API/Inspector';
import clipboardCopy from 'clipboard-copy';
import * as React from 'react';
import {useState} from 'react';
import {useBreadcrumbs} from '@yiisoft/yii-dev-panel/Application/Context/BreadcrumbsContext';

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
                    <Tooltip title="Examine as a file">
                        <IconButton
                            size="small"
                            href={'/inspector/files?path=' + parseFilePathWithLineAnchor(filePath)}
                        >
                            <FilePresent fontSize="small" />
                        </IconButton>
                    </Tooltip>
                    {filePath}
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

function renderGrid(data: any) {
    return <DataTable rows={data as GridValidRowModel[]} columns={columns as GridColumns} />;
}

type CommandResponseType = {
    isSuccessful: boolean | undefined;
    errors: string[];
};
export const AnalysePage = () => {
    const [commandQuery, commandQueryInfo] = useRunCommandMutation();
    const [errorRows, setErrorRows] = useState<any[]>([]);
    const [infoRows, setInfoRows] = useState<any[]>([]);
    const [commandResponse, setCommandResponse] = useState<CommandResponseType | null>(null);

    async function runPsalmHandler() {
        const data = await commandQuery('analyse/psalm');
        if (!('data' in data) || typeof data.data !== 'object') {
            console.error(data);
            return;
        }

        const resultInfoRows: any = [];
        const resultErrorRows: any = [];

        let tempObject = {
            id: 0,
            file_name: '',
            file_path: '',
            line_from: '',
            line_to: '',
            type: '',
            message: '',
            link: '',
        };
        let id = 0;
        for (const event of data.data.result) {
            id++;
            tempObject = {
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
                resultInfoRows.push(tempObject);
                continue;
            }
            if (event.severity === 'error') {
                resultErrorRows.push(tempObject);
            }
        }
        setCommandResponse({
            isSuccessful: data.data.status === 'ok',
            errors: data.data.errors,
        });
        setInfoRows(resultInfoRows);
        setErrorRows(resultErrorRows);
    }

    const [expanded, setExpanded] = React.useState<string[]>([]);

    const handleChange = (panel: string) => (event: React.SyntheticEvent) => {
        setExpanded((v) => (v.includes(panel) ? v.filter((v) => v !== panel) : v.concat(panel)));
    };

    useBreadcrumbs(() => ['Inspector', 'Analyze']);

    return (
        <>
            <h2>{'Psalm'}</h2>
            <Box display="flex" alignItems="center">
                <Button
                    onClick={runPsalmHandler}
                    color={commandResponse === null ? 'primary' : commandResponse.isSuccessful ? 'success' : 'error'}
                    disabled={commandQueryInfo.isLoading}
                    endIcon={commandQueryInfo.isLoading ? <CircularProgress size={24} color="info" /> : null}
                >
                    Run Psalm
                </Button>
                {!commandQueryInfo.isLoading && commandResponse && (
                    <>
                        {commandResponse.isSuccessful === true && <Check color="success" />}
                        {commandResponse.isSuccessful === false && <Error color="error" />}
                    </>
                )}
            </Box>
            {commandQueryInfo.isSuccess && (
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
        </>
    );
};
