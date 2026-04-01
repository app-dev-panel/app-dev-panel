import {
    ClosureDescriptor,
    EventEntry,
    EventListener,
    EventListenersType,
    useGetEventsQuery,
} from '@app-dev-panel/panel/Module/Inspector/API/Inspector';
import {CodeHighlight} from '@app-dev-panel/sdk/Component/CodeHighlight';
import {EmptyState} from '@app-dev-panel/sdk/Component/EmptyState';
import {FileLink} from '@app-dev-panel/sdk/Component/FileLink';
import {FilterInput} from '@app-dev-panel/sdk/Component/FilterInput';
import {FullScreenCircularProgress} from '@app-dev-panel/sdk/Component/FullScreenCircularProgress';
import {PageHeader} from '@app-dev-panel/sdk/Component/PageHeader';
import {primitives} from '@app-dev-panel/sdk/Component/Theme/tokens';
import {serializeCallable} from '@app-dev-panel/sdk/Helper/callableSerializer';
import {searchVariants} from '@app-dev-panel/sdk/Helper/layoutTranslit';
import {regexpQuote} from '@app-dev-panel/sdk/Helper/regexpQuote';
import {ContentCopy, ExpandMore} from '@mui/icons-material';
import {TabContext, TabPanel} from '@mui/lab';
import TabList from '@mui/lab/TabList';
import {
    Accordion,
    AccordionDetails,
    AccordionSummary,
    Box,
    Chip,
    IconButton,
    Tab,
    Tooltip,
    Typography,
} from '@mui/material';
import {styled} from '@mui/material/styles';
import clipboardCopy from 'clipboard-copy';
import React, {SyntheticEvent, useCallback, useMemo, useState} from 'react';
import {useSearchParams} from 'react-router-dom';

// ---------------------------------------------------------------------------
// Helpers
// ---------------------------------------------------------------------------

function normalizeEntries(data: EventListenersType | null): EventEntry[] {
    if (!data) return [];
    if (Array.isArray(data)) return data;
    return Object.entries(data).map(([name, listeners]) => ({name, class: null, listeners}));
}

