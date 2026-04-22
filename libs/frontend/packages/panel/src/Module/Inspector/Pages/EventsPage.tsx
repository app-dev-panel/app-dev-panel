import {
    ClosureDescriptor,
    EventEntry,
    EventListener,
    EventListenersType,
    useGetEventsQuery,
} from '@app-dev-panel/panel/Module/Inspector/API/Inspector';
import {groupByNamespace, stripNamespace} from '@app-dev-panel/panel/Module/Inspector/Pages/Config/grouping';
import {CodeHighlight} from '@app-dev-panel/sdk/Component/CodeHighlight';
import {EmptyState} from '@app-dev-panel/sdk/Component/EmptyState';
import {FileLink} from '@app-dev-panel/sdk/Component/FileLink';
import {FilterInput} from '@app-dev-panel/sdk/Component/FilterInput';
import {FullScreenCircularProgress} from '@app-dev-panel/sdk/Component/FullScreenCircularProgress';
import {GroupCard} from '@app-dev-panel/sdk/Component/GroupCard';
import {PageHeader} from '@app-dev-panel/sdk/Component/PageHeader';
import {QueryErrorState} from '@app-dev-panel/sdk/Component/QueryErrorState';
import {serializeCallable} from '@app-dev-panel/sdk/Helper/callableSerializer';
import {searchVariants} from '@app-dev-panel/sdk/Helper/layoutTranslit';
import {regexpQuote} from '@app-dev-panel/sdk/Helper/regexpQuote';
import {ChevronRight, Code, ContentCopy, Description} from '@mui/icons-material';
import {TabContext, TabPanel} from '@mui/lab';
import TabList from '@mui/lab/TabList';
import {Box, Chip, Collapse, IconButton, Tab, Tooltip, Typography} from '@mui/material';
import {styled} from '@mui/material/styles';
import clipboardCopy from 'clipboard-copy';
import React, {SyntheticEvent, useCallback, useMemo, useState} from 'react';
import {Link as RouterLink, useSearchParams} from 'react-router';

// ---------------------------------------------------------------------------
// Helpers
// ---------------------------------------------------------------------------

function normalizeEntries(data: EventListenersType | null): EventEntry[] {
    if (!data) return [];
    if (Array.isArray(data)) return data;
    return Object.entries(data).map(([name, listeners]) => ({name, class: null, listeners}));
}

function isClosureDescriptor(value: unknown): value is ClosureDescriptor {
    return typeof value === 'object' && value !== null && '__closure' in value;
}

function getListenerSearchText(listener: EventListener): string {
    if (isClosureDescriptor(listener)) return listener.source;
    return serializeCallable(listener);
}

function parseCallable(value: any): {className: string; methodName: string} | null {
    if (Array.isArray(value) && value.length >= 2 && typeof value[0] === 'string' && typeof value[1] === 'string') {
        return {className: value[0], methodName: value[1]};
    }
    if (typeof value === 'string' && value.includes('::')) {
        const [className, methodName] = value.split('::', 2);
        if (className && methodName) return {className, methodName};
    }
    return null;
}

function isClassName(value: string): boolean {
    return value.includes('\\') && !value.includes(' ');
}

// ---------------------------------------------------------------------------
// Styled components
// ---------------------------------------------------------------------------

const ListenerRow = styled(Box)(({theme}) => ({
    display: 'flex',
    alignItems: 'flex-start',
    gap: theme.spacing(1),
    padding: theme.spacing(0.75, 2),
    '&:not(:first-of-type)': {borderTop: `1px solid ${theme.palette.divider}`},
    '&:hover': {backgroundColor: theme.palette.action.hover},
    '& .copy-btn': {opacity: 0, transition: 'opacity 0.15s'},
    '&:hover .copy-btn': {opacity: 1},
}));

const EventRowBox = styled(Box, {shouldForwardProp: (p) => p !== 'expanded'})<{expanded?: boolean}>(
    ({theme, expanded}) => ({
        borderTop: `1px solid ${theme.palette.divider}`,
        backgroundColor: expanded ? theme.palette.action.hover : 'transparent',
        transition: 'background-color 120ms',
        '& .event-row-actions': {opacity: 0, transition: 'opacity 0.15s'},
        '&:hover .event-row-actions': {opacity: 1},
    }),
);

const EventRowHead = styled(Box)(({theme}) => ({
    display: 'flex',
    alignItems: 'center',
    gap: theme.spacing(2),
    padding: theme.spacing(1, 2),
    cursor: 'pointer',
    '&:hover': {backgroundColor: theme.palette.action.hover},
}));

const EventExpandIndicator = styled(Box, {shouldForwardProp: (p) => p !== 'open'})<{open?: boolean}>(
    ({theme, open}) => ({
        width: 16,
        flexShrink: 0,
        display: 'flex',
        alignItems: 'center',
        justifyContent: 'center',
        color: theme.palette.text.disabled,
        transition: 'transform 150ms',
        transform: open ? 'rotate(90deg)' : 'rotate(0deg)',
    }),
);

