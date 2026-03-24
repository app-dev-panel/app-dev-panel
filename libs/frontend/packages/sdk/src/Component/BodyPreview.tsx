import {CodeHighlight} from '@app-dev-panel/sdk/Component/CodeHighlight';
import {JsonRenderer} from '@app-dev-panel/sdk/Component/JsonRenderer';
import {ExpandLess, ExpandMore} from '@mui/icons-material';
import {Box, Collapse, IconButton, Typography} from '@mui/material';
import {useState} from 'react';

type BodyPreviewProps = {
    /** The raw body string */
    body: string;
    /** Content-Type header value (e.g. "text/html; charset=UTF-8") */
    contentType?: string;
    /** Section title */
    title?: string;
    /** Whether the body starts collapsed. Defaults to true when body > 500 chars. */
    defaultCollapsed?: boolean;
};

/** Map Content-Type header to a syntax highlight language */
function detectLanguage(contentType?: string): string {
    if (!contentType) return 'plain';
    const ct = contentType.toLowerCase();
    if (ct.includes('json')) return 'json';
    if (ct.includes('html')) return 'html';
    if (ct.includes('xml') || ct.includes('svg')) return 'xml';
    if (ct.includes('javascript') || ct.includes('ecmascript')) return 'javascript';
    if (ct.includes('css')) return 'css';
    if (ct.includes('yaml') || ct.includes('yml')) return 'yaml';
    if (ct.includes('sql')) return 'sql';
    return 'plain';
}

function tryParseJson(body: string): unknown | null {
    try {
        return JSON.parse(body);
    } catch {
        return null;
    }
}

function tryPrettyPrint(body: string, language: string): string {
    if (language === 'json') {
        try {
            return JSON.stringify(JSON.parse(body), null, 2);
        } catch {
            return body;
        }
    }
    return body;
}

export const BodyPreview = ({body, contentType, title = 'Body', defaultCollapsed}: BodyPreviewProps) => {
    const autoCollapse = defaultCollapsed ?? body.length > 500;
    const [collapsed, setCollapsed] = useState(autoCollapse);
    const language = detectLanguage(contentType);
    const isJson = language === 'json';

    // For JSON, try to render as interactive JsonRenderer
    const parsedJson = isJson ? tryParseJson(body) : null;
    const displayBody = tryPrettyPrint(body, language);

    return (
        <Box>
            <Box
                onClick={() => setCollapsed(!collapsed)}
                sx={{display: 'flex', alignItems: 'center', cursor: 'pointer', gap: 0.5, '&:hover': {opacity: 0.8}}}
            >
                <IconButton size="small" sx={{p: 0.25}}>
                    {collapsed ? <ExpandMore sx={{fontSize: 18}} /> : <ExpandLess sx={{fontSize: 18}} />}
                </IconButton>
                <Typography
                    variant="caption"
                    sx={{fontWeight: 600, color: 'text.disabled', textTransform: 'uppercase', letterSpacing: 0.5}}
                >
                    {title}
                </Typography>
                {language !== 'plain' && (
                    <Typography variant="caption" sx={{color: 'text.disabled', ml: 0.5}}>
                        ({language})
                    </Typography>
                )}
                <Typography variant="caption" sx={{color: 'text.disabled', ml: 'auto'}}>
                    {formatSize(body.length)}
                </Typography>
            </Box>
            <Collapse in={!collapsed}>
                <Box
                    sx={{
                        mt: 0.5,
                        borderRadius: 1,
                        overflow: 'hidden',
                        border: '1px solid',
                        borderColor: 'divider',
                        maxHeight: 600,
                        overflowY: 'auto',
                    }}
                >
                    {parsedJson !== null && typeof parsedJson === 'object' ? (
                        <Box sx={{p: 1.5}}>
                            <JsonRenderer value={parsedJson} />
                        </Box>
                    ) : (
                        <CodeHighlight code={displayBody} language={language} showLineNumbers={false} />
                    )}
                </Box>
            </Collapse>
        </Box>
    );
};

function formatSize(bytes: number): string {
    if (bytes < 1024) return `${bytes} B`;
    if (bytes < 1024 * 1024) return `${(bytes / 1024).toFixed(1)} KB`;
    return `${(bytes / (1024 * 1024)).toFixed(1)} MB`;
}
