import {DataContextProvider} from '@app-dev-panel/panel/Module/Inspector/Context/DataContext';
import * as Pages from '@app-dev-panel/panel/Module/Inspector/Pages';
import {ContainerPage} from '@app-dev-panel/panel/Module/Inspector/Pages/Config/ContainerPage';
import {PageHeader} from '@app-dev-panel/sdk/Component/PageHeader';
import {TabContext, TabPanel} from '@mui/lab';
import TabList from '@mui/lab/TabList';
import {Box, Tab} from '@mui/material';
import {SyntheticEvent, useState} from 'react';
import {useNavigate, useParams} from 'react-router';

type TabValue = 'container' | 'parameters' | 'definitions';
export const ConfigurationPage = () => {
    const {page} = useParams();
    const navigate = useNavigate();
    const [tabValue, setTabValue] = useState<TabValue>((page as TabValue | undefined) ?? 'parameters');
    const handleChange = (event: SyntheticEvent, newValue: TabValue) => {
        setTabValue(newValue);
        navigate(`/inspector/config/${newValue}`);
    };

    return (
        <>
            <PageHeader
                title="Configuration"
                icon="settings"
                description="Application parameters, DI definitions and resolved container entries"
            />
            <TabContext value={tabValue}>
                <Box sx={{borderBottom: 1, borderColor: 'divider'}}>
                    <TabList onChange={handleChange}>
                        <Tab value="parameters" label="Parameters" />
                        <Tab value="definitions" label="Definitions" />
                        <Tab value="container" label="Container" />
                    </TabList>
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
