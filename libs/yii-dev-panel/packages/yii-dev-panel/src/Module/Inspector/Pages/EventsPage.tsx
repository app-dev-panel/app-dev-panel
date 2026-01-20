import {OpenInNew} from '@mui/icons-material';
import {TabContext, TabPanel} from '@mui/lab';
import TabList from '@mui/lab/TabList';
import {Button, IconButton, List, ListItem, Tab, Tooltip, Typography} from '@mui/material';
import Box from '@mui/material/Box';
import {CodeHighlight} from '@yiisoft/yii-dev-panel-sdk/Component/CodeHighlight';
import {FullScreenCircularProgress} from '@yiisoft/yii-dev-panel-sdk/Component/FullScreenCircularProgress';
import {serializeCallable} from '@yiisoft/yii-dev-panel-sdk/Helper/callableSerializer';
import {
    EventListenersType,
    EventsResponse,
    useGetEventsQuery,
} from '@yiisoft/yii-dev-panel/Module/Inspector/API/Inspector';
import React, {SyntheticEvent, useEffect, useState} from 'react';
import {useBreadcrumbs} from '@yiisoft/yii-dev-panel/Application/Context/BreadcrumbsContext';

type EventListenersProps = {
    eventListeners: EventListenersType;
};
const EventListeners = React.memo(({eventListeners}: EventListenersProps) => {
    const events = Object.entries(eventListeners);
    return (
        <>
            {events.map((event, index) => (
                <React.Fragment key={index}>
                    <Typography variant="subtitle2" component="h3">
                        {event[0]}
                        <Tooltip title="Open in File Explorer">
                            <IconButton size="small" href={`/inspector/files?class=${event[0]}`}>
                                <OpenInNew fontSize="small" />
                            </IconButton>
                        </Tooltip>
                    </Typography>

                    <List>
                        {event[1].map((item: any, index) => (
                            <React.Fragment key={index}>
                                {Array.isArray(item) && (
                                    <Tooltip title="Open in File Explorer">
                                        <Button
                                            size="small"
                                            href={`/inspector/files?class=${item[0]}&method=${item[1]}`}
                                            endIcon={<OpenInNew fontSize="small" />}
                                        >
                                            Inspect method
                                        </Button>
                                    </Tooltip>
                                )}
                                <ListItem disablePadding>
                                    <CodeHighlight
                                        language={'php'}
                                        code={serializeCallable(item)}
                                        showLineNumbers={false}
                                    />
                                </ListItem>
                            </React.Fragment>
                        ))}
                    </List>
                </React.Fragment>
            ))}
        </>
    );
});

const NoEventListenersFound = React.memo(() => {
    return (
        <Typography variant="subtitle2" component="h3">
            No event listeners found
        </Typography>
    );
});
type TabValue = 'common' | 'web' | 'console';
export const EventsPage = () => {
    const {data, isLoading, isSuccess} = useGetEventsQuery();
    const [events, setEvents] = useState<EventsResponse>(null);
    const [tabValue, setTabValue] = useState<TabValue>('web');
    const handleChange = (event: SyntheticEvent, newValue: TabValue) => setTabValue(newValue);

    useEffect(() => {
        if (!isSuccess) {
            return;
        }
        console.log(data);

        // @ts-ignore
        setEvents(data);
    }, [isSuccess, data]);

    useBreadcrumbs(() => ['Inspector', 'Event listeners']);

    if (isLoading) {
        return <FullScreenCircularProgress />;
    }

    return (
        <>
            {!events && <NoEventListenersFound />}
            {events && (
                <TabContext value={tabValue}>
                    <Box sx={{borderBottom: 1, borderColor: 'divider'}}>
                        <TabList onChange={handleChange}>
                            <Tab value="common" label="Common" disabled={!events.common} />
                            <Tab value="web" label="Web" disabled={!events.web} />
                            <Tab value="console" label="Console" disabled={!events.console} />
                        </TabList>
                    </Box>
                    <TabPanel value="common" sx={{px: 0}}>
                        {events.common && !Array.isArray(events.common) ? (
                            <EventListeners eventListeners={events.common} />
                        ) : (
                            <NoEventListenersFound />
                        )}
                    </TabPanel>
                    <TabPanel value="web" sx={{px: 0}}>
                        {events.web && !Array.isArray(events.web) ? (
                            <EventListeners eventListeners={events.web} />
                        ) : (
                            <NoEventListenersFound />
                        )}
                    </TabPanel>
                    <TabPanel value="console" sx={{px: 0}}>
                        {events.console && !Array.isArray(events.console) ? (
                            <EventListeners eventListeners={events.console} />
                        ) : (
                            <NoEventListenersFound />
                        )}
                    </TabPanel>
                </TabContext>
            )}
        </>
    );
};
