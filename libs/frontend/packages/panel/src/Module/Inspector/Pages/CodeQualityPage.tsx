import {AnalysePage} from '@app-dev-panel/panel/Module/Inspector/Pages/AnalysePage';
import {CodeCoveragePage} from '@app-dev-panel/panel/Module/Inspector/Pages/CodeCoveragePage';
import {TestsPage} from '@app-dev-panel/panel/Module/Inspector/Pages/TestsPage';
import {PageHeader} from '@app-dev-panel/sdk/Component/PageHeader';
import {Box, Tab, Tabs} from '@mui/material';
import {useCallback} from 'react';
import {useSearchParams} from 'react-router';

const tabs = ['tests', 'analyse', 'coverage'] as const;
type TabKey = (typeof tabs)[number];

const tabLabels: Record<TabKey, string> = {tests: 'Tests', analyse: 'Analyse', coverage: 'Coverage'};

export const CodeQualityPage = () => {
    const [searchParams, setSearchParams] = useSearchParams();
    const activeTab = (searchParams.get('tab') as TabKey) || 'tests';
    const tabIndex = Math.max(tabs.indexOf(activeTab), 0);

    const handleTabChange = useCallback(
        (_: React.SyntheticEvent, index: number) => {
            setSearchParams({tab: tabs[index]}, {replace: true});
        },
        [setSearchParams],
    );

    return (
        <>
            <PageHeader title="Code Quality" icon="verified" description="Tests, static analysis, and code coverage" />
            <Box sx={{borderBottom: 1, borderColor: 'divider', mb: 2}}>
                <Tabs value={tabIndex} onChange={handleTabChange}>
                    {tabs.map((key) => (
                        <Tab key={key} label={tabLabels[key]} />
                    ))}
                </Tabs>
            </Box>
            {activeTab === 'tests' && <TestsPage showHeader={false} />}
            {activeTab === 'analyse' && <AnalysePage showHeader={false} />}
            {activeTab === 'coverage' && <CodeCoveragePage showHeader={false} />}
        </>
    );
};
