import {JsonRenderer} from '@app-dev-panel/panel/Module/Debug/Component/JsonRenderer';
import {SectionTitle} from '@app-dev-panel/sdk/Component/SectionTitle';
import {Box, Chip, Collapse, Icon, Tab, Tabs, Typography, type Theme} from '@mui/material';
import {styled, useTheme} from '@mui/material/styles';
import React, {useCallback, useState} from 'react';

type CommandEvent = {
    name?: string;
    command?: unknown;
    input?: string;
    output?: string;
    exitCode?: number;
    error?: string;
    arguments?: Record<string, unknown>;
    options?: Record<string, unknown>;
};

type CommandPanelProps = {data: Record<string, CommandEvent>};

// ---------------------------------------------------------------------------
// Styled components
// ---------------------------------------------------------------------------

const MetricBox = styled(Box)(({theme}) => ({
    display: 'flex',
    alignItems: 'center',
    gap: theme.spacing(1.5),
    padding: theme.spacing(1.5, 2),
    borderBottom: `1px solid ${theme.palette.divider}`,
    backgroundColor: theme.palette.action.hover,
}));

const TabPanel = styled(Box)(({theme}) => ({padding: theme.spacing(2)}));

const InfoTable = styled('table')(({theme}) => ({
    width: '100%',
    borderCollapse: 'collapse',
    fontFamily: theme.adp.fontFamilyMono,
    fontSize: '12px',
    '& th': {
        textAlign: 'left',
        padding: theme.spacing(0.75, 1.5),
        fontWeight: 600,
        color: theme.palette.text.secondary,
        borderBottom: `1px solid ${theme.palette.divider}`,
        whiteSpace: 'nowrap',
        width: '30%',
        verticalAlign: 'top',
    },
    '& td': {
        padding: theme.spacing(0.75, 1.5),
        color: theme.palette.text.primary,
        borderBottom: `1px solid ${theme.palette.divider}`,
        wordBreak: 'break-all',
    },
    '& tr:last-child th, & tr:last-child td': {borderBottom: 'none'},
}));

// ---------------------------------------------------------------------------
// Helpers
// ---------------------------------------------------------------------------

const exitCodeColor = (code: number | undefined, theme: Theme): string => {
    if (code == null || code === -1) return theme.palette.text.disabled;
    return code === 0 ? theme.palette.success.main : theme.palette.error.main;
};

function getTerminateEvent(data: Record<string, CommandEvent> | null | undefined): CommandEvent | null {
    if (!data || typeof data !== 'object') return null;
    // Prefer terminate event, then error, then generic, then framework-agnostic
    for (const suffix of ['ConsoleTerminateEvent', 'ConsoleErrorEvent', 'ConsoleCommandEvent', 'command']) {
        const match = Object.entries(data).find(([key]) => key.endsWith(suffix));
        if (match) return match[1];
    }
    // Fallback: first event
    const values = Object.values(data);
    return values.length > 0 ? values[0] : null;
}

function getCommandEvent(data: Record<string, CommandEvent> | null | undefined): CommandEvent | null {
    if (!data || typeof data !== 'object') return null;
    for (const suffix of ['ConsoleCommandEvent', 'command']) {
        const match = Object.entries(data).find(([key]) => key.endsWith(suffix));
        if (match) return match[1];
    }
    return null;
}

// ---------------------------------------------------------------------------
// Overview Tab
// ---------------------------------------------------------------------------

