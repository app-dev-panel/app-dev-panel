import {CachePage} from '@app-dev-panel/panel/Module/Inspector/Pages/CachePage';
import {DatabasePage} from '@app-dev-panel/panel/Module/Inspector/Pages/DatabasePage';
import {ElasticsearchPage} from '@app-dev-panel/panel/Module/Inspector/Pages/ElasticsearchPage';
import {RedisPage} from '@app-dev-panel/panel/Module/Inspector/Pages/RedisPage';
import {PageHeader} from '@app-dev-panel/sdk/Component/PageHeader';
import {Box, Tab, Tabs} from '@mui/material';
import {useCallback} from 'react';
import {useSearchParams} from 'react-router';

const tabs = ['database', 'cache', 'redis', 'elasticsearch'] as const;
type TabKey = (typeof tabs)[number];

const tabLabels: Record<TabKey, string> = {
    database: 'Database',
    cache: 'Cache',
    redis: 'Redis',
    elasticsearch: 'Elasticsearch',
};

export const StoragePage = () => {
    const [searchParams, setSearchParams] = useSearchParams();
    const activeTab = (searchParams.get('tab') as TabKey) || 'database';
    const tabIndex = Math.max(tabs.indexOf(activeTab), 0);

    const handleTabChange = useCallback(
        (_: React.SyntheticEvent, index: number) => {
            setSearchParams({tab: tabs[index]}, {replace: true});
        },
        [setSearchParams],
    );

    return (
        <Box sx={{p: {xs: 1.5, sm: 3.5}}}>
            <PageHeader title="Storage" icon="storage" description="Database, cache, and data store management" />
            <Box sx={{borderBottom: 1, borderColor: 'divider', mb: 2}}>
                <Tabs value={tabIndex} onChange={handleTabChange}>
                    {tabs.map((key) => (
                        <Tab key={key} label={tabLabels[key]} />
                    ))}
                </Tabs>
            </Box>
            {activeTab === 'database' && <DatabasePage showHeader={false} />}
            {activeTab === 'cache' && <CachePage showHeader={false} />}
            {activeTab === 'redis' && <RedisPage showHeader={false} />}
            {activeTab === 'elasticsearch' && <ElasticsearchPage showHeader={false} />}
        </Box>
    );
};