function isClosureDescriptor(value: unknown): value is ClosureDescriptor {
    return typeof value === 'object' && value !== null && '__closure' in value && (value as any).__closure === true;
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

function parseEventName(name: string): {className: string; member: string} | {className: string} | null {
    if (name.includes('::')) {
        const [className, member] = name.split('::', 2);
        if (className && member && isClassName(className)) {
            return {className, member};
        }
    }
    if (isClassName(name)) {
        return {className: name};
    }
    return null;
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

const StyledAccordion = styled(Accordion)(({theme}) => ({
    border: `1px solid ${theme.palette.divider}`,
    borderRadius: `${theme.shape.borderRadius}px !important`,
    '&:before': {display: 'none'},
    '&:not(:last-child)': {marginBottom: theme.spacing(1)},
    '&.Mui-expanded': {margin: 0, '&:not(:last-child)': {marginBottom: theme.spacing(1)}},
}));

const StyledAccordionSummary = styled(AccordionSummary)(({theme}) => ({
    minHeight: 40,
    padding: theme.spacing(0, 2),
    '&.Mui-expanded': {minHeight: 40},
    '& .MuiAccordionSummary-content': {
        margin: theme.spacing(0.75, 0),
        alignItems: 'center',
        gap: theme.spacing(1),
        overflow: 'hidden',
    },
    '& .MuiAccordionSummary-content.Mui-expanded': {margin: theme.spacing(0.75, 0)},
    '& .header-actions': {opacity: 0, transition: 'opacity 0.15s'},
    '&:hover .header-actions': {opacity: 1},
}));

// ---------------------------------------------------------------------------
// Event name rendering
// ---------------------------------------------------------------------------

const EventName = React.memo(({name, eventClass}: {name: string; eventClass: string | null}) => {
    const parsed = parseEventName(name);

    if (parsed && 'member' in parsed) {
        return (
            <FileLink className={parsed.className} methodName={parsed.member} sx={{minWidth: 0}}>
                <Typography
                    component="span"
                    sx={{
                        fontFamily: primitives.fontFamilyMono,
                        fontWeight: 600,
                        fontSize: '13px',
                        color: 'primary.main',
                        wordBreak: 'break-all',
                        '&:hover': {textDecoration: 'underline'},
                    }}
                >
                    {name}
                </Typography>
            </FileLink>
        );
    }

    if (parsed) {
        return (
            <FileLink className={parsed.className} sx={{minWidth: 0}}>
                <Typography
                    component="span"
                    sx={{
                        fontFamily: primitives.fontFamilyMono,
                        fontWeight: 600,
                        fontSize: '13px',
                        color: 'primary.main',
                        wordBreak: 'break-all',
                        '&:hover': {textDecoration: 'underline'},
                    }}
                >
                    {name}
                </Typography>
            </FileLink>
        );
    }

    if (eventClass && eventClass !== name) {
        return (
            <Box sx={{display: 'flex', alignItems: 'baseline', gap: 0.5, minWidth: 0}}>
                <FileLink className={eventClass} sx={{flexShrink: 0}}>
                    <Typography
                        component="span"
                        sx={{
                            fontWeight: 600,
                            fontSize: '13px',
                            color: 'primary.main',
                            '&:hover': {textDecoration: 'underline'},
                        }}
                    >
                        {eventClass.split('\\').pop()}
                    </Typography>
                </FileLink>
                <Typography component="span" sx={{fontSize: '12px', color: 'text.secondary', wordBreak: 'break-all'}}>
                    ({name})
                </Typography>
            </Box>
        );
    }

    return (
        <Typography sx={{fontWeight: 600, fontSize: '13px', wordBreak: 'break-all', minWidth: 0}}>{name}</Typography>
    );
});

// ---------------------------------------------------------------------------
// Listener rendering
// ---------------------------------------------------------------------------

const ListenerItem = React.memo(({listener}: {listener: EventListener}) => {
    if (isClosureDescriptor(listener)) {
        return (
            <ListenerRow>
                <Box sx={{flex: 1, minWidth: 0}}>
                    <Box sx={{display: 'flex', alignItems: 'center', gap: 0.5}}>
                        <CodeHighlight
                            language="php"
                            code={`<?php\n${listener.source}`}
                            showLineNumbers={false}
                            fontSize={9}
                        />
                        {listener.file && <FileLink path={listener.file} line={listener.startLine ?? undefined} />}
                    </Box>
                </Box>
            </ListenerRow>
        );
    }

    const parsed = parseCallable(listener);
    if (parsed) {
        return (
            <ListenerRow>
                <FileLink className={parsed.className} methodName={parsed.methodName} sx={{flex: 1, minWidth: 0}}>
                    <Typography
                        component="span"
                        sx={{
                            fontFamily: primitives.fontFamilyMono,
                            fontSize: '12px',
                            color: 'primary.main',
                            textDecoration: 'none',
                            wordBreak: 'break-all',
                            '&:hover': {textDecoration: 'underline'},
                        }}
                    >
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
                    <Typography
                        component="span"
                        sx={{
                            fontFamily: primitives.fontFamilyMono,
                            fontSize: '12px',
                            color: 'primary.main',
                            textDecoration: 'none',
                            wordBreak: 'break-all',
                            '&:hover': {textDecoration: 'underline'},
                        }}
                    >
                        {listener}
                    </Typography>
                </FileLink>
            </ListenerRow>
        );
    }

    return (
        <ListenerRow>
            <Typography
                sx={{
                    flex: 1,
                    minWidth: 0,
                    fontFamily: primitives.fontFamilyMono,
                    fontSize: '12px',
                    color: 'text.secondary',
                    wordBreak: 'break-all',
                }}
            >
                {serializeCallable(listener)}
            </Typography>
        </ListenerRow>
    );
});

// ---------------------------------------------------------------------------
// Event accordion
// ---------------------------------------------------------------------------

type EventListenersProps = {entries: EventEntry[]};

const EventListeners = React.memo(({entries}: EventListenersProps) => {
    if (entries.length === 0) {
        return <EmptyState icon="bolt" title="No event listeners found" />;
    }

    return (
        <>
            {entries.map((entry) => (
                <StyledAccordion
                    key={entry.name}
                    disableGutters
                    elevation={0}
                    slotProps={{transition: {unmountOnExit: true}}}
                >
                    <StyledAccordionSummary expandIcon={<ExpandMore />}>
                        <Box sx={{flex: 1, minWidth: 0, display: 'flex', alignItems: 'center', gap: 1}}>
                            <EventName name={entry.name} eventClass={entry.class} />
                        </Box>
                        <Box className="header-actions" sx={{display: 'flex', alignItems: 'center', flexShrink: 0}}>
                            <Tooltip title="Copy event name">
                                <IconButton
                                    size="small"
                                    onClick={(e) => {
                                        e.stopPropagation();
                                        clipboardCopy(entry.name);
                                    }}
                                    sx={{p: 0.25}}
                                >
                                    <ContentCopy sx={{fontSize: 14}} />
                                </IconButton>
                            </Tooltip>
                        </Box>
                        {entry.class && (
                            <Box className="header-actions" sx={{flexShrink: 0}} onClick={(e) => e.stopPropagation()}>
                                <FileLink className={entry.class} />
                            </Box>
                        )}
                        <Chip
                            label={entry.listeners.length}
                            size="small"
                            sx={{
                                fontSize: '10px',
                                height: 20,
                                minWidth: 24,
                                borderRadius: 1,
                                backgroundColor: 'action.selected',
                                flexShrink: 0,
                            }}
                        />
                    </StyledAccordionSummary>
                    <AccordionDetails sx={{p: 0, borderTop: 1, borderColor: 'divider'}}>
                        {entry.listeners.map((listener, i) => (
                            <ListenerItem key={i} listener={listener} />
                        ))}
                    </AccordionDetails>
                </StyledAccordion>
            ))}
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