const OverviewTab = ({data}: {data: Record<string, CommandEvent>}) => {
    const theme = useTheme();
    const terminateEvent = getTerminateEvent(data);
    const commandEvent = getCommandEvent(data);

    if (!terminateEvent) {
        return (
            <TabPanel>
                <Typography sx={{color: 'text.disabled', textAlign: 'center', py: 4}}>
                    No command data available
                </Typography>
            </TabPanel>
        );
    }

    const args = commandEvent?.arguments;
    const opts = commandEvent?.options;

    return (
        <TabPanel>
            <SectionTitle>Command Info</SectionTitle>
            <Box sx={{borderRadius: 1, border: '1px solid', borderColor: 'divider', overflow: 'hidden', mb: 2}}>
                <InfoTable>
                    <tbody>
                        <tr>
                            <th>Name</th>
                            <td>{terminateEvent.name || 'Unknown'}</td>
                        </tr>
                        <tr>
                            <th>Input</th>
                            <td>{terminateEvent.input || '(empty)'}</td>
                        </tr>
                        <tr>
                            <th>Exit Code</th>
                            <td>
                                <Chip
                                    label={terminateEvent.exitCode ?? 'N/A'}
                                    size="small"
                                    sx={{
                                        fontWeight: 700,
                                        fontSize: '11px',
                                        height: 22,
                                        backgroundColor: exitCodeColor(terminateEvent.exitCode, theme),
                                        color: theme.palette.common.white,
                                        borderRadius: 1,
                                    }}
                                />
                            </td>
                        </tr>
                        {terminateEvent.error && (
                            <tr>
                                <th>Error</th>
                                <td style={{color: theme.palette.error.main}}>{terminateEvent.error}</td>
                            </tr>
                        )}
                    </tbody>
                </InfoTable>
            </Box>

            {args && Object.keys(args).length > 0 && (
                <>
                    <SectionTitle>Arguments</SectionTitle>
                    <Box sx={{borderRadius: 1, border: '1px solid', borderColor: 'divider', overflow: 'hidden', mb: 2}}>
                        <InfoTable>
                            <tbody>
                                {Object.entries(args).map(([name, value]) => (
                                    <tr key={name}>
                                        <th>{name}</th>
                                        <td>
                                            <JsonRenderer value={value} />
                                        </td>
                                    </tr>
                                ))}
                            </tbody>
                        </InfoTable>
                    </Box>
                </>
            )}

            {opts && Object.keys(opts).length > 0 && (
                <>
                    <SectionTitle>Options</SectionTitle>
                    <Box sx={{borderRadius: 1, border: '1px solid', borderColor: 'divider', overflow: 'hidden'}}>
                        <InfoTable>
                            <tbody>
                                {Object.entries(opts).map(([name, value]) => (
                                    <tr key={name}>
                                        <th>--{name}</th>
                                        <td>
                                            <JsonRenderer value={value} />
                                        </td>
                                    </tr>
                                ))}
                            </tbody>
                        </InfoTable>
                    </Box>
                </>
            )}
        </TabPanel>
    );
};

// ---------------------------------------------------------------------------
// Output Tab
// ---------------------------------------------------------------------------

const OutputTab = ({data}: {data: Record<string, CommandEvent>}) => {
    const terminateEvent = getTerminateEvent(data);
    const output = terminateEvent?.output;

    return (
        <TabPanel>
            {output ? (
                <>
                    <SectionTitle>Console Output</SectionTitle>
                    <Box
                        component="pre"
                        sx={(theme) => ({
                            fontFamily: theme.adp.fontFamilyMono,
                            fontSize: '12px',
                            p: 2,
                            borderRadius: 1,
                            border: '1px solid',
                            borderColor: 'divider',
                            backgroundColor: 'action.hover',
                            whiteSpace: 'pre-wrap',
                            wordBreak: 'break-word',
                            m: 0,
                        })}
                    >
                        {output}
                    </Box>
                </>
            ) : (
                <Typography sx={{color: 'text.disabled', fontSize: '13px', textAlign: 'center', py: 4}}>
                    No output captured
                </Typography>
            )}
        </TabPanel>
    );
};

// ---------------------------------------------------------------------------
// Events Tab — structured view per event type
// ---------------------------------------------------------------------------

const NullChip = () => (
    <Chip
        label="null"
        size="small"
        sx={{fontSize: '10px', height: 18, color: 'text.disabled', borderRadius: 1}}
        variant="outlined"
    />
);