// ---------------------------------------------------------------------------
// Shared sx constants
// ---------------------------------------------------------------------------

const monoLinkSx = (theme: import('@mui/material/styles').Theme) =>
    ({
        fontFamily: theme.adp.fontFamilyMono,
        fontSize: '12px',
        color: 'primary.main',
        wordBreak: 'break-all',
        '&:hover': {textDecoration: 'underline'},
    }) as const;

// ---------------------------------------------------------------------------
// Listener rendering
// ---------------------------------------------------------------------------

const ClosureActions = React.memo(({listener}: {listener: ClosureDescriptor}) => (
    <Box
        className="copy-btn"
        sx={{display: 'flex', alignItems: 'center', gap: 0.25, flexShrink: 0, alignSelf: 'flex-start', mt: 0.5}}
    >
        <Tooltip title="Copy code">
            <IconButton size="small" onClick={() => clipboardCopy(listener.source)} sx={{p: 0.25}}>
                <ContentCopy sx={{fontSize: 14}} />
            </IconButton>
        </Tooltip>
        {listener.file && (
            <FileLink path={listener.file} line={listener.startLine ?? undefined}>
                <Tooltip title="Open in File Inspector">
                    <IconButton size="small" component="span" aria-label="Open File" sx={{p: 0.25}}>
                        <Description sx={{fontSize: 14}} />
                    </IconButton>
                </Tooltip>
            </FileLink>
        )}
    </Box>
));

const ListenerItem = React.memo(({listener}: {listener: EventListener}) => {
    if (isClosureDescriptor(listener)) {
        return (
            <ListenerRow>
                <Box sx={{flex: 1, minWidth: 0, overflow: 'auto'}}>
                    <CodeHighlight language="php" code={listener.source} showLineNumbers={false} fontSize={9} />
                </Box>
                <ClosureActions listener={listener} />
            </ListenerRow>
        );
    }

    const parsed = parseCallable(listener);
    if (parsed) {
        return (
            <ListenerRow>
                <FileLink className={parsed.className} methodName={parsed.methodName} sx={{flex: 1, minWidth: 0}}>
                    <Typography component="span" sx={monoLinkSx}>
                        {serializeCallable(listener)}
                    </Typography>
                </FileLink>
            </ListenerRow>
        );
    }

    if (typeof listener === 'string' && isClassName(listener)) {
        return (
            <ListenerRow>
                <FileLink className={listener} sx={{flex: 1, minWidth: 0}}>
                    <Typography component="span" sx={monoLinkSx}>
                        {listener}
                    </Typography>
                </FileLink>
            </ListenerRow>
        );
    }

    return (
        <ListenerRow>
            <Typography sx={(theme) => ({...monoLinkSx(theme), flex: 1, color: 'text.secondary'})}>
                {serializeCallable(listener)}
            </Typography>
        </ListenerRow>
    );
});

// ---------------------------------------------------------------------------
// Event row (inside a namespace group)
// ---------------------------------------------------------------------------

const EventRow = React.memo(({entry, displayName}: {entry: EventEntry; displayName: string}) => {
    const [expanded, setExpanded] = useState(false);
    const targetClass = entry.class || (isClassName(entry.name) ? entry.name : null);
    const toggle = useCallback(() => {
        const selection = typeof window !== 'undefined' ? window.getSelection()?.toString() : '';
        if (selection && selection.length > 0) return;
        setExpanded((v) => !v);
    }, []);
    const stopProp = useCallback((e: React.MouseEvent) => e.stopPropagation(), []);

    return (
        <EventRowBox expanded={expanded}>
            <EventRowHead onClick={toggle}>
                <EventExpandIndicator open={expanded}>
                    <ChevronRight sx={{fontSize: 14}} />
                </EventExpandIndicator>
                <Tooltip title={entry.name} placement="top-start">
                    <Typography
                        sx={(theme) => ({
                            fontFamily: theme.adp.fontFamilyMono,
                            fontSize: '12px',
                            fontWeight: 600,
                            wordBreak: 'break-all',
                            flex: 1,
                            minWidth: 0,
                        })}
                    >
                        {displayName}
                    </Typography>
                </Tooltip>
                <Chip
                    label={`${entry.listeners.length} ${entry.listeners.length === 1 ? 'listener' : 'listeners'}`}
                    size="small"
                    sx={{
                        fontSize: '10px',
                        height: 20,
                        borderRadius: 1,
                        backgroundColor: 'action.selected',
                        flexShrink: 0,
                    }}
                />
                <Box
                    className="event-row-actions"
                    sx={{display: 'flex', alignItems: 'center', gap: 0.25, flexShrink: 0}}
                    onClick={stopProp}
                >
                    {targetClass && (
                        <Tooltip title="Open class source">
                            <IconButton
                                size="small"
                                component={RouterLink}
                                to={`/inspector/files?class=${encodeURIComponent(targetClass)}`}
                                aria-label="Open class source"
                                sx={{p: 0.25}}
                            >
                                <Code sx={{fontSize: 14}} />
                            </IconButton>
                        </Tooltip>
                    )}
                    <Tooltip title="Copy event name">
                        <IconButton
                            size="small"
                            onClick={() => clipboardCopy(entry.name)}
                            aria-label="Copy event name"
                            sx={{p: 0.25}}
                        >
                            <ContentCopy sx={{fontSize: 14}} />
                        </IconButton>
                    </Tooltip>
                </Box>
            </EventRowHead>
            <Collapse in={expanded} unmountOnExit>
                <Box sx={{borderTop: 1, borderColor: 'divider'}}>
                    {entry.listeners.map((listener, i) => (
                        <ListenerItem key={i} listener={listener} />
                    ))}
                </Box>
            </Collapse>
        </EventRowBox>
    );
});

