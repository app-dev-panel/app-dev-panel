import {JsonRenderer} from '@app-dev-panel/panel/Module/Debug/Component/JsonRenderer';
import {useDoRequestMutation, usePostCurlBuildMutation} from '@app-dev-panel/panel/Module/Inspector/API/Inspector';
import {useDebugEntry} from '@app-dev-panel/sdk/API/Debug/Context';
import {HTTPMethod, useLazyGetDebugQuery} from '@app-dev-panel/sdk/API/Debug/Debug';
import {CodeHighlight} from '@app-dev-panel/sdk/Component/CodeHighlight';
import {EmptyState} from '@app-dev-panel/sdk/Component/EmptyState';
import {SectionTitle} from '@app-dev-panel/sdk/Component/SectionTitle';
import {primitives} from '@app-dev-panel/sdk/Component/Theme/tokens';
import {
    Box,
    Chip,
    Icon,
    IconButton,
    InputAdornment,
    Tab,
    Tabs,
    TextField,
    type Theme,
    Tooltip,
    Typography,
} from '@mui/material';
import {styled, useTheme} from '@mui/material/styles';
import clipboardCopy from 'clipboard-copy';
import {useCallback, useMemo, useState} from 'react';
import {useSearchParams} from 'react-router-dom';

type Response = {
    content: string;
    request: string;
    requestIsAjax: boolean;
    requestMethod: HTTPMethod;
    requestPath: string;
    requestQuery: string;
    requestRaw: string;
    requestUrl: string;
    response: string;
    responseRaw: string;
    responseStatusCode: number;
    userIp: string;
};
type RequestPanelProps = {data: Response};

const statusColor = (code: number, theme: Theme): string => {
    if (code >= 500) return theme.palette.error.main;
    if (code >= 400) return theme.palette.warning.main;
    if (code >= 300) return theme.palette.primary.main;
    return theme.palette.success.main;
};

const methodColor = (method: string, theme: Theme): string => {
    switch (method?.toUpperCase()) {
        case 'GET':
            return theme.palette.success.main;
        case 'POST':
            return theme.palette.primary.main;
        case 'PUT':
        case 'PATCH':
            return theme.palette.warning.main;
        case 'DELETE':
            return theme.palette.error.main;
        default:
            return theme.palette.text.disabled;
    }
};

// ---------------------------------------------------------------------------
// Styled components
// ---------------------------------------------------------------------------

const MetricBox = styled(Box)(({theme}) => ({
    display: 'flex',
    alignItems: 'center',
    gap: theme.spacing(1.5),
    padding: theme.spacing(1.5, 2),
    borderBottom: `1px solid ${theme.palette.divider}`,
    backgroundColor: theme.palette.action.hover,
}));

const TabPanel = styled(Box)(({theme}) => ({padding: theme.spacing(2)}));

const HeaderTable = styled('table')(({theme}) => ({
    width: '100%',
    borderCollapse: 'collapse',
    fontFamily: primitives.fontFamilyMono,
    fontSize: '12px',
    '& th': {
        textAlign: 'left',
        padding: theme.spacing(0.75, 1.5),
        fontWeight: 600,
        color: theme.palette.text.secondary,
        borderBottom: `1px solid ${theme.palette.divider}`,
        whiteSpace: 'nowrap',
        width: '30%',
        verticalAlign: 'top',
    },
    '& td': {
        padding: theme.spacing(0.75, 1.5),
        color: theme.palette.text.primary,
        borderBottom: `1px solid ${theme.palette.divider}`,
        wordBreak: 'break-all',
    },
    '& tr:last-child th, & tr:last-child td': {borderBottom: 'none'},
}));

// ---------------------------------------------------------------------------
// Helpers
// ---------------------------------------------------------------------------