const EventCard = ({eventClass, eventData}: {eventClass: string; eventData: CommandEvent}) => {
    const theme = useTheme();
    const [expanded, setExpanded] = useState(true);
    const toggleExpanded = useCallback(() => setExpanded((prev) => !prev), []);
    const shortName = eventClass.includes('\\') ? eventClass.split('\\').pop() : eventClass;
    const exitCode = eventData.exitCode;
    const args = eventData.arguments;
    const opts = eventData.options;

    return (
        <Box sx={{mb: 2, border: '1px solid', borderColor: 'divider', borderRadius: 1, overflow: 'hidden'}}>
            <Box
                role="button"
                tabIndex={0}
                aria-expanded={expanded}
                aria-label={`Toggle ${shortName ?? eventClass} details`}
                onClick={toggleExpanded}
                onKeyDown={(e: React.KeyboardEvent) => {
                    if (e.key === 'Enter' || e.key === ' ') {
                        e.preventDefault();
                        toggleExpanded();
                    }
                }}
                sx={{
                    display: 'flex',
                    alignItems: 'center',
                    gap: 1,
                    px: 2,
                    py: 1,
                    cursor: 'pointer',
                    backgroundColor: 'action.hover',
                    '&:hover': {backgroundColor: 'action.selected'},
                }}
            >
                <Icon sx={{fontSize: 16, color: 'text.disabled'}}>{expanded ? 'expand_less' : 'expand_more'}</Icon>
                <Typography sx={{fontWeight: 600, fontSize: '13px', flex: 1}}>{shortName}</Typography>
                {exitCode != null && (
                    <Chip
                        label={exitCode === 0 ? 'OK' : `exit ${exitCode}`}
                        size="small"
                        sx={{
                            fontWeight: 600,
                            fontSize: '10px',
                            height: 20,
                            borderRadius: 1,
                            backgroundColor: exitCodeColor(exitCode, theme),
                            color: theme.palette.common.white,
                        }}
                    />
                )}
            </Box>
            <Collapse in={expanded}>
                <Box sx={{p: 0}}>
                    <InfoTable>
                        <tbody>
                            <tr>
                                <th>Name</th>
                                <td>{eventData.name || <NullChip />}</td>
                            </tr>
                            <tr>
                                <th>Input</th>
                                <td>
                                    {eventData.input ? (
                                        <Typography
                                            component="span"
                                            sx={(theme) => ({fontFamily: theme.adp.fontFamilyMono, fontSize: '12px'})}
                                        >
                                            {eventData.input}
                                        </Typography>
                                    ) : (
                                        <NullChip />
                                    )}
                                </td>
                            </tr>
                            {eventData.output !== undefined && (
                                <tr>
                                    <th>Output</th>
                                    <td>{eventData.output ? eventData.output : <NullChip />}</td>
                                </tr>
                            )}
                            {eventData.command !== undefined && (
                                <tr>
                                    <th>Command</th>
                                    <td>
                                        {eventData.command ? <JsonRenderer value={eventData.command} /> : <NullChip />}
                                    </td>
                                </tr>
                            )}
                            {eventData.error && (
                                <tr>
                                    <th>Error</th>
                                    <td>
                                        <Typography component="span" sx={{color: 'error.main', fontSize: '12px'}}>
                                            {eventData.error}
                                        </Typography>
                                    </td>
                                </tr>
                            )}
                        </tbody>
                    </InfoTable>

                    {args && Object.keys(args).length > 0 && (
                        <Box sx={{px: 2, pb: 1.5}}>
                            <SectionTitle>Arguments</SectionTitle>
                            <Box
                                sx={{borderRadius: 1, border: '1px solid', borderColor: 'divider', overflow: 'hidden'}}
                            >
                                <InfoTable>
                                    <tbody>
                                        {Object.entries(args).map(([name, value]) => (
                                            <tr key={name}>
                                                <th>{name}</th>
                                                <td>
                                                    <JsonRenderer value={value} />
                                                </td>
                                            </tr>
                                        ))}
                                    </tbody>
                                </InfoTable>
                            </Box>
                        </Box>
                    )}

                    {opts && Object.keys(opts).length > 0 && (
                        <Box sx={{px: 2, pb: 1.5}}>
                            <SectionTitle>Options</SectionTitle>
                            <Box
                                sx={{borderRadius: 1, border: '1px solid', borderColor: 'divider', overflow: 'hidden'}}
                            >
                                <InfoTable>
                                    <tbody>
                                        {Object.entries(opts).map(([name, value]) => (
                                            <tr key={name}>
                                                <th>--{name}</th>
                                                <td>
                                                    <JsonRenderer value={value} />
                                                </td>
                                            </tr>
                                        ))}
                                    </tbody>
                                </InfoTable>
                            </Box>
                        </Box>
                    )}
                </Box>
            </Collapse>
        </Box>
    );
};

