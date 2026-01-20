import HttpIcon from '@mui/icons-material/Http';
import RefreshIcon from '@mui/icons-material/Refresh';
import SyncAltIcon from '@mui/icons-material/SyncAlt';
import TerminalIcon from '@mui/icons-material/Terminal';
import {
    Button,
    CircularProgress,
    ListItemIcon,
    ListItemText,
    ToggleButton,
    ToggleButtonGroup,
    Tooltip,
} from '@mui/material';
import Dialog from '@mui/material/Dialog';
import DialogTitle from '@mui/material/DialogTitle';
import List from '@mui/material/List';
import ListItemButton from '@mui/material/ListItemButton';
import {useCurrentPageRequestIds, useDebugEntry} from '@yiisoft/yii-dev-panel-sdk/API/Debug/Context';
import {DebugEntry, useGetDebugQuery} from '@yiisoft/yii-dev-panel-sdk/API/Debug/Debug';
import {DebugEntryChip} from '@yiisoft/yii-dev-panel-sdk/Component/DebugEntryChip';
import {isDebugEntryAboutConsole, isDebugEntryAboutWeb} from '@yiisoft/yii-dev-panel-sdk/Helper/debugEntry';
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

export type DebugEntriesListModalProps = {
    open: boolean;
    onClick: (entry: DebugEntry) => void;
    onClose: () => void;
};

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
            <DialogTitle>Select a debug entry</DialogTitle>
            <List sx={{pt: 0}}>
                <ToggleButtonGroup fullWidth size="small" color="primary" value={filters} onChange={handleFormat}>
                    <ToggleButton value="web">
                        <HttpIcon />
                    </ToggleButton>
                    <ToggleButton value="console">
                        <TerminalIcon />
                    </ToggleButton>
                    <ToggleButton value="current">Current</ToggleButton>
                    <Button color="primary" onClick={() => getDebugQuery.refetch()} disabled={getDebugQuery.isFetching}>
                        {getDebugQuery.isFetching ? <CircularProgress size={24} color="info" /> : <RefreshIcon />}
                    </Button>
                </ToggleButtonGroup>
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
        </Dialog>
    );
};
