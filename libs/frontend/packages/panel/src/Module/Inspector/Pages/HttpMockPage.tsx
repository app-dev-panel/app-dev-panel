import {
    type HttpMockExpectation,
    useClearHttpMockExpectationsMutation,
    useCreateHttpMockExpectationMutation,
    useGetHttpMockExpectationsQuery,
    useGetHttpMockHistoryQuery,
    useGetHttpMockStatusQuery,
    useResetHttpMockMutation,
} from '@app-dev-panel/panel/Module/Inspector/API/Inspector';
import {EmptyState} from '@app-dev-panel/sdk/Component/EmptyState';
import {PageHeader} from '@app-dev-panel/sdk/Component/PageHeader';
import {
    Alert,
    Box,
    Button,
    Chip,
    Dialog,
    DialogActions,
    DialogContent,
    DialogTitle,
    FormControl,
    FormControlLabel,
    InputLabel,
    LinearProgress,
    MenuItem,
    Paper,
    Radio,
    RadioGroup,
    Select,
    Stack,
    Tab,
    Table,
    TableBody,
    TableCell,
    TableContainer,
    TableHead,
    TableRow,
    Tabs,
    TextField,
    Tooltip,
    Typography,
} from '@mui/material';
import {styled} from '@mui/material/styles';
import {useCallback, useState} from 'react';
import {useSearchParams} from 'react-router';

// ---------------------------------------------------------------------------
// Types
// ---------------------------------------------------------------------------

const tabs = ['expectations', 'history', 'import-export'] as const;
type TabKey = (typeof tabs)[number];

const tabLabels: Record<TabKey, string> = {
    expectations: 'Expectations',
    history: 'Request Log',
    'import-export': 'Import / Export',
};

const HTTP_METHODS = ['GET', 'POST', 'PUT', 'DELETE', 'PATCH', 'HEAD', 'OPTIONS', 'ANY'] as const;
const MATCH_TYPES = ['isEqualTo', 'contains', 'matches'] as const;

type MatchType = (typeof MATCH_TYPES)[number];

const matchTypeLabels: Record<MatchType, string> = {isEqualTo: 'Equals', contains: 'Contains', matches: 'Regex'};

const defaultExpectation: HttpMockExpectation = {
    request: {method: 'GET', url: {isEqualTo: '/'}},
    response: {statusCode: 200, body: '', headers: {'Content-Type': 'application/json'}},
    priority: 0,
};

// ---------------------------------------------------------------------------
// Styled
// ---------------------------------------------------------------------------

const MethodChip = styled(Chip)<{method?: string}>(({theme, method}) => {
    const colors: Record<string, string> = {
        GET: theme.palette.info.main,
        POST: theme.palette.success.main,
        PUT: theme.palette.warning.main,
        DELETE: theme.palette.error.main,
        PATCH: theme.palette.secondary.main,
    };
    return {fontWeight: 700, backgroundColor: colors[method ?? ''] ?? theme.palette.grey[500], color: '#fff'};
});

const StatusChip = styled(Chip)<{code?: number}>(({theme, code}) => {
    const c = code ?? 200;
    let color = theme.palette.success.main;
    if (c >= 400) color = theme.palette.error.main;
    else if (c >= 300) color = theme.palette.warning.main;
    return {fontWeight: 700, backgroundColor: color, color: '#fff'};
});

// ---------------------------------------------------------------------------
// Helpers
// ---------------------------------------------------------------------------

function getUrlPattern(exp: HttpMockExpectation): string {
    const url = exp.request?.url;
    if (!url) return '*';
    return url.isEqualTo ?? url.matches ?? url.contains ?? '*';
}

function getUrlMatchType(exp: HttpMockExpectation): MatchType {
    const url = exp.request?.url;
    if (!url) return 'isEqualTo';
    if (url.matches) return 'matches';
    if (url.contains) return 'contains';
    return 'isEqualTo';
}

// ---------------------------------------------------------------------------
// Sub-components
// ---------------------------------------------------------------------------

