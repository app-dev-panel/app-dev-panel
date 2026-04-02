import {useCurrentPageRequestIds, useDebugEntry} from '@app-dev-panel/sdk/API/Debug/Context';
import {DebugEntry, useGetDebugQuery} from '@app-dev-panel/sdk/API/Debug/Debug';
import {DebugEntryChip} from '@app-dev-panel/sdk/Component/DebugEntryChip';
import {isDebugEntryAboutConsole, isDebugEntryAboutWeb} from '@app-dev-panel/sdk/Helper/debugEntry';
import {Close} from '@mui/icons-material';
import HttpIcon from '@mui/icons-material/Http';
import RefreshIcon from '@mui/icons-material/Refresh';
import SyncAltIcon from '@mui/icons-material/SyncAlt';
import TerminalIcon from '@mui/icons-material/Terminal';
import {
    Box,
    CircularProgress,
    IconButton,
    ListItemIcon,
    ListItemText,
    ToggleButton,
    ToggleButtonGroup,
    Tooltip,
} from '@mui/material';
import Dialog from '@mui/material/Dialog';
import DialogContent from '@mui/material/DialogContent';
import DialogTitle from '@mui/material/DialogTitle';
import List from '@mui/material/List';
import ListItemButton from '@mui/material/ListItemButton';
import React, {MouseEvent, useEffect, useState} from 'react';

type DebugEntryItemProps = {
    entry: DebugEntry;
    selected: boolean;
    rightText: string | null;
    onClick: (entry: DebugEntry) => void;
};

const DebugEntryItem = React.memo(({entry, onClick, selected, rightText}: DebugEntryItemProps) => {
    return (
        <ListItemButton onClick={() => onClick(entry)} defaultChecked={selected}>
            <ListItemIcon>
                <DebugEntryChip entry={entry} />
            </ListItemIcon>
            <ListItemText primary={entry.request?.path ?? entry.command?.input} />
            {rightText && (
                <Tooltip title="The request was made by the current page">
                    <SyncAltIcon />
                </Tooltip>
            )}
        </ListItemButton>
    );
});

const filterDebugEntry = (filters: string[], currentPageRequestIds: string[]) => (entry: DebugEntry) => {
    let result = false;
    if (filters.includes('web') && isDebugEntryAboutWeb(entry)) {
        result = true;
    }
    if (filters.includes('console') && isDebugEntryAboutConsole(entry)) {
        result = true;
    }
    if (filters.includes('current') && currentPageRequestIds.includes(entry.id)) {
        result = true;
    }
    return result;
};

export type DebugEntriesListModalProps = {open: boolean; onClick: (entry: DebugEntry) => void; onClose: () => void};

export const DebugEntriesListModal = ({onClick, onClose, open}: DebugEntriesListModalProps) => {
    const getDebugQuery = useGetDebugQuery();
    const currentEntry = useDebugEntry();
    const [entries, setEntries] = useState<DebugEntry[]>([]);
    const [filters, setFilters] = useState(() => ['web', 'console', 'current']);
    const currentPageRequestIds = useCurrentPageRequestIds();

    const handleFormat = (event: MouseEvent<HTMLElement>, newFormats: string[]) => {
        setFilters(newFormats);
    };
    useEffect(() => {
        if (!getDebugQuery.isFetching && getDebugQuery.data && getDebugQuery.data.length > 0) {
            setEntries(getDebugQuery.data);
        }
    }, [getDebugQuery.isFetching]);

    return (
        <Dialog fullWidth onClose={() => onClose()} open={open}>
            <DialogTitle sx={{display: 'flex', alignItems: 'center', justifyContent: 'space-between', pb: 1}}>
                Select a debug entry
                <IconButton size="small" onClick={onClose} aria-label="close" sx={{color: 'text.secondary'}}>
                    <Close fontSize="small" />
                </IconButton>
            </DialogTitle>
            <DialogContent dividers sx={{p: 0}}>
                <Box sx={{px: 1, py: 1}}>
                    <ToggleButtonGroup fullWidth size="small" color="primary" value={filters} onChange={handleFormat}>
                        <ToggleButton value="web">
                            <HttpIcon />
                        </ToggleButton>
                        <ToggleButton value="console">
                            <TerminalIcon />
                        </ToggleButton>
                        <ToggleButton value="current">Current</ToggleButton>
                    </ToggleButtonGroup>
                    <IconButton
                        color="primary"
                        onClick={() => getDebugQuery.refetch()}
                        disabled={getDebugQuery.isFetching}
                        aria-label="Refresh"
                        sx={{ml: 1}}
                    >
                        {getDebugQuery.isFetching ? <CircularProgress size={20} /> : <RefreshIcon />}
                    </IconButton>
                </Box>
                <List disablePadding>
                    {entries.filter(filterDebugEntry(filters, currentPageRequestIds)).map((entry) => (
                        <DebugEntryItem
                            key={entry.id}
                            entry={entry}
                            onClick={onClick}
                            selected={currentEntry && entry.id === currentEntry.id}
                            rightText={currentPageRequestIds.includes(entry.id) ? 'Current' : null}
                        />
                    ))}
                </List>
            </DialogContent>
        </Dialog>
    );
};
