import {ComposerPage} from '@app-dev-panel/panel/Module/Inspector/Pages/ComposerPage';
import {GitLogPage} from '@app-dev-panel/panel/Module/Inspector/Pages/Git/GitLogPage';
import {GitPage} from '@app-dev-panel/panel/Module/Inspector/Pages/Git/GitPage';
import {OpcachePage} from '@app-dev-panel/panel/Module/Inspector/Pages/OpcachePage';
import {PhpInfoPage} from '@app-dev-panel/panel/Module/Inspector/Pages/PhpInfoPage';
import {PageHeader} from '@app-dev-panel/sdk/Component/PageHeader';
import {Box, Tab, Tabs} from '@mui/material';
import {useCallback} from 'react';
import {useSearchParams} from 'react-router';

const tabs = ['phpinfo', 'composer', 'opcache', 'git', 'git-log'] as const;
type TabKey = (typeof tabs)[number];

const tabLabels: Record<TabKey, string> = {
    phpinfo: 'PHP Info',
    composer: 'Composer',
    opcache: 'Opcache',
    git: 'Git',
    'git-log': 'Git Log',
};

export const EnvironmentPage = () => {
    const [searchParams, setSearchParams] = useSearchParams();
    const activeTab = (searchParams.get('tab') as TabKey) || 'phpinfo';
    const tabIndex = Math.max(tabs.indexOf(activeTab), 0);

    const handleTabChange = useCallback(
        (_: React.SyntheticEvent, index: number) => {
            setSearchParams({tab: tabs[index]}, {replace: true});
        },
        [setSearchParams],
    );

    return (
        <>
            <PageHeader
                title="Environment"
                icon="settings_suggest"
                description="PHP runtime, packages, opcache, and version control"
            />
            <Box sx={{borderBottom: 1, borderColor: 'divider', mb: 2}}>
                <Tabs value={tabIndex} onChange={handleTabChange}>
                    {tabs.map((key) => (
                        <Tab key={key} label={tabLabels[key]} />
                    ))}
                </Tabs>
            </Box>
            {activeTab === 'phpinfo' && <PhpInfoPage />}
            {activeTab === 'composer' && <ComposerPage />}
            {activeTab === 'opcache' && <OpcachePage />}
            {activeTab === 'git' && <GitPage showHeader={false} />}
            {activeTab === 'git-log' && <GitLogPage showHeader={false} />}
        </>
    );
};
