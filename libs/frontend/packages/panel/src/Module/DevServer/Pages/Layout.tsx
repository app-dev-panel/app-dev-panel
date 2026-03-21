import {useSelector} from '@app-dev-panel/panel/store';
import {JsonRenderer} from '@app-dev-panel/sdk/Component/JsonRenderer';
import {SectionTitle} from '@app-dev-panel/sdk/Component/SectionTitle';
import {primitives} from '@app-dev-panel/sdk/Component/Theme/tokens';
import {useDevServerEvents} from '@app-dev-panel/sdk/Component/useDevServerEvents';
import {logLevelColor} from '@app-dev-panel/sdk/Helper/logLevelColor';
import DataObjectIcon from '@mui/icons-material/DataObject';
import DeleteSweepIcon from '@mui/icons-material/DeleteSweep';
import TableRowsIcon from '@mui/icons-material/TableRows';
import {
    Badge,
    Box,
    Chip,
    IconButton,
    List,
    ListItem,
    ListItemIcon,
    ListItemText,
    ToggleButton,
    ToggleButtonGroup,
    Tooltip,
    Typography,
} from '@mui/material';
import {styled, useTheme} from '@mui/material/styles';
import {type MouseEvent, useCallback, useMemo, useRef, useState} from 'react';

/** Matches Connection::MESSAGE_TYPE_VAR_DUMPER (0x001B) and MESSAGE_TYPE_LOGGER (0x002B) in PHP */
enum EventTypeEnum {
    VAR_DUMPER = 27,
    LOGS = 43,
}

type ParsedEventItem = {id: number; data: unknown; time: Date; type: EventTypeEnum};

const MAX_EVENTS = 5000;

const StyledListItem = styled(ListItem)(({theme}) => ({
    alignItems: 'flex-start',
    borderBottom: `1px solid ${theme.palette.divider}`,
    paddingTop: theme.spacing(1.5),
    paddingBottom: theme.spacing(1.5),
}));

const TimeLabel = styled(Typography)({fontFamily: primitives.fontFamilyMono, fontSize: '11px', whiteSpace: 'nowrap'});

const FallbackText = styled(Typography)({fontSize: '13px', wordBreak: 'break-word'});

function DebugEntryIcon({type}: {type: EventTypeEnum}) {
    if (type === EventTypeEnum.VAR_DUMPER) {
        return (
            <Tooltip title="VarDumper">
                <DataObjectIcon color="secondary" />
            </Tooltip>
        );
    }
    if (type === EventTypeEnum.LOGS) {
        return (
            <Tooltip title="Logger">
                <TableRowsIcon color="primary" />
            </Tooltip>
        );
    }
    return null;
}

function VarDumperContent({data}: {data: unknown}) {
    if (typeof data === 'string') {
        return <FallbackText>{data}</FallbackText>;
    }
    return <JsonRenderer value={data} />;
}

function LogContent({data}: {data: unknown}) {
    const theme = useTheme();

    if (typeof data !== 'object' || data === null) {
        return <FallbackText>{String(data)}</FallbackText>;
    }

    const parsed = data as {level?: string; message?: string; context?: Record<string, unknown>};
    const level = parsed.level || 'info';
    const color = logLevelColor(level, theme);

    return (
        <Box sx={{display: 'flex', alignItems: 'center', gap: 1, flexWrap: 'wrap'}}>
            <Chip
                label={level.toUpperCase()}
                size="small"
                sx={{
                    fontWeight: 600,
                    fontSize: '10px',
                    height: 20,
                    minWidth: 50,
                    backgroundColor: color,
                    color: 'common.white',
                    borderRadius: 1,
                }}
            />
            <Typography sx={{fontSize: '13px', fontWeight: 500}}>{parsed.message}</Typography>
            {parsed.context && Object.keys(parsed.context).length > 0 && (
                <JsonRenderer value={parsed.context} depth={2} />
            )}
        </Box>
    );
}

function DebugEntryContent({data, type}: {data: unknown; type: EventTypeEnum}) {
    if (type === EventTypeEnum.VAR_DUMPER) return <VarDumperContent data={data} />;
    if (type === EventTypeEnum.LOGS) return <LogContent data={data} />;
    return <FallbackText>{String(data)}</FallbackText>;
}