const EventsTab = ({data}: {data: Record<string, CommandEvent>}) => (
    <TabPanel>
        {Object.entries(data).map(([eventClass, eventData]) => (
            <EventCard key={eventClass} eventClass={eventClass} eventData={eventData} />
        ))}
    </TabPanel>
);

// ---------------------------------------------------------------------------
// Main component
// ---------------------------------------------------------------------------

export const CommandPanel = ({data}: CommandPanelProps) => {
    const theme = useTheme();
    const [tab, setTab] = useState(0);
    const safeData = data && typeof data === 'object' ? data : {};
    const terminateEvent = getTerminateEvent(safeData);

    const handleTabChange = useCallback((_: React.SyntheticEvent, newValue: number) => {
        setTab(newValue);
    }, []);

    const commandName = terminateEvent?.name || 'Unknown command';
    const commandInput = terminateEvent?.input || commandName;
    const exitCode = terminateEvent?.exitCode;
    const hasOutput = Object.values(safeData).some((e) => e.output);
    const hasError = Object.values(safeData).some((e) => e.error);

    return (
        <Box>
            <MetricBox>
                <Icon sx={{fontSize: 18, color: 'info.main'}}>terminal</Icon>
                <Typography
                    sx={(theme) => ({
                        fontFamily: theme.adp.fontFamilyMono,
                        fontSize: '13px',
                        flex: 1,
                        wordBreak: 'break-all',
                    })}
                >
                    {commandInput}
                </Typography>
                <Chip
                    label={exitCode != null && exitCode !== -1 ? (exitCode === 0 ? 'OK' : `exit ${exitCode}`) : 'N/A'}
                    size="small"
                    sx={{
                        fontWeight: 700,
                        fontSize: '11px',
                        height: 22,
                        backgroundColor: exitCodeColor(exitCode, theme),
                        color: theme.palette.common.white,
                        borderRadius: 1,
                    }}
                />
                {hasError && (
                    <Chip
                        label="ERROR"
                        size="small"
                        sx={{
                            fontSize: '10px',
                            height: 20,
                            borderRadius: 1,
                            backgroundColor: theme.palette.error.light,
                            color: theme.palette.error.main,
                            fontWeight: 700,
                        }}
                    />
                )}
            </MetricBox>

            <Box sx={{borderBottom: 1, borderColor: 'divider'}}>
                <Tabs
                    value={tab}
                    onChange={handleTabChange}
                    sx={{'& .MuiTab-root': {textTransform: 'none', minHeight: 40, fontSize: '13px', fontWeight: 600}}}
                >
                    <Tab label="Overview" />
                    <Tab label="Output" disabled={!hasOutput} />
                    <Tab label="Events" />
                </Tabs>
            </Box>

            {tab === 0 && <OverviewTab data={data} />}
            {tab === 1 && <OutputTab data={data} />}
            {tab === 2 && <EventsTab data={data} />}
        </Box>
    );
};
