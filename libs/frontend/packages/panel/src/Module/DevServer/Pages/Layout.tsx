import {useSelector} from '@app-dev-panel/panel/store';
import {JsonRenderer} from '@app-dev-panel/sdk/Component/JsonRenderer';
import {SectionTitle} from '@app-dev-panel/sdk/Component/SectionTitle';
import {primitives} from '@app-dev-panel/sdk/Component/Theme/tokens';
import {useDevServerEvents} from '@app-dev-panel/sdk/Component/useDevServerEvents';
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
import {styled, type Theme, useTheme} from '@mui/material/styles';
import {type MouseEvent, useCallback, useState} from 'react';

enum EventTypeEnum {
    VAR_DUMPER = 27,
    LOGS = 43,
}

type EventItem = {data: string; time: Date; type: EventTypeEnum};

const levelColor = (level: string, theme: Theme): string => {
    switch (level) {
        case 'emergency':
        case 'alert':
        case 'critical':
        case 'error':
            return theme.palette.error.main;
        case 'warning':
            return theme.palette.warning.main;
        case 'notice':
            return theme.palette.primary.main;
        case 'info':
            return theme.palette.success.main;
        case 'debug':
            return theme.palette.text.disabled;
        default:
            return theme.palette.text.disabled;
    }
};

const StyledListItem = styled(ListItem)(({theme}) => ({
    alignItems: 'flex-start',
    borderBottom: `1px solid ${theme.palette.divider}`,
    paddingTop: theme.spacing(1.5),
    paddingBottom: theme.spacing(1.5),
}));

const TimeLabel = styled(Typography)({fontFamily: primitives.fontFamilyMono, fontSize: '11px', whiteSpace: 'nowrap'});

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

function DebugEntryContent({data, type}: {data: string; type: EventTypeEnum}) {
    const theme = useTheme();

    if (type === EventTypeEnum.VAR_DUMPER) {
        try {
            return <JsonRenderer value={JSON.parse(data)} />;
        } catch {
            return <Typography sx={{fontSize: '13px', wordBreak: 'break-word'}}>{data}</Typography>;
        }
    }

    if (type === EventTypeEnum.LOGS) {
        try {
            const parsed = JSON.parse(data);
            const level = parsed.level || 'info';
            const color = levelColor(level, theme);
            return (
                <Box>
                    <Box sx={{display: 'flex', alignItems: 'center', gap: 1, mb: 0.5}}>
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
                    </Box>
                    {parsed.context && Object.keys(parsed.context).length > 0 && (
                        <JsonRenderer value={parsed.context} depth={2} />
                    )}
                </Box>
            );
        } catch {
            return <Typography sx={{fontSize: '13px', wordBreak: 'break-word'}}>{data}</Typography>;
        }
    }

    return <Typography sx={{fontSize: '13px', wordBreak: 'break-word'}}>{data}</Typography>;
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
    const [events, setEvents] = useState<EventItem[]>([]);
    const [counters, setCounters] = useState<Record<EventTypeEnum, number>>({
        [EventTypeEnum.VAR_DUMPER]: 0,
        [EventTypeEnum.LOGS]: 0,
    });
    const backendUrl = useSelector((state) => state.application.baseUrl);

    const onMessage = useCallback((m: MessageEvent) => {
        let data: [number, string];
        try {
            data = JSON.parse(m.data);
        } catch {
            return;
        }
        const type = data[0] as EventTypeEnum;
        setCounters((prev) => ({...prev, [type]: (prev[type] || 0) + 1}));
        setEvents((prev) => [{data: data[1], time: new Date(), type}, ...prev]);
    }, []);

    useDevServerEvents(backendUrl, onMessage, true);

    const [activeTypes, setActiveTypes] = useState<EventTypeEnum[]>([EventTypeEnum.VAR_DUMPER, EventTypeEnum.LOGS]);

    const handleTypeFilter = (_event: MouseEvent<HTMLElement>, types: EventTypeEnum[]) => {
        setActiveTypes(types);
    };

    const handleClear = () => {
        setEvents([]);
        setCounters({[EventTypeEnum.VAR_DUMPER]: 0, [EventTypeEnum.LOGS]: 0});
    };

    const filtered = events.filter((e) => activeTypes.includes(e.type));

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
                    {filtered.map((event, index) => (
                        <StyledListItem key={index}>
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