function formatTime(date: Date): string {
    return date.toLocaleTimeString('en-GB', {
        hour: '2-digit',
        minute: '2-digit',
        second: '2-digit',
        fractionalSecondDigits: 3,
    });
}

export const Layout = () => {
    const [events, setEvents] = useState<ParsedEventItem[]>([]);
    const backendUrl = useSelector((state) => state.application.baseUrl);
    const nextId = useRef(0);

    const onMessage = useCallback((m: MessageEvent) => {
        let raw: [number, string];
        try {
            raw = JSON.parse(m.data);
        } catch {
            return;
        }

        let parsed: unknown;
        try {
            parsed = JSON.parse(raw[1]);
        } catch {
            parsed = raw[1];
        }

        const id = nextId.current++;
        const type = raw[0] as EventTypeEnum;
        setEvents((prev) => {
            const next = [{id, data: parsed, time: new Date(), type}, ...prev];
            if (next.length > MAX_EVENTS) next.length = MAX_EVENTS;
            return next;
        });
    }, []);

    useDevServerEvents(backendUrl, onMessage, true);

    const [activeTypes, setActiveTypes] = useState<EventTypeEnum[]>([EventTypeEnum.VAR_DUMPER, EventTypeEnum.LOGS]);

    const handleTypeFilter = (_event: MouseEvent<HTMLElement>, types: EventTypeEnum[]) => {
        setActiveTypes(types);
    };

    const handleClear = () => {
        setEvents([]);
    };

    const filtered = useMemo(() => events.filter((e) => activeTypes.includes(e.type)), [events, activeTypes]);

    const counters = useMemo(() => {
        const counts = {[EventTypeEnum.VAR_DUMPER]: 0, [EventTypeEnum.LOGS]: 0};
        for (const e of events) counts[e.type] = (counts[e.type] || 0) + 1;
        return counts;
    }, [events]);

    return (
        <Box>
            <SectionTitle
                action={
                    <Box sx={{display: 'flex', alignItems: 'center', gap: 1}}>
                        <ToggleButtonGroup size="small" value={activeTypes} onChange={handleTypeFilter} color="primary">
                            <ToggleButton value={EventTypeEnum.LOGS}>
                                <Badge badgeContent={counters[EventTypeEnum.LOGS]} color="primary" max={9999}>
                                    <TableRowsIcon sx={{fontSize: 18, mr: 0.5}} />
                                </Badge>
                                &nbsp;Logs
                            </ToggleButton>
                            <ToggleButton value={EventTypeEnum.VAR_DUMPER}>
                                <Badge badgeContent={counters[EventTypeEnum.VAR_DUMPER]} color="secondary" max={9999}>
                                    <DataObjectIcon sx={{fontSize: 18, mr: 0.5}} />
                                </Badge>
                                &nbsp;Dump
                            </ToggleButton>
                        </ToggleButtonGroup>
                        <Tooltip title="Clear all">
                            <IconButton size="small" onClick={handleClear}>
                                <Badge badgeContent={events.length} color="default" max={9999}>
                                    <DeleteSweepIcon />
                                </Badge>
                            </IconButton>
                        </Tooltip>
                    </Box>
                }
            >
                Dev Server
            </SectionTitle>

            {filtered.length === 0 ? (
                <Box sx={{textAlign: 'center', py: 8, color: 'text.disabled'}}>
                    <DataObjectIcon sx={{fontSize: 48, mb: 1, opacity: 0.3}} />
                    <Typography variant="body2">Waiting for events...</Typography>
                    <Typography variant="caption">
                        Start the debug server with <code>adp dev</code> and send data from your application
                    </Typography>
                </Box>
            ) : (
                <List disablePadding>
                    {filtered.map((event) => (
                        <StyledListItem key={event.id}>
                            <ListItemIcon sx={{minWidth: 40, pt: 0.5}}>
                                <DebugEntryIcon type={event.type} />
                            </ListItemIcon>
                            <ListItemText
                                disableTypography
                                primary={<DebugEntryContent type={event.type} data={event.data} />}
                                secondary={<TimeLabel color="text.disabled">{formatTime(event.time)}</TimeLabel>}
                            />
                        </StyledListItem>
                    ))}
                </List>
            )}
        </Box>
    );
};