function ServerStatusBar() {
    const {data: status, isLoading} = useGetHttpMockStatusQuery(undefined, {pollingInterval: 10000});
    const [resetMock, resetInfo] = useResetHttpMockMutation();

    if (isLoading) return <LinearProgress />;

    const running = status?.running ?? false;

    return (
        <Stack direction="row" alignItems="center" gap={2} sx={{mb: 2}}>
            <Chip
                label={running ? `Running on :${status?.port}` : 'Not Running'}
                color={running ? 'success' : 'default'}
                variant="outlined"
                size="small"
                icon={
                    <Box
                        component="span"
                        sx={{
                            width: 8,
                            height: 8,
                            borderRadius: '50%',
                            backgroundColor: running ? 'success.main' : 'text.disabled',
                            ml: 1,
                        }}
                    />
                }
            />
            {running && (
                <Tooltip title="Reset server state (clear expectations, history, scenarios)">
                    <Button
                        size="small"
                        color="warning"
                        variant="outlined"
                        onClick={() => resetMock()}
                        disabled={resetInfo.isLoading}
                    >
                        Reset Server
                    </Button>
                </Tooltip>
            )}
            {!running && (
                <Alert severity="info" sx={{flex: 1, py: 0}}>
                    Start Phiremock server with: <code>vendor/bin/phiremock --port 8086</code>
                </Alert>
            )}
        </Stack>
    );
}

// ---------------------------------------------------------------------------
// Expectation Dialog
// ---------------------------------------------------------------------------

