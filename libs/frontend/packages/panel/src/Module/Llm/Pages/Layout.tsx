import {useGetStatusQuery} from '@app-dev-panel/panel/Module/Llm/API/Llm';
import {AnalyzePanel} from '@app-dev-panel/panel/Module/Llm/Component/AnalyzePanel';
import {ChatPanel} from '@app-dev-panel/panel/Module/Llm/Component/ChatPanel';
import {ConnectionCard} from '@app-dev-panel/panel/Module/Llm/Component/ConnectionCard';
import {Box, Tab, Tabs} from '@mui/material';
import {useState} from 'react';

export const Layout = () => {
    const [tab, setTab] = useState(0);
    const {data: status} = useGetStatusQuery();
    const connected = status?.connected ?? false;

    return (
        <Box sx={{display: 'flex', flexDirection: 'column', gap: 3, p: {xs: 1.5, sm: 3.5}}}>
            <ConnectionCard />
            {connected && (
                <Box>
                    <Tabs
                        value={tab}
                        onChange={(_, v) => setTab(v)}
                        variant="scrollable"
                        scrollButtons="auto"
                        allowScrollButtonsMobile
                    >
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
            )}
        </Box>
    );
};
