import {useBreadcrumbs} from '@app-dev-panel/panel/Application/Context/BreadcrumbsContext';
import {EventEntry, EventListenersType, useGetEventsQuery} from '@app-dev-panel/panel/Module/Inspector/API/Inspector';
import {CodeHighlight} from '@app-dev-panel/sdk/Component/CodeHighlight';
import {EmptyState} from '@app-dev-panel/sdk/Component/EmptyState';
import {FilterInput} from '@app-dev-panel/sdk/Component/FilterInput';
import {FullScreenCircularProgress} from '@app-dev-panel/sdk/Component/FullScreenCircularProgress';
import {PageHeader} from '@app-dev-panel/sdk/Component/PageHeader';
import {serializeCallable} from '@app-dev-panel/sdk/Helper/callableSerializer';
import {searchVariants} from '@app-dev-panel/sdk/Helper/layoutTranslit';
import {regexpQuote} from '@app-dev-panel/sdk/Helper/regexpQuote';
import {OpenInNew} from '@mui/icons-material';
import {TabContext, TabPanel} from '@mui/lab';
import TabList from '@mui/lab/TabList';
import {Box, Button, Chip, IconButton, Tab, Tooltip, Typography} from '@mui/material';
import {styled} from '@mui/material/styles';
import React, {SyntheticEvent, useCallback, useMemo, useState} from 'react';
import {useSearchParams} from 'react-router-dom';

// ---------------------------------------------------------------------------
// Helpers
// ---------------------------------------------------------------------------

/**
 * Normalize EventListenersType (supports both new structured format and legacy Record format).
 */
function normalizeEntries(data: EventListenersType | null): EventEntry[] {
    if (!data) return [];
    if (Array.isArray(data)) return data;
    // Legacy Record<string, Array<...>> format
    return Object.entries(data).map(([name, listeners]) => ({name, class: null, listeners}));
}

function shortClassName(fqcn: string): string {
    const parts = fqcn.split('\\');
    return parts[parts.length - 1];
}

// ---------------------------------------------------------------------------
// Styled components
// ---------------------------------------------------------------------------

const SearchRow = styled(Box)(({theme}) => ({
    display: 'flex',
    alignItems: 'center',
    gap: theme.spacing(1.5),
    marginBottom: theme.spacing(2),
}));

const EventCard = styled(Box)(({theme}) => ({
    border: `1px solid ${theme.palette.divider}`,
    borderRadius: theme.shape.borderRadius,
    overflow: 'hidden',
    '&:not(:last-child)': {marginBottom: theme.spacing(1.5)},
}));

const EventHeader = styled(Box)(({theme}) => ({
    display: 'flex',
    alignItems: 'center',
    gap: theme.spacing(1),
    padding: theme.spacing(1.5, 2),
    backgroundColor: theme.palette.action.hover,
}));

const ListenerRow = styled(Box)(({theme}) => ({
    display: 'flex',
    alignItems: 'center',
    gap: theme.spacing(1),
    padding: theme.spacing(0.5, 2),
    borderTop: `1px solid ${theme.palette.divider}`,
    '&:hover': {backgroundColor: theme.palette.action.hover},
    '& .MuiButtonBase-root': {opacity: 0, transition: 'opacity 0.15s'},
    '&:hover .MuiButtonBase-root': {opacity: 1},
}));

// ---------------------------------------------------------------------------
// Sub-components
// ---------------------------------------------------------------------------

type EventListenersProps = {entries: EventEntry[]};

const EventListeners = React.memo(({entries}: EventListenersProps) => {
    if (entries.length === 0) {
        return <EmptyState icon="bolt" title="No event listeners found" />;
    }

    return (
        <>
            {entries.map((entry) => {
                const hasClass = !!entry.class;
                const nameEqualsClass = entry.class === entry.name;

                return (
                    <EventCard key={entry.name}>
                        <EventHeader>
                            <Typography sx={{fontWeight: 600, fontSize: '13px', flex: 1, wordBreak: 'break-all'}}>
                                {hasClass && !nameEqualsClass ? (
                                    <>
                                        {shortClassName(entry.class!)}
                                        <Typography
                                            component="span"
                                            sx={{fontSize: '12px', color: 'text.secondary', ml: 0.5}}
                                        >
                                            ({entry.name})
                                        </Typography>
                                    </>
                                ) : (
                                    entry.name
                                )}
                            </Typography>
                            <Chip
                                label={`${entry.listeners.length}`}
                                size="small"
                                sx={{
                                    fontSize: '10px',
                                    height: 20,
                                    minWidth: 24,
                                    borderRadius: 1,
                                    backgroundColor: 'action.selected',
                                }}
                            />
                            {hasClass && (
                                <Tooltip title="Open in File Explorer">
                                    <IconButton size="small" href={`/inspector/files?class=${entry.class}`}>
                                        <OpenInNew sx={{fontSize: 14}} />
                                    </IconButton>
                                </Tooltip>
                            )}
                        </EventHeader>
                        {entry.listeners.map((listener, i) => {
                            const isArray = Array.isArray(listener);
                            return (
                                <ListenerRow key={i}>
                                    <Box sx={{flex: 1, minWidth: 0, overflow: 'hidden'}}>
                                        <CodeHighlight
                                            language="php"
                                            code={serializeCallable(listener)}
                                            showLineNumbers={false}
                                        />
                                    </Box>
                                    {isArray && (
                                        <Tooltip title="Open in File Explorer">
                                            <Button
                                                size="small"
                                                href={`/inspector/files?class=${listener[0]}&method=${listener[1]}`}
                                                endIcon={<OpenInNew sx={{fontSize: 14}} />}
                                                sx={{flexShrink: 0, whiteSpace: 'nowrap'}}
                                            >
                                                Inspect
                                            </Button>
                                        </Tooltip>
                                    )}
                                </ListenerRow>
                            );
                        })}
                    </EventCard>
                );
            })}
        </>
    );
});