function ExpectationDialog({
    open,
    initial,
    onClose,
    onSave,
}: {
    open: boolean;
    initial: HttpMockExpectation;
    onClose: () => void;
    onSave: (exp: HttpMockExpectation) => void;
}) {
    const [method, setMethod] = useState(initial.request?.method ?? 'GET');
    const [urlValue, setUrlValue] = useState(getUrlPattern(initial));
    const [urlMatchType, setUrlMatchType] = useState<MatchType>(getUrlMatchType(initial));
    const [statusCode, setStatusCode] = useState(initial.response?.statusCode ?? 200);
    const [responseBody, setResponseBody] = useState(initial.response?.body ?? '');
    const [contentType, setContentType] = useState(initial.response?.headers?.['Content-Type'] ?? 'application/json');
    const [delayMs, setDelayMs] = useState(initial.response?.delayMillis ?? 0);
    const [priority, setPriority] = useState(initial.priority ?? 0);
    const [scenarioName, setScenarioName] = useState(initial.scenarioName ?? '');
    const [scenarioStateIs, setScenarioStateIs] = useState(initial.scenarioStateIs ?? '');
    const [newScenarioState, setNewScenarioState] = useState(initial.newScenarioState ?? '');
    const [proxyTo, setProxyTo] = useState(initial.proxyTo ?? '');
    const [responseMode, setResponseMode] = useState<'static' | 'proxy'>(initial.proxyTo ? 'proxy' : 'static');

    const handleSave = () => {
        const exp: HttpMockExpectation = {
            request: {method: method === 'ANY' ? undefined : method, url: {[urlMatchType]: urlValue}},
            response: {
                statusCode,
                body: responseMode === 'static' ? responseBody : undefined,
                headers: responseMode === 'static' ? {'Content-Type': contentType} : undefined,
                delayMillis: delayMs > 0 ? delayMs : undefined,
            },
            proxyTo: responseMode === 'proxy' ? proxyTo : undefined,
            priority: priority > 0 ? priority : undefined,
            scenarioName: scenarioName || undefined,
            scenarioStateIs: scenarioStateIs || undefined,
            newScenarioState: newScenarioState || undefined,
        };
        onSave(exp);
    };

    return (
        <Dialog open={open} onClose={onClose} maxWidth="md" fullWidth>
            <DialogTitle>New Expectation</DialogTitle>
            <DialogContent>
                <Stack gap={2.5} sx={{mt: 1}}>
                    <Typography variant="subtitle2" color="text.secondary">
                        Request Matching
                    </Typography>
                    <Stack direction="row" gap={2}>
                        <FormControl size="small" sx={{minWidth: 120}}>
                            <InputLabel>Method</InputLabel>
                            <Select value={method} label="Method" onChange={(e) => setMethod(e.target.value)}>
                                {HTTP_METHODS.map((m) => (
                                    <MenuItem key={m} value={m}>
                                        {m}
                                    </MenuItem>
                                ))}
                            </Select>
                        </FormControl>
                        <TextField
                            size="small"
                            label="URL"
                            value={urlValue}
                            onChange={(e) => setUrlValue(e.target.value)}
                            fullWidth
                            placeholder="/api/example"
                        />
                        <FormControl size="small" sx={{minWidth: 130}}>
                            <InputLabel>Match</InputLabel>
                            <Select
                                value={urlMatchType}
                                label="Match"
                                onChange={(e) => setUrlMatchType(e.target.value as MatchType)}
                            >
                                {MATCH_TYPES.map((t) => (
                                    <MenuItem key={t} value={t}>
                                        {matchTypeLabels[t]}
                                    </MenuItem>
                                ))}
                            </Select>
                        </FormControl>
                    </Stack>

                    <Typography variant="subtitle2" color="text.secondary">
                        Response
                    </Typography>
                    <RadioGroup
                        row
                        value={responseMode}
                        onChange={(e) => setResponseMode(e.target.value as 'static' | 'proxy')}
                    >
                        <FormControlLabel value="static" control={<Radio size="small" />} label="Static Response" />
                        <FormControlLabel value="proxy" control={<Radio size="small" />} label="Proxy" />
                    </RadioGroup>

                    {responseMode === 'static' ? (
                        <>
                            <Stack direction="row" gap={2}>
                                <TextField
                                    size="small"
                                    label="Status Code"
                                    type="number"
                                    value={statusCode}
                                    onChange={(e) => setStatusCode(Number(e.target.value))}
                                    sx={{width: 140}}
                                />
                                <TextField
                                    size="small"
                                    label="Content-Type"
                                    value={contentType}
                                    onChange={(e) => setContentType(e.target.value)}
                                    fullWidth
                                />
                            </Stack>
                            <TextField
                                size="small"
                                label="Response Body"
                                multiline
                                minRows={4}
                                maxRows={12}
                                value={responseBody}
                                onChange={(e) => setResponseBody(e.target.value)}
                                fullWidth
                                placeholder={'{\n  "status": "success"\n}'}
                                sx={{fontFamily: 'monospace'}}
                            />
                        </>
                    ) : (
                        <TextField
                            size="small"
                            label="Proxy URL"
                            value={proxyTo}
                            onChange={(e) => setProxyTo(e.target.value)}
                            fullWidth
                            placeholder="https://staging.api.example.com"
                        />
                    )}

                    <Typography variant="subtitle2" color="text.secondary">
                        Advanced
                    </Typography>
                    <Stack direction="row" gap={2}>
                        <TextField
                            size="small"
                            label="Priority"
                            type="number"
                            value={priority}
                            onChange={(e) => setPriority(Number(e.target.value))}
                            sx={{width: 120}}
                        />
                        <TextField
                            size="small"
                            label="Delay (ms)"
                            type="number"
                            value={delayMs}
                            onChange={(e) => setDelayMs(Number(e.target.value))}
                            sx={{width: 140}}
                        />
                    </Stack>
                    <Stack direction="row" gap={2}>
                        <TextField
                            size="small"
                            label="Scenario Name"
                            value={scenarioName}
                            onChange={(e) => setScenarioName(e.target.value)}
                            fullWidth
                        />
                        <TextField
                            size="small"
                            label="State Is"
                            value={scenarioStateIs}
                            onChange={(e) => setScenarioStateIs(e.target.value)}
                            fullWidth
                        />
                        <TextField
                            size="small"
                            label="New State"
                            value={newScenarioState}
                            onChange={(e) => setNewScenarioState(e.target.value)}
                            fullWidth
                        />
                    </Stack>
                </Stack>
            </DialogContent>
            <DialogActions>
                <Button onClick={onClose}>Cancel</Button>
                <Button variant="contained" onClick={handleSave}>
                    Save
                </Button>
            </DialogActions>
        </Dialog>
    );
}

// ---------------------------------------------------------------------------
// Expectations Tab
// ---------------------------------------------------------------------------

