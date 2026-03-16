import {Alert, AlertTitle, Box, Chip, Collapse, Icon, Typography} from '@mui/material';
import {styled} from '@mui/material/styles';
import {HTTPMethod} from '@yiisoft/yii-dev-panel-sdk/API/Debug/Debug';
import {CodeHighlight} from '@yiisoft/yii-dev-panel-sdk/Component/CodeHighlight';
import {SectionTitle} from '@yiisoft/yii-dev-panel-sdk/Component/SectionTitle';
import {primitives} from '@yiisoft/yii-dev-panel-sdk/Component/Theme/tokens';
import {JsonRenderer} from '@yiisoft/yii-dev-panel/Module/Debug/Component/JsonRenderer';
import {useState} from 'react';

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

const statusColor = (code: number): string => {
    if (code >= 500) return primitives.red600;
    if (code >= 400) return primitives.amber600;
    if (code >= 300) return primitives.blue500;
    return primitives.green600;
};

const methodColor = (method: string): string => {
    switch (method?.toUpperCase()) {
        case 'GET':
            return primitives.green600;
        case 'POST':
            return primitives.blue500;
        case 'PUT':
        case 'PATCH':
            return primitives.amber600;
        case 'DELETE':
            return primitives.red600;
        default:
            return primitives.gray400;
    }
};

const MetricBox = styled(Box)(({theme}) => ({
    display: 'flex',
    alignItems: 'center',
    gap: theme.spacing(1.5),
    padding: theme.spacing(1.5, 2),
    borderBottom: `1px solid ${theme.palette.divider}`,
    backgroundColor: theme.palette.action.hover,
}));

const SectionBox = styled(Box)(({theme}) => ({
    padding: theme.spacing(2),
    borderBottom: `1px solid ${theme.palette.divider}`,
}));

const RawToggle = ({label, content, language}: {label: string; content: string; language: string}) => {
    const [open, setOpen] = useState(false);

    if (!content) return null;

    return (
        <Box sx={{mt: 1.5}}>
            <Box
                onClick={() => setOpen(!open)}
                sx={{
                    display: 'flex',
                    alignItems: 'center',
                    gap: 0.5,
                    cursor: 'pointer',
                    color: 'text.disabled',
                    fontSize: '12px',
                    fontWeight: 600,
                    '&:hover': {color: 'text.secondary'},
                }}
            >
                <Icon sx={{fontSize: 16}}>{open ? 'expand_less' : 'expand_more'}</Icon>
                {label}
            </Box>
            <Collapse in={open}>
                <Box sx={{mt: 1, borderRadius: 1, overflow: 'hidden', border: '1px solid', borderColor: 'divider'}}>
                    <CodeHighlight code={content} language={language} showLineNumbers={false} />
                </Box>
            </Collapse>
        </Box>
    );
};

export const RequestPanel = ({data}: RequestPanelProps) => {
    if (!data) {
        return (
            <Box m={2}>
                <Alert severity="info">
                    <AlertTitle>Request is not associated with HTTP request</AlertTitle>
                </Alert>
            </Box>
        );
    }

    const responseParts = typeof data.responseRaw === 'string' ? data.responseRaw.split('\r\n\r\n') : [];
    const responseHeaders = responseParts[0] || '';
    const responseContent = responseParts.slice(1).join('\r\n\r\n');
    const contentTypeMatch = responseHeaders.match(/Content-Type: \w+\/(\w+);/);
    const contentType = Array.isArray(contentTypeMatch) ? contentTypeMatch[1] : 'plain';
    const isJson = /json/.test(contentType);

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
                        backgroundColor: methodColor(data.requestMethod),
                        color: '#fff',
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
                        backgroundColor: statusColor(data.responseStatusCode),
                        color: '#fff',
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
            </MetricBox>

            <SectionBox>
                <SectionTitle>Request</SectionTitle>
                <JsonRenderer value={data.request} />
                <RawToggle label="Raw Request" content={data.requestRaw} language="plain" />
            </SectionBox>

            <SectionBox>
                <SectionTitle>Response</SectionTitle>
                <JsonRenderer value={data.response} />

                {responseContent && (
                    <Box sx={{mt: 1.5}}>
                        <Typography sx={{fontSize: '12px', fontWeight: 600, color: 'text.disabled', mb: 0.5}}>
                            Content
                        </Typography>
                        <Box sx={{borderRadius: 1, overflow: 'hidden', border: '1px solid', borderColor: 'divider'}}>
                            {isJson ? (
                                <Box sx={{p: 1.5}}>
                                    <JsonRenderer value={JSON.parse(responseContent)} />
                                </Box>
                            ) : (
                                <CodeHighlight code={responseContent} language={contentType} showLineNumbers={false} />
                            )}
                        </Box>
                    </Box>
                )}

                <RawToggle label="Raw Response" content={data.responseRaw} language={contentType} />
            </SectionBox>
        </Box>
    );
};