// ---------------------------------------------------------------------------
// Main component
// ---------------------------------------------------------------------------

type TabValue = 'common' | 'web' | 'console';

export const EventsPage = () => {
    const {data, isLoading} = useGetEventsQuery();
    const [tabValue, setTabValue] = useState<TabValue>('web');
    const [searchParams, setSearchParams] = useSearchParams();
    const filterValue = searchParams.get('filter') || '';

    const handleTabChange = (_event: SyntheticEvent, newValue: TabValue) => setTabValue(newValue);

    const onFilterChange = useCallback(
        (value: string) => {
            setSearchParams(value ? {filter: value} : {});
        },
        [setSearchParams],
    );

    const allEntries = useMemo(() => {
        if (!data) return {common: [] as EventEntry[], web: [] as EventEntry[], console: [] as EventEntry[]};
        return {
            common: normalizeEntries(data.common),
            web: normalizeEntries(data.web),
            console: normalizeEntries(data.console),
        };
    }, [data]);

    const filtered = useMemo(() => {
        const filter = filterValue.trim();
        if (!filter) return allEntries;

        const patterns = searchVariants(filter).map((v) => new RegExp(regexpQuote(v), 'i'));
        const filterEntries = (entries: EventEntry[]) =>
            entries.filter((e) =>
                patterns.some(
                    (re) =>
                        re.test(e.name) ||
                        (e.class && re.test(e.class)) ||
                        e.listeners.some((l) => re.test(serializeCallable(l))),
                ),
            );

        return {
            common: filterEntries(allEntries.common),
            web: filterEntries(allEntries.web),
            console: filterEntries(allEntries.console),
        };
    }, [allEntries, filterValue]);

    const totalCount = filtered.common.length + filtered.web.length + filtered.console.length;

    useBreadcrumbs(() => ['Inspector', 'Event Listeners']);

    if (isLoading) {
        return <FullScreenCircularProgress />;
    }

    if (!data) {
        return (
            <>
                <PageHeader title="Event Listeners" icon="bolt" description="View registered event listeners" />
                <EmptyState icon="bolt" title="No event listeners found" />
            </>
        );
    }

    return (
        <>
            <PageHeader title="Event Listeners" icon="bolt" description="View registered event listeners" />
            <SearchRow>
                <FilterInput value={filterValue} onChange={onFilterChange} placeholder="Search events..." />
                <Typography sx={{fontSize: '12px', color: 'text.disabled', whiteSpace: 'nowrap'}}>
                    {totalCount} events
                </Typography>
            </SearchRow>
            <TabContext value={tabValue}>
                <Box sx={{borderBottom: 1, borderColor: 'divider'}}>
                    <TabList onChange={handleTabChange}>
                        <Tab
                            value="common"
                            label={`Common (${filtered.common.length})`}
                            disabled={allEntries.common.length === 0}
                        />
                        <Tab
                            value="web"
                            label={`Web (${filtered.web.length})`}
                            disabled={allEntries.web.length === 0}
                        />
                        <Tab
                            value="console"
                            label={`Console (${filtered.console.length})`}
                            disabled={allEntries.console.length === 0}
                        />
                    </TabList>
                </Box>
                <TabPanel value="common" sx={{px: 0}}>
                    <EventListeners entries={filtered.common} />
                </TabPanel>
                <TabPanel value="web" sx={{px: 0}}>
                    <EventListeners entries={filtered.web} />
                </TabPanel>
                <TabPanel value="console" sx={{px: 0}}>
                    <EventListeners entries={filtered.console} />
                </TabPanel>
            </TabContext>
        </>
    );
};