function parseHeaders(raw: string): Array<{name: string; value: string}> {
    const lines = raw.split('\r\n').filter(Boolean);
    const headers: Array<{name: string; value: string}> = [];
    for (const line of lines) {
        const colonIndex = line.indexOf(':');
        if (colonIndex > 0) {
            headers.push({name: line.substring(0, colonIndex).trim(), value: line.substring(colonIndex + 1).trim()});
        }
    }
    return headers;
}

function parseQueryParams(queryString: string): Array<{name: string; value: string}> {
    if (!queryString) return [];
    const cleaned = queryString.startsWith('?') ? queryString.slice(1) : queryString;
    if (!cleaned) return [];
    const params: Array<{name: string; value: string}> = [];
    for (const pair of cleaned.split('&')) {
        const eqIndex = pair.indexOf('=');
        if (eqIndex > 0) {
            params.push({
                name: decodeURIComponent(pair.substring(0, eqIndex)),
                value: decodeURIComponent(pair.substring(eqIndex + 1)),
            });
        } else if (pair) {
            params.push({name: decodeURIComponent(pair), value: ''});
        }
    }
    return params;
}

const HeadersTable = ({headers}: {headers: Array<{name: string; value: string}>}) => {
    const [filter, setFilter] = useState('');

    const filteredHeaders = useMemo(() => {
        if (!filter) return headers;
        const lower = filter.toLowerCase();
        return headers.filter((h) => h.name.toLowerCase().includes(lower) || h.value.toLowerCase().includes(lower));
    }, [headers, filter]);

    if (headers.length === 0) return null;
    return (
        <>
            {headers.length > 3 && (
                <TextField
                    size="small"
                    placeholder="Filter..."
                    value={filter}
                    onChange={(e) => setFilter(e.target.value)}
                    slotProps={{
                        input: {
                            startAdornment: (
                                <InputAdornment position="start" sx={{color: 'text.disabled', fontSize: '14px'}}>
                                    /
                                </InputAdornment>
                            ),
                        },
                    }}
                    sx={{
                        mb: 1,
                        maxWidth: 260,
                        '& .MuiOutlinedInput-root': {fontSize: '12px', height: 30},
                        '& .MuiInputAdornment-root': {mr: 0},
                    }}
                />
            )}
            <Box sx={{borderRadius: 1, border: '1px solid', borderColor: 'divider', overflow: 'hidden'}}>
                <HeaderTable>
                    <tbody>
                        {filteredHeaders.map((h, i) => (
                            <tr key={i}>
                                <th>{h.name}</th>
                                <td>{h.value}</td>
                            </tr>
                        ))}
                    </tbody>
                </HeaderTable>
            </Box>
        </>
    );
};

// ---------------------------------------------------------------------------
// Request Tab
// ---------------------------------------------------------------------------

const RequestTab = ({data}: {data: Response}) => {
    const queryParams = parseQueryParams(data.requestQuery);
    const requestParts = typeof data.requestRaw === 'string' ? data.requestRaw.split('\r\n\r\n') : [];
    const requestHeadersRaw = requestParts[0] || '';
    const requestBody = requestParts.slice(1).join('\r\n\r\n');
    const requestHeaders = parseHeaders(requestHeadersRaw);

    return (
        <TabPanel>
            {requestHeaders.length > 0 && (
                <>
                    <SectionTitle>Headers</SectionTitle>
                    <HeadersTable headers={requestHeaders} />
                </>
            )}

            {queryParams.length > 0 && (
                <>
                    <SectionTitle>Query Parameters</SectionTitle>
                    <HeadersTable headers={queryParams} />
                </>
            )}

            {requestBody && (
                <>
                    <SectionTitle>Body</SectionTitle>
                    <Box sx={{borderRadius: 1, overflow: 'hidden', border: '1px solid', borderColor: 'divider'}}>
                        <CodeHighlight code={requestBody} language="plain" showLineNumbers={false} />
                    </Box>
                </>
            )}

            {!requestHeaders.length && !queryParams.length && !requestBody && (
                <>
                    <SectionTitle>Request Data</SectionTitle>
                    <JsonRenderer value={data.request} />
                </>
            )}
        </TabPanel>
    );
};

