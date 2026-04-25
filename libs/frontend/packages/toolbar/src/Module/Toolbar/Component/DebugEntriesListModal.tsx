import {useCurrentPageRequestIds, useDebugEntry} from '@app-dev-panel/sdk/API/Debug/Context';
import {DebugEntry, useGetDebugQuery} from '@app-dev-panel/sdk/API/Debug/Debug';
import {isDebugEntryAboutConsole, isDebugEntryAboutWeb} from '@app-dev-panel/sdk/Helper/debugEntry';
import CloseIcon from '@mui/icons-material/Close';
import RefreshIcon from '@mui/icons-material/Refresh';
import SearchIcon from '@mui/icons-material/Search';
import {
    Box,
    Chip,
    CircularProgress,
    Dialog,
    DialogContent,
    DialogTitle,
    IconButton,
    InputAdornment,
    TextField,
    ToggleButton,
    ToggleButtonGroup,
    Typography,
} from '@mui/material';
import React, {MouseEvent, useCallback, useEffect, useMemo, useState} from 'react';

const METHOD_COLORS: Record<string, string> = {
    GET: '#2563EB',
    POST: '#16A34A',
    PUT: '#D97706',
    PATCH: '#D97706',
    DELETE: '#DC2626',
    HEAD: '#6B7280',
    OPTIONS: '#8B5CF6',
};

const statusColor = (code: number | undefined): string => {
    if (!code) return '#6B7280';
    if (code < 300) return '#16A34A';
    if (code < 400) return '#2563EB';
    if (code < 500) return '#D97706';
    return '#DC2626';
};

const formatTime = (startTime: number): string => {
    const d = new Date(startTime * 1000);
    return d.toLocaleTimeString('en-US', {hour12: false, hour: '2-digit', minute: '2-digit', second: '2-digit'});
};

const formatDuration = (seconds: number): string => {
    const ms = seconds * 1000;
    if (ms < 1) return '<1ms';
    if (ms < 1000) return `${Math.round(ms)}ms`;
    return `${seconds.toFixed(2)}s`;
};

const formatMemory = (bytes: number): string => {
    if (bytes < 1024 * 1024) return `${(bytes / 1024).toFixed(0)}KB`;
    return `${(bytes / (1024 * 1024)).toFixed(1)}MB`;
};

type EntryRowProps = {
    entry: DebugEntry;
    selected: boolean;
    isCurrentPage: boolean;
    onClick: (entry: DebugEntry) => void;
};

const EntryRow = React.memo(({entry, selected, isCurrentPage, onClick}: EntryRowProps) => {
    const isWeb = isDebugEntryAboutWeb(entry);
    const isConsole = isDebugEntryAboutConsole(entry);
    const timing = entry.web || entry.console;
    const method = entry.request?.method ?? '';
    const path = entry.request?.path ?? entry.command?.input ?? '';
    const status = entry.response?.statusCode;
    const exitCode = entry.command?.exitCode;

    return (
        <Box
            onClick={() => onClick(entry)}
            sx={{
                display: 'flex',
                alignItems: 'center',
                gap: 1,
                px: 1.5,
                py: 0.75,
                cursor: 'pointer',
                borderBottom: 1,
                borderColor: 'divider',
                bgcolor: selected ? 'action.selected' : isCurrentPage ? 'primary.light' : 'transparent',
                transition: 'background-color 100ms ease',
                '&:hover': {bgcolor: selected ? 'action.selected' : 'action.hover'},
                '&:last-child': {borderBottom: 0},
            }}
        >
            {/* Time */}
            <Typography
                sx={{
                    fontFamily: "'JetBrains Mono', monospace",
                    fontSize: 11,
                    color: 'text.disabled',
                    flexShrink: 0,
                    width: 56,
                }}
            >
                {timing ? formatTime(timing.request.startTime) : ''}
            </Typography>

            {/* Method badge or console indicator */}
            {isWeb && (
                <Chip
                    label={method}
                    size="small"
                    sx={{
                        height: 20,
                        minWidth: 52,
                        fontFamily: "'JetBrains Mono', monospace",
                        fontSize: 10,
                        fontWeight: 700,
                        bgcolor: METHOD_COLORS[method] ?? '#6B7280',
                        color: '#fff',
                        borderRadius: 0.5,
                        '& .MuiChip-label': {px: 0.75},
                    }}
                />
            )}
            {isConsole && (
                <Chip
                    label="CLI"
                    size="small"
                    sx={{
                        height: 20,
                        minWidth: 52,
                        fontFamily: "'JetBrains Mono', monospace",
                        fontSize: 10,
                        fontWeight: 700,
                        bgcolor: '#6B7280',
                        color: '#fff',
                        borderRadius: 0.5,
                        '& .MuiChip-label': {px: 0.75},
                    }}
                />
            )}

            {/* Path */}
            <Typography
                sx={{
                    flex: 1,
                    fontFamily: "'JetBrains Mono', monospace",
                    fontSize: 12,
                    color: 'text.primary',
                    overflow: 'hidden',
                    textOverflow: 'ellipsis',
                    whiteSpace: 'nowrap',
                }}
            >
                {path}
            </Typography>

            {/* Status code */}
            {isWeb && status != null && (
                <Typography
                    sx={{
                        fontFamily: "'JetBrains Mono', monospace",
                        fontSize: 11,
                        fontWeight: 600,
                        color: statusColor(status),
                        flexShrink: 0,
                        width: 28,
                        textAlign: 'right',
                    }}
                >
                    {status}
                </Typography>
            )}
            {isConsole && exitCode != null && (
                <Typography
                    sx={{
                        fontFamily: "'JetBrains Mono', monospace",
                        fontSize: 11,
                        fontWeight: 600,
                        color: exitCode === 0 ? '#16A34A' : '#DC2626',
                        flexShrink: 0,
                    }}
                >
                    exit {exitCode}
                </Typography>
            )}

            {/* Duration */}
            {timing && (
                <Typography
                    sx={{
                        fontFamily: "'JetBrains Mono', monospace",
                        fontSize: 11,
                        color: 'text.secondary',
                        flexShrink: 0,
                        width: 48,
                        textAlign: 'right',
                    }}
                >
                    {formatDuration(timing.request.processingTime)}
                </Typography>
            )}

            {/* Memory */}
            {timing && (
                <Typography
                    sx={{
                        fontFamily: "'JetBrains Mono', monospace",
                        fontSize: 11,
                        color: 'text.disabled',
                        flexShrink: 0,
                        width: 48,
                        textAlign: 'right',
                    }}
                >
                    {formatMemory(timing.memory.peakUsage)}
                </Typography>
            )}

            {/* Current page indicator */}
            {isCurrentPage && (
                <Box sx={{width: 6, height: 6, borderRadius: '50%', bgcolor: 'primary.main', flexShrink: 0}} />
            )}
        </Box>
    );
});