function ExpectationsTab() {
    const {data: expectations, isLoading, isFetching} = useGetHttpMockExpectationsQuery();
    const [createExpectation] = useCreateHttpMockExpectationMutation();
    const [clearExpectations, clearInfo] = useClearHttpMockExpectationsMutation();
    const [dialogOpen, setDialogOpen] = useState(false);

    const handleSave = async (exp: HttpMockExpectation) => {
        await createExpectation(exp);
        setDialogOpen(false);
    };

    if (isLoading) return <LinearProgress />;

    return (
        <>
            <Stack direction="row" gap={1} sx={{mb: 2}}>
                <Button variant="contained" size="small" onClick={() => setDialogOpen(true)}>
                    + New Expectation
                </Button>
                <Button
                    variant="outlined"
                    size="small"
                    color="error"
                    onClick={() => clearExpectations()}
                    disabled={clearInfo.isLoading || !expectations?.length}
                >
                    Clear All
                </Button>
            </Stack>

            {isFetching && <LinearProgress />}

            {!expectations?.length ? (
                <EmptyState
                    icon="cloud_off"
                    title="No expectations"
                    description="Create an expectation to start mocking HTTP responses."
                />
            ) : (
                <Stack gap={1.5}>
                    {expectations.map((exp, index) => (
                        <ExpectationCard key={index} expectation={exp} />
                    ))}
                </Stack>
            )}

            <ExpectationDialog
                open={dialogOpen}
                initial={defaultExpectation}
                onClose={() => setDialogOpen(false)}
                onSave={handleSave}
            />
        </>
    );
}

function ExpectationCard({expectation: exp}: {expectation: HttpMockExpectation}) {
    const urlPattern = getUrlPattern(exp);
    const method = exp.request?.method ?? 'ANY';
    const statusCode = exp.response?.statusCode ?? 200;
    const delay = exp.response?.delayMillis ?? 0;
    const isProxy = !!exp.proxyTo;

    return (
        <Paper variant="outlined" sx={{p: 2}}>
            <Stack direction="row" alignItems="center" gap={1.5} flexWrap="wrap">
                <MethodChip label={method} method={method} size="small" />
                <Typography variant="body2" fontFamily="monospace" fontWeight={600} sx={{flex: 1, minWidth: 200}}>
                    {urlPattern}
                </Typography>
                {exp.priority !== undefined && exp.priority > 0 && (
                    <Chip label={`Priority: ${exp.priority}`} size="small" variant="outlined" />
                )}
            </Stack>
            <Stack direction="row" alignItems="center" gap={1.5} sx={{mt: 1}}>
                <Typography variant="caption" color="text.secondary">
                    {isProxy ? (
                        <>
                            Proxy to <code>{exp.proxyTo}</code>
                        </>
                    ) : (
                        <>
                            <StatusChip label={statusCode} code={statusCode} size="small" />
                            {delay > 0 && (
                                <Chip label={`${delay}ms delay`} size="small" variant="outlined" sx={{ml: 1}} />
                            )}
                        </>
                    )}
                </Typography>
                {exp.scenarioName && (
                    <Chip label={`Scenario: ${exp.scenarioName}`} size="small" variant="outlined" color="secondary" />
                )}
            </Stack>
            {exp.response?.body && (
                <Typography
                    variant="caption"
                    component="pre"
                    sx={{
                        mt: 1,
                        p: 1,
                        backgroundColor: 'action.hover',
                        borderRadius: 1,
                        overflow: 'auto',
                        maxHeight: 100,
                        fontFamily: 'monospace',
                        fontSize: '0.75rem',
                    }}
                >
                    {exp.response.body}
                </Typography>
            )}
        </Paper>
    );
}

// ---------------------------------------------------------------------------
// History Tab
// ---------------------------------------------------------------------------

function HistoryTab() {
    const {data: history, isLoading, isFetching} = useGetHttpMockHistoryQuery();

    if (isLoading) return <LinearProgress />;

    return (
        <>
            {isFetching && <LinearProgress />}
            {!history?.length ? (
                <EmptyState
                    icon="history"
                    title="No requests recorded"
                    description="Requests to the mock server will appear here."
                />
            ) : (
                <TableContainer component={Paper} variant="outlined">
                    <Table size="small">
                        <TableHead>
                            <TableRow>
                                <TableCell width={80}>Method</TableCell>
                                <TableCell>URL</TableCell>
                                <TableCell width={200}>Body</TableCell>
                            </TableRow>
                        </TableHead>
                        <TableBody>
                            {history.map((entry, index) => (
                                <TableRow key={index}>
                                    <TableCell>
                                        <MethodChip label={entry.method} method={entry.method} size="small" />
                                    </TableCell>
                                    <TableCell>
                                        <Typography variant="body2" fontFamily="monospace">
                                            {entry.url}
                                        </Typography>
                                    </TableCell>
                                    <TableCell>
                                        <Typography
                                            variant="caption"
                                            fontFamily="monospace"
                                            sx={{
                                                maxWidth: 200,
                                                overflow: 'hidden',
                                                textOverflow: 'ellipsis',
                                                display: 'block',
                                                whiteSpace: 'nowrap',
                                            }}
                                        >
                                            {entry.body ?? '-'}
                                        </Typography>
                                    </TableCell>
                                </TableRow>
                            ))}
                        </TableBody>
                    </Table>
                </TableContainer>
            )}
        </>
    );
}

