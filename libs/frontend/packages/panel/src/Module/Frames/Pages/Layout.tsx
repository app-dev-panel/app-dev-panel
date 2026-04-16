import {SettingsDialog} from '@app-dev-panel/panel/Module/Frames/Component/SettingsDialog';
import {useFramesEntries} from '@app-dev-panel/panel/Module/Frames/Context/Context';
import {DuckIcon} from '@app-dev-panel/sdk/Component/DuckIcon';
import {InfoBox} from '@app-dev-panel/sdk/Component/InfoBox';
import {PageHeader} from '@app-dev-panel/sdk/Component/PageHeader';
import {Settings} from '@mui/icons-material';
import {TabContext, TabPanel} from '@mui/lab';
import {Box, IconButton, Link, Stack, Tab, Tabs, Typography, useTheme} from '@mui/material';
import * as React from 'react';
import {useEffect, useState} from 'react';
import {useSearchParams} from 'react-router';

const PoliciesList = () => {
    return (
        <ul>
            <li>
                <Link href="https://developer.mozilla.org/en-US/docs/Web/HTTP/Headers/X-Frame-Options" target="_blank">
                    X-Frame-Options
                </Link>
            </li>
            <li>
                <Link href="https://developer.mozilla.org/en-US/docs/Web/HTTP/CORS" target="_blank">
                    CORS
                </Link>
            </li>
            <li>
                <Link
                    href="https://developer.mozilla.org/en-US/docs/Web/HTTP/Headers/Content-Security-Policy/Sources"
                    target="_blank"
                >
                    Content-Security-Policy
                </Link>
            </li>
        </ul>
    );
};
type ErrorResolutionBoxProps = {url: string};
const ErrorResolutionBox = ({url}: ErrorResolutionBoxProps) => {
    return (
        <InfoBox
            title={`"${url}" is inaccessible`}
            text={
                <>
                    <Typography>Having problems with X-Frame-Options or CORS?</Typography>
                    <Typography>
                        Configure response headers to prevent browser blocking the requests to external resources or set
                        up a proxy server.
                    </Typography>
                    <Typography>
                        Read more about blocking external resources:
                        <PoliciesList />
                    </Typography>
                </>
            }
            severity="info"
            icon={<DuckIcon />}
        />
    );
};

const NoEntries = React.memo(() => {
    return (
        <InfoBox
            title="No frames found"
            text={
                <>
                    <Typography>You can add any external resources as a embed and manage them there.</Typography>
                    <Typography>
                        Due to multiple privacy policies some of frames cannot be opened. Read more about the policies:
                        <PoliciesList />
                    </Typography>
                    <Typography>Click on settings button and add a frame.</Typography>
                </>
            }
            severity="info"
            icon={<DuckIcon />}
        />
    );
});
export const Layout = () => {
    const [searchParams] = useSearchParams();
    const requestedTab = searchParams.get('tab') ?? '';
    const [tab, setTab] = useState<string>('');
    const [settingsPopupOpen, setSettingsPopupOpen] = useState<boolean>(false);
    const handleChange = (_event: React.SyntheticEvent, value: string) => setTab(value);
    const theme = useTheme();

    const frames = useFramesEntries();
    const focusedFrame = requestedTab && frames[requestedTab] ? requestedTab : null;

    useEffect(() => {
        if (focusedFrame) return;
        const frameKeys = frames ? Object.keys(frames) : [];
        if (!frameKeys.length) return;
        if (!tab || !frameKeys.includes(tab)) {
            setTab(frameKeys[0]);
        }
    }, [frames, focusedFrame, tab]);

    // Focused mode: a single frame is opened directly from the sidebar submenu.
    // Render only the embedded content, filling the whole container.
    if (focusedFrame) {
        const url = frames[focusedFrame];
        return (
            <Box className={theme.palette.mode} sx={{width: '100%', height: 'calc(100vh - 140px)', display: 'flex'}}>
                <object data={url} width="100%" height="100%" type="text/html" style={{flex: 1}}>
                    <ErrorResolutionBox url={url} />
                </object>
            </Box>
        );
    }

    return (
        <>
            <PageHeader title="Frames" icon="web" description="Embedded external resources" />
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
                            {Object.keys(frames).map((name) => (
                                <Tab key={name} label={name} value={name} wrapped />
                            ))}
                        </Tabs>
                        <IconButton onClick={() => setSettingsPopupOpen(true)} aria-label="Frames settings">
                            <Settings />
                        </IconButton>
                    </Stack>
                    {Object.keys(frames).length === 0 ? (
                        <NoEntries />
                    ) : (
                        <>
                            {Object.entries(frames).map(([name, url]) => (
                                <TabPanel key={name} value={name} className={theme.palette.mode}>
                                    {/*<iframe src={url} width="100%" height="1000px" />*/}
                                    <object data={url} width="100%" height="1000px" type="text/html">
                                        <ErrorResolutionBox url={url} />
                                    </object>
                                </TabPanel>
                            ))}
                        </>
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