// ---------------------------------------------------------------------------
// Event listeners list (groups of events by namespace)
// ---------------------------------------------------------------------------

type EventListenersProps = {entries: EventEntry[]};

const EventListeners = React.memo(({entries}: EventListenersProps) => {
    const groups = useMemo(() => groupByNamespace(entries.map((e) => ({...e, id: e.name}))), [entries]);

    if (entries.length === 0) {
        return <EmptyState icon="bolt" title="No event listeners found" />;
    }

    return (
        <Box sx={{px: 2, pb: 2}}>
            {groups.map((group) => (
                <GroupCard
                    key={group.name || '__events__'}
                    name={group.displayName}
                    count={group.entries.length}
                    countLabel={group.entries.length === 1 ? 'event' : 'events'}
                    defaultExpanded={entries.length <= 10 || groups.length === 1}
                    preview={
                        <>
                            {group.entries.slice(0, 4).map((entry, i) => (
                                <span key={entry.name}>
                                    {i > 0 && <span style={{opacity: 0.4}}>{' · '}</span>}
                                    {stripNamespace(entry.name, group.name)}
                                </span>
                            ))}
                            {group.entries.length > 4 && <span style={{opacity: 0.4}}> …</span>}
                        </>
                    }
                >
                    {group.entries.map((entry) => (
                        <EventRow key={entry.name} entry={entry} displayName={stripNamespace(entry.name, group.name)} />
                    ))}
                </GroupCard>
            ))}
        </Box>
    );
});

// ---------------------------------------------------------------------------
// Main component
// ---------------------------------------------------------------------------

type TabValue = 'common' | 'web' | 'console';

export const EventsPage = () => {
    const {data, isLoading, isError, error, refetch} = useGetEventsQuery();
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
                        e.listeners.some((l) => re.test(getListenerSearchText(l))),
                ),
            );

        return {
            common: filterEntries(allEntries.common),
            web: filterEntries(allEntries.web),
            console: filterEntries(allEntries.console),
        };
    }, [allEntries, filterValue]);

    const totalCount = filtered.common.length + filtered.web.length + filtered.console.length;

    if (isLoading) {
        return <FullScreenCircularProgress />;
    }

    if (isError) {
        return (
            <>
                <PageHeader title="Event Listeners" icon="bolt" description="View registered event listeners" />
                <QueryErrorState
                    error={error}
                    title="Failed to load event listeners"
                    fallback="Failed to load event listeners."
                    onRetry={refetch}
                />
            </>
        );
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
            <TabContext value={tabValue}>
                <Box sx={{display: 'flex', alignItems: 'center', borderBottom: 1, borderColor: 'divider', mb: 2}}>
                    <TabList onChange={handleTabChange} sx={{flex: 1}}>
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
                    <Box sx={{display: 'flex', alignItems: 'center', gap: 1.5, pb: 0.5}}>
                        <Typography sx={{fontSize: '12px', color: 'text.disabled', whiteSpace: 'nowrap'}}>
                            {totalCount} events
                        </Typography>
                        <FilterInput value={filterValue} onChange={onFilterChange} placeholder="Search events..." />
                    </Box>
                </Box>
                <TabPanel value="common" sx={{px: 0, py: 0}}>
                    <EventListeners entries={filtered.common} />
                </TabPanel>
                <TabPanel value="web" sx={{px: 0, py: 0}}>
                    <EventListeners entries={filtered.web} />
                </TabPanel>
                <TabPanel value="console" sx={{px: 0, py: 0}}>
                    <EventListeners entries={filtered.console} />
                </TabPanel>
            </TabContext>
        </>
    );
};
