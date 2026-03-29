import {AnalyzePanel} from '@app-dev-panel/panel/Module/Llm/Component/AnalyzePanel';
import {ChatPanel} from '@app-dev-panel/panel/Module/Llm/Component/ChatPanel';
import {ConnectionCard} from '@app-dev-panel/panel/Module/Llm/Component/ConnectionCard';
import {PageHeader} from '@app-dev-panel/sdk/Component/PageHeader';
import {Box, Tab, Tabs} from '@mui/material';
import {useState} from 'react';

export const Layout = () => {
    const [tab, setTab] = useState(0);

    return (
        <>
            <PageHeader title="LLM Integration" icon="psychology" description="AI-powered debug analysis" />
            <Box sx={{display: 'flex', flexDirection: 'column', gap: 3}}>
                <ConnectionCard />
                <Box>
                    <Tabs value={tab} onChange={(_, v) => setTab(v)}>
                        <Tab label="Chat" />
                        <Tab label="Analyze Debug Entry" />
                    </Tabs>
                    <Box sx={{mt: 2, display: tab === 0 ? 'block' : 'none'}}>
                        <ChatPanel />
                    </Box>
                    <Box sx={{mt: 2, display: tab === 1 ? 'block' : 'none'}}>
                        <AnalyzePanel />
                    </Box>
                </Box>
            </Box>
        </>
    );
};
