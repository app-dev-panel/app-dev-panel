import {SettingsDialog} from '@app-dev-panel/panel/Module/OpenApi/Component/SettingsDialog';
import {useOpenApiEntries} from '@app-dev-panel/panel/Module/OpenApi/Context/Context';
import '@app-dev-panel/panel/Module/OpenApi/Pages/dark.css';
import {EmptyState} from '@app-dev-panel/sdk/Component/EmptyState';
import {PageHeader} from '@app-dev-panel/sdk/Component/PageHeader';
import {Settings} from '@mui/icons-material';
import {TabContext, TabPanel} from '@mui/lab';
import {IconButton, Stack, Tab, Tabs, useTheme} from '@mui/material';
import * as React from 'react';
import {useEffect, useState} from 'react';
import {useSearchParams} from 'react-router';
import SwaggerUI from 'swagger-ui-react';
import 'swagger-ui-react/swagger-ui.css';

const NoEntries = React.memo(() => (
    <EmptyState
        icon="data_object"
        title="No Open API entries found"
        description="Click the settings button to add a new Open API entry."
    />
));
export const Layout = () => {
    const [searchParams, setSearchParams] = useSearchParams();
    const requestedTab = searchParams.get('tab') ?? '';
    const [tab, setTab] = useState<string>(requestedTab);
    const [settingsPopupOpen, setSettingsPopupOpen] = useState<boolean>(false);
    const handleChange = (_event: React.SyntheticEvent, value: string) => {
        setTab(value);
        setSearchParams({tab: value});
    };
    const theme = useTheme();

    const apiEntries = useOpenApiEntries();

    useEffect(() => {
        const entryKeys = apiEntries ? Object.keys(apiEntries) : [];
        if (!entryKeys.length) return;
        if (requestedTab && entryKeys.includes(requestedTab)) {
            setTab(requestedTab);
        } else if (!tab || !entryKeys.includes(tab)) {
            setTab(entryKeys[0]);
        }
    }, [apiEntries, requestedTab, tab]);

    return (
        <>
            <PageHeader title="Open API" icon="data_object" description="API documentation viewer" />
            <TabContext value={tab}>
                <Stack>
                    <Stack direction="row" justifyContent="space-between">
                        <Tabs
                            value={tab}
                            onChange={handleChange}
                            scrollButtons="auto"
                            variant="scrollable"
                            allowScrollButtonsMobile
                            sx={{maxWidth: '100%'}}
                        >
                            {Object.keys(apiEntries).map((name) => (
                                <Tab key={name} label={name} value={name} wrapped />
                            ))}
                        </Tabs>
                        <IconButton onClick={() => setSettingsPopupOpen(true)} aria-label="Open API settings">
                            <Settings />
                        </IconButton>
                    </Stack>
                    {Object.keys(apiEntries).length === 0 ? (
                        <NoEntries />
                    ) : (
                        Object.entries(apiEntries).map(([name, url]) => (
                            <TabPanel key={name} value={name} className={theme.palette.mode} sx={{p: 0}}>
                                <SwaggerUI url={url} />
                            </TabPanel>
                        ))
                    )}
                </Stack>
            </TabContext>
            {settingsPopupOpen && (
                <SettingsDialog
                    onClose={() => {
                        setSettingsPopupOpen(false);
                    }}
                />
            )}
        </>
    );
};