// ---------------------------------------------------------------------------
// Response Tab
// ---------------------------------------------------------------------------

const ResponseTab = ({data}: {data: Response}) => {
    const responseParts = typeof data.responseRaw === 'string' ? data.responseRaw.split('\r\n\r\n') : [];
    const responseHeadersRaw = responseParts[0] || '';
    const responseBody = responseParts.slice(1).join('\r\n\r\n');
    const responseHeaders = parseHeaders(responseHeadersRaw);

    const contentTypeMatch = responseHeadersRaw.match(/Content-Type:\s*\w+\/(\w+)/);
    const contentType = Array.isArray(contentTypeMatch) ? contentTypeMatch[1] : 'plain';
    const isJson = /json/.test(contentType);

    let parsedBody: unknown = responseBody;
    if (isJson && responseBody) {
        try {
            parsedBody = JSON.parse(responseBody);
        } catch {
            /* keep as string */
        }
    }

    return (
        <TabPanel>
            {responseHeaders.length > 0 && (
                <>
                    <SectionTitle>Headers</SectionTitle>
                    <HeadersTable headers={responseHeaders} />
                </>
            )}

            {responseBody && (
                <>
                    <SectionTitle>Body</SectionTitle>
                    <Box sx={{borderRadius: 1, overflow: 'hidden', border: '1px solid', borderColor: 'divider'}}>
                        {isJson && typeof parsedBody === 'object' ? (
                            <Box sx={{p: 1.5}}>
                                <JsonRenderer value={parsedBody} />
                            </Box>
                        ) : (
                            <CodeHighlight code={responseBody} language={contentType} showLineNumbers={false} />
                        )}
                    </Box>
                </>
            )}

            {!responseHeaders.length && !responseBody && (
                <>
                    <SectionTitle>Response Data</SectionTitle>
                    <JsonRenderer value={data.response} />
                </>
            )}
        </TabPanel>
    );
};

// ---------------------------------------------------------------------------
// Raw Tab
// ---------------------------------------------------------------------------

const RawTab = ({data}: {data: Response}) => (
    <TabPanel>
        {data.requestRaw && (
            <>
                <SectionTitle>Raw Request</SectionTitle>
                <Box sx={{borderRadius: 1, overflow: 'hidden', border: '1px solid', borderColor: 'divider'}}>
                    <CodeHighlight code={data.requestRaw} language="plain" showLineNumbers={false} />
                </Box>
            </>
        )}

        {data.responseRaw && (
            <>
                <SectionTitle>Raw Response</SectionTitle>
                <Box sx={{borderRadius: 1, overflow: 'hidden', border: '1px solid', borderColor: 'divider'}}>
                    <CodeHighlight code={data.responseRaw} language="plain" showLineNumbers={false} />
                </Box>
            </>
        )}

        {!data.requestRaw && !data.responseRaw && (
            <Typography sx={{color: 'text.disabled', fontSize: '13px', textAlign: 'center', py: 4}}>
                No raw data available
            </Typography>
        )}
    </TabPanel>
);

// ---------------------------------------------------------------------------
// Parsed Data Tab (original JSON view)
// ---------------------------------------------------------------------------

const ParsedTab = ({data}: {data: Response}) => (
    <TabPanel>
        <SectionTitle>Request</SectionTitle>
        <JsonRenderer value={data.request} />
        <SectionTitle>Response</SectionTitle>
        <JsonRenderer value={data.response} />
    </TabPanel>
);

// ---------------------------------------------------------------------------
// Main component
// ---------------------------------------------------------------------------

const tabNames = ['request', 'response', 'raw', 'parsed'] as const;

