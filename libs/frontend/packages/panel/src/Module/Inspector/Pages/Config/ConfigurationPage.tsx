import {
    useGetClassesQuery,
    useGetConfigurationQuery,
    useGetParametersQuery,
} from '@app-dev-panel/panel/Module/Inspector/API/Inspector';
import {DataContextProvider} from '@app-dev-panel/panel/Module/Inspector/Context/DataContext';
import * as Pages from '@app-dev-panel/panel/Module/Inspector/Pages';
import {ContainerPage} from '@app-dev-panel/panel/Module/Inspector/Pages/Config/ContainerPage';
import {FilterInput} from '@app-dev-panel/sdk/Component/FilterInput';
import {PageHeader} from '@app-dev-panel/sdk/Component/PageHeader';
import {TabContext, TabPanel} from '@mui/lab';
import TabList from '@mui/lab/TabList';
import {Box, Tab, Typography} from '@mui/material';
import {SyntheticEvent, useCallback, useMemo, useState} from 'react';
import {useNavigate, useParams, useSearchParams} from 'react-router';

type TabValue = 'container' | 'parameters' | 'definitions';

function countParamLeaves(data: unknown): number {
    if (!data || typeof data !== 'object') return 0;
    let total = 0;
    for (const value of Object.values(data as Record<string, unknown>)) {
        if (value && typeof value === 'object' && !Array.isArray(value)) {
            total += Object.keys(value as Record<string, unknown>).length;
        } else {
            total += 1;
        }
    }
    return total;
}

export const ConfigurationPage = () => {
    const {page} = useParams();
    const navigate = useNavigate();
    const [tabValue, setTabValue] = useState<TabValue>((page as TabValue | undefined) ?? 'parameters');

    const [searchParams, setSearchParams] = useSearchParams();
    const filter = searchParams.get('filter') || '';

    const params = useGetParametersQuery();
    const definitions = useGetConfigurationQuery('di');
    const classes = useGetClassesQuery('');

    const paramCount = useMemo(() => countParamLeaves(params.data), [params.data]);
    const definitionCount = useMemo(
        () => (definitions.data ? Object.keys(definitions.data).length : 0),
        [definitions.data],
    );
    const classCount = classes.data?.length ?? 0;

    const handleTabChange = (_event: SyntheticEvent, newValue: TabValue) => {
        setTabValue(newValue);
        navigate(`/inspector/config/${newValue}${filter ? `?filter=${encodeURIComponent(filter)}` : ''}`);
    };

    const onFilterChange = useCallback(
        (value: string) => {
            setSearchParams(value ? {filter: value} : {});
        },
        [setSearchParams],
    );

    const totalCount = paramCount + definitionCount + classCount;

    return (
        <>
            <PageHeader
                title="Configuration"
                icon="settings"
                description="Application parameters, DI definitions and resolved container entries"
            />
            <TabContext value={tabValue}>
                <Box sx={{display: 'flex', alignItems: 'center', borderBottom: 1, borderColor: 'divider', mb: 2}}>
                    <TabList onChange={handleTabChange} sx={{flex: 1}}>
                        <Tab value="parameters" label={`Parameters (${paramCount})`} />
                        <Tab value="definitions" label={`Definitions (${definitionCount})`} />
                        <Tab value="container" label={`Container (${classCount})`} />
                    </TabList>
                    <Box sx={{display: 'flex', alignItems: 'center', gap: 1.5, pb: 0.5}}>
                        <Typography sx={{fontSize: '12px', color: 'text.disabled', whiteSpace: 'nowrap'}}>
                            {totalCount} entries
                        </Typography>
                        <FilterInput value={filter} onChange={onFilterChange} placeholder="Search configuration..." />
                    </Box>
                </Box>
                <TabPanel value="container" sx={{px: 0, py: 0}}>
                    <DataContextProvider>
                        <ContainerPage />
                    </DataContextProvider>
                </TabPanel>
                <TabPanel value="parameters" sx={{px: 0, py: 0}}>
                    <Pages.ParametersPage />
                </TabPanel>
                <TabPanel value="definitions" sx={{px: 0, py: 0}}>
                    <DataContextProvider>
                        <Pages.DefinitionsPage />
                    </DataContextProvider>
                </TabPanel>
            </TabContext>
        </>
    );
};
