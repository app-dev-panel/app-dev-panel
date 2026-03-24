import {useBreadcrumbs} from '@app-dev-panel/panel/Application/Context/BreadcrumbsContext';
import {PageHeader} from '@app-dev-panel/sdk/Component/PageHeader';
import ContentCopyIcon from '@mui/icons-material/ContentCopy';
import {Box, Chip, IconButton, Paper, ToggleButton, ToggleButtonGroup, Tooltip, Typography} from '@mui/material';
import {styled} from '@mui/material/styles';
import {useCallback, useMemo, useState} from 'react';
import {useSelector} from 'react-redux';

type ConfigTab = 'url' | 'stdio' | 'cli';

const mcpTools = [
    'list_debug_entries',
    'view_debug_entry',
    'search_logs',
    'analyze_exception',
    'view_database_queries',
    'view_timeline',
];

const CodeBlock = styled('pre')(({theme}) => ({
    margin: 0,
    padding: theme.spacing(1.5),
    borderRadius: theme.shape.borderRadius,
    backgroundColor: theme.palette.mode === 'dark' ? theme.palette.background.default : theme.palette.grey[50],
    border: `1px solid ${theme.palette.divider}`,
    fontSize: theme.typography.body2.fontSize,
    fontFamily: "'JetBrains Mono', monospace",
    lineHeight: 1.6,
    overflowX: 'auto',
    whiteSpace: 'pre',
    position: 'relative',
}));

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
    const baseUrl = useSelector((state) => (state as {application: {baseUrl: string}}).application.baseUrl) as string;
    const [tab, setTab] = useState<ConfigTab>('url');
    const [copied, setCopied] = useState(false);

    useBreadcrumbs(() => ['MCP']);

    const mcpUrl = useMemo(() => {
        const base = (baseUrl || window.location.origin).replace(/\/$/, '');
        return `${base}/inspect/api/mcp`;
    }, [baseUrl]);

    const config = useMemo(() => buildConfig(tab, mcpUrl), [tab, mcpUrl]);

    const handleCopy = useCallback(async () => {
        await navigator.clipboard.writeText(config);
        setCopied(true);
        setTimeout(() => setCopied(false), 2000);
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
                            sx={{fontFamily: "'JetBrains Mono', monospace", wordBreak: 'break-all'}}
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
                        <CodeBlock>{config}</CodeBlock>
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

                <Paper variant="outlined" sx={{p: 2, display: 'flex', flexDirection: 'column', gap: 1.5}}>
                    <Typography variant="body1" fontWeight={600}>
                        Available tools
                    </Typography>
                    <Box sx={{display: 'flex', flexWrap: 'wrap', gap: 0.75}}>
                        {mcpTools.map((tool) => (
                            <Chip
                                key={tool}
                                label={tool}
                                size="small"
                                variant="outlined"
                                sx={{fontFamily: "'JetBrains Mono', monospace", fontSize: '12px'}}
                            />
                        ))}
                    </Box>
                </Paper>
            </Box>
        </>
    );
};