const filterDebugEntry = (filters: string[], currentPageRequestIds: string[]) => (entry: DebugEntry) => {
    let result = false;
    if (filters.includes('web') && isDebugEntryAboutWeb(entry)) result = true;
    if (filters.includes('console') && isDebugEntryAboutConsole(entry)) result = true;
    if (filters.includes('current') && currentPageRequestIds.includes(entry.id)) result = true;
    return result;
};

export type DebugEntriesListModalProps = {open: boolean; onClick: (entry: DebugEntry) => void; onClose: () => void};

export const DebugEntriesListModal = ({onClick, onClose, open}: DebugEntriesListModalProps) => {
    const getDebugQuery = useGetDebugQuery();
    const currentEntry = useDebugEntry();
    const [entries, setEntries] = useState<DebugEntry[]>([]);
    const [filters, setFilters] = useState(() => ['web', 'console', 'current']);
    const [search, setSearch] = useState('');
    const currentPageRequestIds = useCurrentPageRequestIds();

    const handleFormat = useCallback((_event: MouseEvent<HTMLElement>, newFormats: string[]) => {
        setFilters(newFormats);
    }, []);

    useEffect(() => {
        if (!getDebugQuery.isFetching && getDebugQuery.data && getDebugQuery.data.length > 0) {
            setEntries(getDebugQuery.data);
        }
    }, [getDebugQuery.isFetching]);

    const filteredEntries = useMemo(() => {
        let result = entries.filter(filterDebugEntry(filters, currentPageRequestIds));
        if (search.trim()) {
            const q = search.toLowerCase();
            result = result.filter((entry) => {
                const path = entry.request?.path ?? entry.command?.input ?? '';
                const method = entry.request?.method ?? '';
                const status = String(entry.response?.statusCode ?? '');
                return path.toLowerCase().includes(q) || method.toLowerCase().includes(q) || status.includes(q);
            });
        }
        return result;
    }, [entries, filters, currentPageRequestIds, search]);

    return (
        <Dialog
            fullWidth
            maxWidth="sm"
            onClose={onClose}
            open={open}
            PaperProps={{sx: {borderRadius: 2, maxHeight: '70vh'}}}
        >
            <DialogTitle sx={{display: 'flex', alignItems: 'center', gap: 1, pb: 1, pt: 1.5, px: 2}}>
                <Typography sx={{fontSize: 15, fontWeight: 600, flex: 1}}>Debug Entries</Typography>
                <Chip
                    label={filteredEntries.length}
                    size="small"
                    sx={{height: 20, fontSize: 11, fontWeight: 600, fontFamily: "'JetBrains Mono', monospace"}}
                />
                <IconButton
                    size="small"
                    onClick={() => getDebugQuery.refetch()}
                    disabled={getDebugQuery.isFetching}
                    sx={{color: 'text.secondary'}}
                >
                    {getDebugQuery.isFetching ? <CircularProgress size={16} /> : <RefreshIcon sx={{fontSize: 18}} />}
                </IconButton>
                <IconButton size="small" onClick={onClose} sx={{color: 'text.secondary'}}>
                    <CloseIcon sx={{fontSize: 18}} />
                </IconButton>
            </DialogTitle>

            <Box sx={{px: 2, pb: 1, display: 'flex', gap: 1, alignItems: 'center'}}>
                <TextField
                    size="small"
                    placeholder="Filter by path, method, status..."
                    value={search}
                    onChange={(e) => setSearch(e.target.value)}
                    slotProps={{
                        input: {
                            startAdornment: (
                                <InputAdornment position="start">
                                    <SearchIcon sx={{fontSize: 18, color: 'text.disabled'}} />
                                </InputAdornment>
                            ),
                            sx: {fontSize: 13, height: 34},
                        },
                    }}
                    sx={{flex: 1}}
                />
                <ToggleButtonGroup size="small" value={filters} onChange={handleFormat} sx={{height: 34}}>
                    <ToggleButton value="web" sx={{px: 1.5, fontSize: 11, textTransform: 'none'}}>
                        HTTP
                    </ToggleButton>
                    <ToggleButton value="console" sx={{px: 1.5, fontSize: 11, textTransform: 'none'}}>
                        CLI
                    </ToggleButton>
                    <ToggleButton value="current" sx={{px: 1.5, fontSize: 11, textTransform: 'none'}}>
                        Page
                    </ToggleButton>
                </ToggleButtonGroup>
            </Box>

            <DialogContent dividers sx={{p: 0}}>
                {/* Column headers */}
                <Box
                    sx={{
                        display: 'flex',
                        alignItems: 'center',
                        gap: 1,
                        px: 1.5,
                        py: 0.5,
                        borderBottom: 1,
                        borderColor: 'divider',
                        bgcolor: 'action.hover',
                    }}
                >
                    <Typography
                        sx={{
                            fontSize: 10,
                            fontWeight: 600,
                            color: 'text.disabled',
                            width: 56,
                            textTransform: 'uppercase',
                            letterSpacing: 0.5,
                        }}
                    >
                        Time
                    </Typography>
                    <Typography
                        sx={{
                            fontSize: 10,
                            fontWeight: 600,
                            color: 'text.disabled',
                            width: 52,
                            textTransform: 'uppercase',
                            letterSpacing: 0.5,
                        }}
                    >
                        Method
                    </Typography>
                    <Typography
                        sx={{
                            fontSize: 10,
                            fontWeight: 600,
                            color: 'text.disabled',
                            flex: 1,
                            textTransform: 'uppercase',
                            letterSpacing: 0.5,
                        }}
                    >
                        Path
                    </Typography>
                    <Typography
                        sx={{
                            fontSize: 10,
                            fontWeight: 600,
                            color: 'text.disabled',
                            width: 28,
                            textAlign: 'right',
                            textTransform: 'uppercase',
                            letterSpacing: 0.5,
                        }}
                    >
                        Status
                    </Typography>
                    <Typography
                        sx={{
                            fontSize: 10,
                            fontWeight: 600,
                            color: 'text.disabled',
                            width: 48,
                            textAlign: 'right',
                            textTransform: 'uppercase',
                            letterSpacing: 0.5,
                        }}
                    >
                        Time
                    </Typography>
                    <Typography
                        sx={{
                            fontSize: 10,
                            fontWeight: 600,
                            color: 'text.disabled',
                            width: 48,
                            textAlign: 'right',
                            textTransform: 'uppercase',
                            letterSpacing: 0.5,
                        }}
                    >
                        Mem
                    </Typography>
                    <Box sx={{width: 6}} />
                </Box>

                {filteredEntries.length === 0 && (
                    <Box sx={{py: 4, textAlign: 'center'}}>
                        <Typography sx={{fontSize: 13, color: 'text.disabled'}}>
                            {entries.length === 0 ? 'No debug entries yet' : 'No entries match the filters'}
                        </Typography>
                    </Box>
                )}

                {filteredEntries.map((entry) => (
                    <EntryRow
                        key={entry.id}
                        entry={entry}
                        onClick={onClick}
                        selected={!!(currentEntry && entry.id === currentEntry.id)}
                        isCurrentPage={currentPageRequestIds.includes(entry.id)}
                    />
                ))}
            </DialogContent>
        </Dialog>
    );
};
