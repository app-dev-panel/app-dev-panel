import {Accordion, AccordionDetails, AccordionSummary, Alert, AlertTitle, Divider, Typography} from '@mui/material';
import {HTTPMethod} from '@yiisoft/yii-dev-panel-sdk/API/Debug/Debug';
import {CodeHighlight} from '@yiisoft/yii-dev-panel-sdk/Component/CodeHighlight';
import {JsonRenderer} from '@yiisoft/yii-dev-panel/Module/Debug/Component/JsonRenderer';
import Box from '@mui/material/Box';
import React from 'react';

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
type RequestPanelProps = {
    data: Response;
};

const ResponseAccordion = ({data}: {data: Response}) => {
    if (typeof data.responseRaw !== 'string') {
        return null;
    }

    const responseParts = data.responseRaw.split('\r\n\r\n');
    const headers = responseParts[0];
    const content = responseParts.splice(1).join('\r\n\r\n');
    const match = headers.match(/Content-Type: \w+\/(\w+);/);
    const contentType = Array.isArray(match) ? match[1] : 'plain';
    const isJson = !!contentType.match(/json/);

    return (
        <>
            <Typography component="h2" variant="h6">
                Response
            </Typography>
            <JsonRenderer value={data.response} />
            {content && (
                <Accordion defaultExpanded={content.length < 500}>
                    <AccordionSummary>Content</AccordionSummary>
                    <AccordionDetails>
                        {isJson ? (
                            <JsonRenderer value={JSON.parse(content)} />
                        ) : (
                            <CodeHighlight code={content} language={contentType} showLineNumbers={false} />
                        )}
                    </AccordionDetails>
                </Accordion>
            )}
            <Accordion defaultExpanded={data.responseRaw.length < 500}>
                <AccordionSummary>Raw</AccordionSummary>
                <AccordionDetails>
                    <CodeHighlight code={data.responseRaw} language={contentType} showLineNumbers={false} />
                </AccordionDetails>
            </Accordion>
        </>
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

    return (
        <>
            <CodeHighlight
                language={'plain'}
                code={data.requestMethod + ' ' + data.requestUrl}
                showLineNumbers={false}
            />
            {/*<DebugChip*/}
            {/*    label={data.requestMethod + ' ' + data.responseStatusCode}*/}
            {/*    color={buttonColorHttp(data.responseStatusCode)}*/}
            {/*/>*/}
            <Typography component="h2" variant="h6">
                Request
            </Typography>
            <JsonRenderer value={data.request} />
            <Accordion defaultExpanded={data.requestRaw.length < 500}>
                <AccordionSummary>Raw</AccordionSummary>
                <AccordionDetails>
                    <CodeHighlight code={data.requestRaw} language={'plain'} showLineNumbers={false} />
                </AccordionDetails>
            </Accordion>
            <Divider />
            <ResponseAccordion data={data} />
        </>
    );
};