export const RequestPanel = ({data}: RequestPanelProps) => {
    const theme = useTheme();
    const [searchParams, setSearchParams] = useSearchParams();
    const tabParam = searchParams.get('requestTab') || 'request';
    const tab = Math.max(0, tabNames.indexOf(tabParam as (typeof tabNames)[number]));
    const debugEntry = useDebugEntry();
    const [doRequest] = useDoRequestMutation();
    const [postCurlBuildInfo] = usePostCurlBuildMutation();
    const [getDebugQuery] = useLazyGetDebugQuery();

    const handleTabChange = useCallback(
        (_: unknown, newValue: number) => {
            setSearchParams((params) => {
                params.set('requestTab', tabNames[newValue]);
                return params;
            });
        },
        [setSearchParams],
    );

    const handleRepeatRequest = useCallback(async () => {
        if (!debugEntry) return;
        try {
            await doRequest({id: debugEntry.id});
        } catch (e) {
            console.error(e);
        }
        getDebugQuery();
    }, [debugEntry, doRequest, getDebugQuery]);

    const handleCopyCurl = useCallback(async () => {
        if (!debugEntry) return;
        const result = await postCurlBuildInfo(debugEntry.id);
        if ('error' in result) {
            console.error(result.error);
            return;
        }
        clipboardCopy(result.data.command);
    }, [debugEntry, postCurlBuildInfo]);

    if (!data) {
        return <EmptyState icon="http" title="Request is not associated with HTTP request" />;
    }

    return (
        <Box>
            <MetricBox>
                <Chip
                    label={data.requestMethod}
                    size="small"
                    sx={{
                        fontWeight: 700,
                        fontSize: '11px',
                        height: 22,
                        backgroundColor: methodColor(data.requestMethod, theme),
                        color: theme.palette.common.white,
                        borderRadius: 1,
                    }}
                />
                <Typography
                    sx={{fontFamily: primitives.fontFamilyMono, fontSize: '13px', flex: 1, wordBreak: 'break-all'}}
                >
                    {data.requestUrl}
                </Typography>
                <Chip
                    label={data.responseStatusCode}
                    size="small"
                    sx={{
                        fontWeight: 700,
                        fontSize: '11px',
                        height: 22,
                        backgroundColor: statusColor(data.responseStatusCode, theme),
                        color: theme.palette.common.white,
                        borderRadius: 1,
                    }}
                />
                {data.requestIsAjax && (
                    <Chip
                        label="AJAX"
                        size="small"
                        sx={{fontSize: '10px', height: 20, borderRadius: 1}}
                        variant="outlined"
                    />
                )}
                {data.userIp && (
                    <Typography sx={{fontFamily: primitives.fontFamilyMono, fontSize: '11px', color: 'text.disabled'}}>
                        {data.userIp}
                    </Typography>
                )}
                <Tooltip title="Repeat Request">
                    <IconButton size="small" onClick={handleRepeatRequest}>
                        <Icon sx={{fontSize: 18}}>replay</Icon>
                    </IconButton>
                </Tooltip>
                <Tooltip title="Copy as cURL">
                    <IconButton size="small" onClick={handleCopyCurl}>
                        <Icon sx={{fontSize: 18}}>content_copy</Icon>
                    </IconButton>
                </Tooltip>
            </MetricBox>

            <Box sx={{borderBottom: 1, borderColor: 'divider'}}>
                <Tabs
                    value={tab}
                    onChange={handleTabChange}
                    sx={{'& .MuiTab-root': {textTransform: 'none', minHeight: 40, fontSize: '13px', fontWeight: 600}}}
                >
                    <Tab label="Request" />
                    <Tab label="Response" />
                    <Tab label="Raw" />
                    <Tab label="Parsed" />
                </Tabs>
            </Box>

            {tab === 0 && <RequestTab data={data} />}
            {tab === 1 && <ResponseTab data={data} />}
            {tab === 2 && <RawTab data={data} />}
            {tab === 3 && <ParsedTab data={data} />}
        </Box>
    );
};