// ---------------------------------------------------------------------------
// Import/Export Tab
// ---------------------------------------------------------------------------

function ImportExportTab() {
    const {data: expectations} = useGetHttpMockExpectationsQuery();
    const [createExpectation] = useCreateHttpMockExpectationMutation();
    const [importText, setImportText] = useState('');
    const [importError, setImportError] = useState<string | null>(null);
    const [importSuccess, setImportSuccess] = useState(false);

    const handleExport = () => {
        const json = JSON.stringify(expectations ?? [], null, 2);
        const blob = new Blob([json], {type: 'application/json'});
        const url = URL.createObjectURL(blob);
        const a = document.createElement('a');
        a.href = url;
        a.download = 'http-mock-expectations.json';
        a.click();
        URL.revokeObjectURL(url);
    };

    const handleImport = async () => {
        setImportError(null);
        setImportSuccess(false);
        try {
            const parsed = JSON.parse(importText);
            const items = Array.isArray(parsed) ? parsed : [parsed];
            for (const item of items) {
                await createExpectation(item);
            }
            setImportSuccess(true);
            setImportText('');
        } catch (e) {
            setImportError(e instanceof Error ? e.message : 'Invalid JSON');
        }
    };

    return (
        <Stack gap={2}>
            <Box>
                <Typography variant="subtitle2" gutterBottom>
                    Export
                </Typography>
                <Button variant="outlined" size="small" onClick={handleExport} disabled={!expectations?.length}>
                    Export as JSON ({expectations?.length ?? 0} expectations)
                </Button>
            </Box>

            <Box>
                <Typography variant="subtitle2" gutterBottom>
                    Import
                </Typography>
                <TextField
                    size="small"
                    multiline
                    minRows={5}
                    maxRows={15}
                    fullWidth
                    placeholder="Paste JSON expectations here..."
                    value={importText}
                    onChange={(e) => setImportText(e.target.value)}
                    sx={{fontFamily: 'monospace', mb: 1}}
                />
                {importError && (
                    <Alert severity="error" sx={{mb: 1}}>
                        {importError}
                    </Alert>
                )}
                {importSuccess && (
                    <Alert severity="success" sx={{mb: 1}}>
                        Expectations imported successfully.
                    </Alert>
                )}
                <Button variant="contained" size="small" onClick={handleImport} disabled={!importText.trim()}>
                    Import JSON
                </Button>
            </Box>
        </Stack>
    );
}

// ---------------------------------------------------------------------------
// Main Page
// ---------------------------------------------------------------------------

export const HttpMockPage = () => {
    const [searchParams, setSearchParams] = useSearchParams();
    const activeTab = (searchParams.get('tab') as TabKey) || 'expectations';
    const tabIndex = Math.max(tabs.indexOf(activeTab), 0);

    const handleTabChange = useCallback(
        (_: React.SyntheticEvent, index: number) => {
            setSearchParams({tab: tabs[index]}, {replace: true});
        },
        [setSearchParams],
    );

    return (
        <>
            <PageHeader title="HTTP Mock" icon="cloud_queue" description="Mock external HTTP services with Phiremock" />
            <ServerStatusBar />
            <Box sx={{borderBottom: 1, borderColor: 'divider', mb: 2}}>
                <Tabs value={tabIndex} onChange={handleTabChange}>
                    {tabs.map((key) => (
                        <Tab key={key} label={tabLabels[key]} />
                    ))}
                </Tabs>
            </Box>
            {activeTab === 'expectations' && <ExpectationsTab />}
            {activeTab === 'history' && <HistoryTab />}
            {activeTab === 'import-export' && <ImportExportTab />}
        </>
    );
};
