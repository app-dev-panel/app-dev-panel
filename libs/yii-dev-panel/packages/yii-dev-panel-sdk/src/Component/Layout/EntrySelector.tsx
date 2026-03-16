import {Box, Chip, Icon, Popover, Typography} from '@mui/material';
import {styled} from '@mui/material/styles';
import {DebugEntry} from '@yiisoft/yii-dev-panel-sdk/API/Debug/Debug';
import {primitives} from '@yiisoft/yii-dev-panel-sdk/Component/Theme/tokens';
import {isDebugEntryAboutConsole, isDebugEntryAboutWeb} from '@yiisoft/yii-dev-panel-sdk/Helper/debugEntry';
import {formatDate} from '@yiisoft/yii-dev-panel-sdk/Helper/formatDate';
import {useState} from 'react';

type EntrySelectorProps = {
    anchorEl: HTMLElement | null;
    open: boolean;
    onClose: () => void;
    entries: DebugEntry[];
    currentEntryId?: string;
    onSelect: (entry: DebugEntry) => void;
};

const EntryRow = styled(Box, {shouldForwardProp: (p) => p !== 'active'})<{active?: boolean}>(({theme, active}) => ({
    display: 'flex',
    alignItems: 'center',
    gap: theme.spacing(1.25),
    padding: theme.spacing(1, 2),
    cursor: 'pointer',
    fontFamily: primitives.fontFamilyMono,
    fontSize: '13px',
    backgroundColor: active ? theme.palette.primary.light : 'transparent',
    '&:hover': {backgroundColor: theme.palette.action.hover},
}));

const MethodLabel = styled('span')({fontWeight: 600, fontSize: '11px', minWidth: 40});

const PathLabel = styled('span')({flex: 1, fontSize: '13px'});

const StatusLabel = styled('span')({fontWeight: 500, fontSize: '12px'});

const TimeLabel = styled('span')({fontSize: '11px', color: primitives.gray400});

const statusColor = (status: number): string => {
    if (status >= 500) return primitives.red600;
    if (status >= 400) return primitives.amber600;
    return primitives.green600;
};

const methodColor = (method: string): string => {
    switch (method.toUpperCase()) {
        case 'GET':
            return primitives.green600;
        case 'POST':
            return primitives.blue500;
        case 'PUT':
        case 'PATCH':
            return primitives.amber600;
        case 'DELETE':
            return primitives.red600;
        default:
            return primitives.gray600;
    }
};

const FilterInput = styled('input')(({theme}) => ({
    width: '100%',
    border: 'none',
    outline: 'none',
    fontSize: '13px',
    fontFamily: primitives.fontFamily,
    backgroundColor: 'transparent',
    color: theme.palette.text.primary,
    padding: theme.spacing(1.25, 2),
    borderBottom: `1px solid ${theme.palette.divider}`,
    '&::placeholder': {color: theme.palette.text.disabled},
}));

export const EntrySelector = ({anchorEl, open, onClose, entries, currentEntryId, onSelect}: EntrySelectorProps) => {
    const [filter, setFilter] = useState('');

    const filtered = filter
        ? entries.filter((e) => {
              const q = filter.toLowerCase();
              if (isDebugEntryAboutWeb(e)) {
                  return (
                      e.request.path.toLowerCase().includes(q) ||
                      e.request.method.toLowerCase().includes(q) ||
                      String(e.response.statusCode).includes(q)
                  );
              }
              if (isDebugEntryAboutConsole(e)) {
                  return e.command?.input?.toLowerCase().includes(q) || false;
              }
              return e.id.includes(q);
          })
        : entries;

    return (
        <Popover
            open={open}
            anchorEl={anchorEl}
            onClose={() => {
                onClose();
                setFilter('');
            }}
            anchorOrigin={{vertical: 'bottom', horizontal: 'left'}}
            transformOrigin={{vertical: 'top', horizontal: 'left'}}
            slotProps={{paper: {sx: {width: 480, maxHeight: 400, mt: 0.5, borderRadius: 1.5}}}}
        >
            <FilterInput placeholder="Filter entries..." value={filter} onChange={(e) => setFilter(e.target.value)} />
            <Box sx={{overflowY: 'auto', maxHeight: 340}}>
                {filtered.length === 0 && (
                    <Box sx={{textAlign: 'center', py: 3, color: 'text.disabled'}}>
                        <Typography variant="body2">No entries found</Typography>
                    </Box>
                )}
                {filtered.map((entry) => {
                    const active = entry.id === currentEntryId;
                    if (isDebugEntryAboutWeb(entry)) {
                        return (
                            <EntryRow
                                key={entry.id}
                                active={active}
                                onClick={() => {
                                    onSelect(entry);
                                    onClose();
                                    setFilter('');
                                }}
                            >
                                <MethodLabel sx={{color: methodColor(entry.request.method)}}>
                                    {entry.request.method}
                                </MethodLabel>
                                <PathLabel>{entry.request.path}</PathLabel>
                                <StatusLabel sx={{color: statusColor(entry.response.statusCode)}}>
                                    {entry.response.statusCode}
                                </StatusLabel>
                                <TimeLabel>{formatDate(entry.web.request.startTime)}</TimeLabel>
                            </EntryRow>
                        );
                    }
                    if (isDebugEntryAboutConsole(entry)) {
                        return (
                            <EntryRow
                                key={entry.id}
                                active={active}
                                onClick={() => {
                                    onSelect(entry);
                                    onClose();
                                    setFilter('');
                                }}
                            >
                                <Icon sx={{fontSize: 14, color: 'text.disabled'}}>terminal</Icon>
                                <PathLabel>{entry.command?.input ?? 'Unknown'}</PathLabel>
                                <Chip
                                    label={entry.command?.exitCode === 0 ? 'OK' : `EXIT ${entry.command?.exitCode}`}
                                    size="small"
                                    color={entry.command?.exitCode === 0 ? 'success' : 'error'}
                                    sx={{height: 18, fontSize: '10px'}}
                                />
                                <TimeLabel>{formatDate(entry.console.request.startTime)}</TimeLabel>
                            </EntryRow>
                        );
                    }
                    return (
                        <EntryRow
                            key={entry.id}
                            active={active}
                            onClick={() => {
                                onSelect(entry);
                                onClose();
                                setFilter('');
                            }}
                        >
                            <PathLabel>{entry.id}</PathLabel>
                        </EntryRow>
                    );
                })}
            </Box>
        </Popover>
    );
};
