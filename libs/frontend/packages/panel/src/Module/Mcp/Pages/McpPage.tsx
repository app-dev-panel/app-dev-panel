import {useBreadcrumbs} from '@app-dev-panel/panel/Application/Context/BreadcrumbsContext';
import {useSelector} from '@app-dev-panel/panel/store';
import {CodeHighlight} from '@app-dev-panel/sdk/Component/CodeHighlight';
import {PageHeader} from '@app-dev-panel/sdk/Component/PageHeader';
import {primitives} from '@app-dev-panel/sdk/Component/Theme/tokens';
import ContentCopyIcon from '@mui/icons-material/ContentCopy';
import {
    Box,
    IconButton,
    Paper,
    Table,
    TableBody,
    TableCell,
    TableContainer,
    TableHead,
    TableRow,
    ToggleButton,
    ToggleButtonGroup,
    Tooltip,
    Typography,
} from '@mui/material';
import {useCallback, useEffect, useMemo, useRef, useState} from 'react';

type ConfigTab = 'url' | 'stdio' | 'cli';

const mcpTools = [
    {
        name: 'list_debug_entries',
        description:
            'List recent debug entries with summary info (ID, timestamp, HTTP method, URL, status code, duration, collectors).',
    },
    {
        name: 'view_debug_entry',
        description: 'View full collector data for a specific debug entry. Optionally filter by collector name.',
    },
    {
        name: 'search_logs',
        description: 'Search log messages across all debug entries. Matches against message text and context.',
    },
    {
        name: 'analyze_exception',
        description: 'Get exception details with full stack trace, related request info, and log messages.',
    },
    {
        name: 'view_database_queries',
        description: 'List SQL queries with timing, parameters, and row counts. Detects N+1 and slow queries.',
    },
    {
        name: 'view_timeline',
        description: 'View the performance timeline — chronological events from all collectors with timestamps.',
    },
];

const buildConfig = (tab: ConfigTab, mcpUrl: string): string => {
    if (tab === 'url') {
        return JSON.stringify({mcpServers: {AppDevPanel: {url: mcpUrl}}}, null, 2);
    }
    if (tab === 'stdio') {
        return JSON.stringify(
            {mcpServers: {AppDevPanel: {command: 'npx', args: ['-y', 'mcp-remote', mcpUrl]}}},
            null,
            2,
        );
    }
    return JSON.stringify(
        {mcpServers: {AppDevPanel: {command: 'php', args: ['vendor/bin/adp-mcp', '--storage=/path/to/debug-data']}}},
        null,
        2,
    );
};

export const McpPage = () => {
    const baseUrl = useSelector((state) => state.application.baseUrl) as string;
    const [tab, setTab] = useState<ConfigTab>('url');
    const [copied, setCopied] = useState(false);
    const copyTimerRef = useRef<ReturnType<typeof setTimeout>>();

    useEffect(() => () => clearTimeout(copyTimerRef.current), []);

    useBreadcrumbs(() => ['MCP']);

    const mcpUrl = useMemo(() => {
        const base = (baseUrl || window.location.origin).replace(/\/$/, '');
        return `${base}/inspect/api/mcp`;
    }, [baseUrl]);

    const config = useMemo(() => buildConfig(tab, mcpUrl), [tab, mcpUrl]);

    const handleCopy = useCallback(async () => {
        await navigator.clipboard.writeText(config);
        setCopied(true);
        clearTimeout(copyTimerRef.current);
        copyTimerRef.current = setTimeout(() => setCopied(false), 2000);
    }, [config]);

    return (
        <>
            <PageHeader title="MCP Server" icon="hub" description="Connect AI assistants to your debug data" />
            <Box sx={{display: 'flex', flexDirection: 'column', gap: 3}}>
                <Paper variant="outlined" sx={{p: 2, display: 'flex', flexDirection: 'column', gap: 2}}>
                    <Box>
                        <Typography variant="body2" color="text.secondary" sx={{mb: 0.5}}>
                            Endpoint
                        </Typography>
                        <Typography
                            variant="body2"
                            sx={{fontFamily: primitives.fontFamilyMono, wordBreak: 'break-all'}}
                        >
                            {mcpUrl}
                        </Typography>
                    </Box>

                    <ToggleButtonGroup
                        value={tab}
                        exclusive
                        onChange={(_, value) => value && setTab(value)}
                        size="small"
                        fullWidth
                    >
                        <ToggleButton value="url">Direct URL</ToggleButton>
                        <ToggleButton value="stdio">stdio</ToggleButton>
                        <ToggleButton value="cli">CLI</ToggleButton>
                    </ToggleButtonGroup>

                    <Box sx={{position: 'relative'}}>
                        <CodeHighlight language="json" code={config} showLineNumbers={false} fontSize={10} />
                        <Tooltip title={copied ? 'Copied' : 'Copy'}>
                            <IconButton
                                size="small"
                                onClick={handleCopy}
                                sx={{position: 'absolute', top: 4, right: 4, bgcolor: 'background.paper'}}
                            >
                                <ContentCopyIcon sx={{fontSize: 16}} />
                            </IconButton>
                        </Tooltip>
                    </Box>

                    <Typography variant="body2" color="text.secondary">
                        Add this to your AI assistant's MCP configuration file (e.g., claude_desktop_config.json,
                        .cursor/mcp.json, or .claude/settings.json).
                    </Typography>
                </Paper>

                <Paper variant="outlined" sx={{overflow: 'hidden'}}>
                    <Typography variant="body1" fontWeight={600} sx={{px: 2, pt: 2, pb: 1}}>
                        Available tools
                    </Typography>
                    <TableContainer>
                        <Table size="small">
                            <TableHead>
                                <TableRow>
                                    <TableCell sx={{fontWeight: 600}}>Tool</TableCell>
                                    <TableCell sx={{fontWeight: 600}}>Description</TableCell>
                                </TableRow>
                            </TableHead>
                            <TableBody>
                                {mcpTools.map((tool) => (
                                    <TableRow key={tool.name}>
                                        <TableCell
                                            sx={{
                                                fontFamily: primitives.fontFamilyMono,
                                                fontSize: '12px',
                                                whiteSpace: 'nowrap',
                                            }}
                                        >
                                            {tool.name}
                                        </TableCell>
                                        <TableCell>
                                            <Typography variant="body2" color="text.secondary">
                                                {tool.description}
                                            </Typography>
                                        </TableCell>
                                    </TableRow>
                                ))}
                            </TableBody>
                        </Table>
                    </TableContainer>
                </Paper>
            </Box>
        </>
    );
};
