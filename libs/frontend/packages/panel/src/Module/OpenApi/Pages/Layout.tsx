import {SettingsDialog} from '@app-dev-panel/panel/Module/OpenApi/Component/SettingsDialog';
import {useOpenApiEntries} from '@app-dev-panel/panel/Module/OpenApi/Context/Context';
import '@app-dev-panel/panel/Module/OpenApi/Pages/dark.css';
import {DuckIcon} from '@app-dev-panel/sdk/Component/DuckIcon';
import {EmptyState} from '@app-dev-panel/sdk/Component/EmptyState';
import {Settings} from '@mui/icons-material';
import {TabContext, TabPanel} from '@mui/lab';
import {Box, Button, IconButton, Stack, Tab, Tabs, useTheme} from '@mui/material';
import * as React from 'react';
import {useEffect, useState} from 'react';
import {useSearchParams} from 'react-router';
import SwaggerUI from 'swagger-ui-react';
import 'swagger-ui-react/swagger-ui.css';

type NoEntriesProps = {onAdd: () => void};
const NoEntries = React.memo(({onAdd}: NoEntriesProps) => (
    <EmptyState
        icon={<DuckIcon />}
        iconSize={150}
        title="No Open API entries yet"
        description="Add an Open API specification URL to browse and explore your endpoints in Swagger UI."
        action={
            <Button variant="contained" size="large" startIcon={<Settings />} onClick={onAdd}>
                Add entry
            </Button>
        }
        fillHeight
    />
));
export const Layout = () => {
    const [searchParams] = useSearchParams();
    const requestedTab = searchParams.get('tab') ?? '';
    const [tab, setTab] = useState<string>('');
    const [settingsPopupOpen, setSettingsPopupOpen] = useState<boolean>(false);
    const handleChange = (_event: React.SyntheticEvent, value: string) => setTab(value);
    const theme = useTheme();

    const apiEntries = useOpenApiEntries();
    const focusedEntry = requestedTab && apiEntries[requestedTab] ? requestedTab : null;
    const hasEntries = Object.keys(apiEntries).length > 0;
    const openSettings = () => setSettingsPopupOpen(true);

    useEffect(() => {
        if (focusedEntry) return;
        const entryKeys = apiEntries ? Object.keys(apiEntries) : [];
        if (!entryKeys.length) return;
        if (!tab || !entryKeys.includes(tab)) {
            setTab(entryKeys[0]);
        }
    }, [apiEntries, focusedEntry, tab]);

    // Focused mode: a single entry is opened directly from the sidebar submenu.
    // Render only the content, filling the whole container.
    if (focusedEntry) {
        return (
            <Box className={theme.palette.mode} sx={{width: '100%', height: '100%'}}>
                <SwaggerUI url={apiEntries[focusedEntry]} />
            </Box>
        );
    }

    return (
        <>
            {hasEntries ? (
                <TabContext value={tab}>
                    <Stack>
                        <Stack direction="row" justifyContent="space-between" sx={{px: 2, pt: 2}}>
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
                            <IconButton onClick={openSettings} aria-label="Open API settings">
                                <Settings />
                            </IconButton>
                        </Stack>
                        {Object.entries(apiEntries).map(([name, url]) => (
                            <TabPanel key={name} value={name} className={theme.palette.mode} sx={{p: 0}}>
                                <SwaggerUI url={url} />
                            </TabPanel>
                        ))}
                    </Stack>
                </TabContext>
            ) : (
                <NoEntries onAdd={openSettings} />
            )}
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
