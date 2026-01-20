import {TabContext, TabPanel} from '@mui/lab';
import TabList from '@mui/lab/TabList';
import {Tab} from '@mui/material';
import Box from '@mui/material/Box';
import {DataContextProvider} from '@yiisoft/yii-dev-panel/Module/Inspector/Context/DataContext';
import * as Pages from '@yiisoft/yii-dev-panel/Module/Inspector/Pages';
import {ContainerPage} from '@yiisoft/yii-dev-panel/Module/Inspector/Pages/Config/ContainerPage';
import {SyntheticEvent, useState} from 'react';
import {useNavigate, useParams} from 'react-router-dom';
import {useBreadcrumbs} from '@yiisoft/yii-dev-panel/Application/Context/BreadcrumbsContext';

type TabValue = 'container' | 'parameters' | 'definitions';
export const ConfigurationPage = () => {
    const {page} = useParams();
    const navigate = useNavigate();
    const [tabValue, setTabValue] = useState<TabValue>((page as TabValue | undefined) ?? 'parameters');
    const handleChange = (event: SyntheticEvent, newValue: TabValue) => {
        setTabValue(newValue);
        navigate(`/inspector/config/${newValue}`);
    };

    useBreadcrumbs(() => ['Inspector', 'Config']);

    return (
        <>
            <TabContext value={tabValue}>
                <Box sx={{borderBottom: 1, borderColor: 'divider'}}>
                    <TabList onChange={handleChange}>
                        <Tab value="parameters" label="Parameters" />
                        <Tab value="definitions" label="Definitions" />
                        <Tab value="container" label="Container" />
                    </TabList>
                </Box>
                <TabPanel value="container">
                    <DataContextProvider>
                        <ContainerPage />
                    </DataContextProvider>
                </TabPanel>
                <TabPanel value="parameters">
                    <Pages.ParametersPage />
                </TabPanel>
                <TabPanel value="definitions">
                    <DataContextProvider>
                        <Pages.DefinitionsPage />
                    </DataContextProvider>
                </TabPanel>
            </TabContext>
        </>
    );
};
