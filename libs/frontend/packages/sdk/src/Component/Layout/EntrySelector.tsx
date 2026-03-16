import {DebugEntry} from '@app-dev-panel/sdk/API/Debug/Debug';
import {primitives} from '@app-dev-panel/sdk/Component/Theme/tokens';
import {isDebugEntryAboutConsole, isDebugEntryAboutWeb} from '@app-dev-panel/sdk/Helper/debugEntry';
import {formatBytes} from '@app-dev-panel/sdk/Helper/formatBytes';
import {formatDate} from '@app-dev-panel/sdk/Helper/formatDate';
import {Box, Chip, Icon, Popover, type Theme, Typography} from '@mui/material';
import {styled, useTheme} from '@mui/material/styles';
import React, {useEffect, useRef, useState} from 'react';

// ---------------------------------------------------------------------------
// Fuzzy search utilities
// ---------------------------------------------------------------------------

type FuzzyMatch = {score: number; indices: number[]};

/**
 * Fuzzy-match `query` against `text`.
 * Returns null if no match, or {score, indices} where indices are
 * the character positions in `text` that matched.
 * Score: lower is better. Consecutive matches and early matches score best.
 */
export function fuzzyMatch(text: string, query: string): FuzzyMatch | null {
    const textLower = text.toLowerCase();
    const queryLower = query.toLowerCase();

    if (queryLower.length === 0) return {score: 0, indices: []};

    const indices: number[] = [];
    let qi = 0;

    for (let ti = 0; ti < textLower.length && qi < queryLower.length; ti++) {
        if (textLower[ti] === queryLower[qi]) {
            indices.push(ti);
            qi++;
        }
    }

    if (qi < queryLower.length) return null; // not all query chars matched

    // Score: penalize gaps between matched characters and late starts
    let score = indices[0]; // penalize late start
    for (let i = 1; i < indices.length; i++) {
        const gap = indices[i] - indices[i - 1] - 1;
        score += gap * 2; // penalize gaps
    }

    // Bonus for exact substring match
    if (textLower.includes(queryLower)) {
        score -= queryLower.length * 3;
    }

    return {score, indices};
}

/**
 * Renders text with fuzzy-matched characters highlighted.
 */
export const HighlightedText = ({text, indices}: {text: string; indices: number[]}) => {
    if (indices.length === 0) return <>{text}</>;

    const indexSet = new Set(indices);
    const parts: React.ReactNode[] = [];
    let current = '';
    let isHighlighted = false;

    for (let i = 0; i < text.length; i++) {
        const charHighlighted = indexSet.has(i);
        if (charHighlighted !== isHighlighted) {
            if (current) {
                parts.push(
                    isHighlighted ? (
                        <mark
                            key={`${i}-m`}
                            style={{
                                backgroundColor: 'transparent',
                                color: 'inherit',
                                fontWeight: 600,
                                textDecoration: 'underline',
                            }}
                        >
                            {current}
                        </mark>
                    ) : (
                        <span key={`${i}-s`}>{current}</span>
                    ),
                );
            }
            current = '';
            isHighlighted = charHighlighted;
        }
        current += text[i];
    }
    if (current) {
        parts.push(
            isHighlighted ? (
                <mark
                    key="end-m"
                    style={{
                        backgroundColor: 'transparent',
                        color: 'inherit',
                        fontWeight: 600,
                        textDecoration: 'underline',
                    }}
                >
                    {current}
                </mark>
            ) : (
                <span key="end-s">{current}</span>
            ),
        );
    }
    return <>{parts}</>;
};

// ---------------------------------------------------------------------------
// Types
// ---------------------------------------------------------------------------

type EntrySelectorProps = {
    anchorEl: HTMLElement | null;
    open: boolean;
    onClose: () => void;
    entries: DebugEntry[];
    currentEntryId?: string;
    onSelect: (entry: DebugEntry) => void;
};

type MatchedEntry = {entry: DebugEntry; indices: number[]; searchText: string};

// ---------------------------------------------------------------------------
// Styled components
// ---------------------------------------------------------------------------

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

const PathLabel = styled('span')({
    flex: 1,
    fontSize: '13px',
    overflow: 'hidden',
    textOverflow: 'ellipsis',
    whiteSpace: 'nowrap',
});

const StatusLabel = styled('span')({fontWeight: 500, fontSize: '12px'});

const TimeLabel = styled('span')(({theme}) => ({fontSize: '11px', color: theme.palette.text.disabled}));

const statusColor = (status: number, theme: Theme): string => {
    if (status >= 500) return theme.palette.error.main;
    if (status >= 400) return theme.palette.warning.main;
    return theme.palette.success.main;
};

const methodColor = (method: string, theme: Theme): string => {
    switch (method.toUpperCase()) {
        case 'GET':
            return theme.palette.success.main;
        case 'POST':
            return theme.palette.primary.main;
        case 'PUT':
        case 'PATCH':
            return theme.palette.warning.main;
        case 'DELETE':
            return theme.palette.error.main;
        default:
            return theme.palette.text.secondary;
    }
};

const FilterInput = styled('input')(({theme}) => ({
    width: '100%',
    border: 'none',
    outline: 'none',
    fontSize: '13px',
    fontFamily: theme.typography.fontFamily,
    backgroundColor: 'transparent',
    color: theme.palette.text.primary,
    padding: theme.spacing(1.25, 2),
    borderBottom: `1px solid ${theme.palette.divider}`,
    '&::placeholder': {color: theme.palette.text.disabled},
}));

const CountLabel = styled(Typography)(({theme}) => ({
    fontSize: '11px',
    color: theme.palette.text.disabled,
    padding: theme.spacing(0.5, 2),
    borderTop: `1px solid ${theme.palette.divider}`,
    textAlign: 'right',
}));

// ---------------------------------------------------------------------------
// Helper: get searchable text for an entry
// ---------------------------------------------------------------------------

function getSearchText(entry: DebugEntry): string {
    if (isDebugEntryAboutWeb(entry)) {
        return `${entry.request.method} ${entry.request.path}`;
    }
    if (isDebugEntryAboutConsole(entry)) {
        return entry.command?.input ?? entry.command?.name ?? '';
    }
    return entry.id;
}

// ---------------------------------------------------------------------------
// Component
// ---------------------------------------------------------------------------

export const EntrySelector = ({anchorEl, open, onClose, entries, currentEntryId, onSelect}: EntrySelectorProps) => {
    const theme = useTheme();
    const [filter, setFilter] = useState('');
    const inputRef = useRef<HTMLInputElement>(null);

    // Auto-focus input when popover opens
    useEffect(() => {
        if (open) {
            setFilter('');
            // Small delay to let Popover render
            const timer = setTimeout(() => inputRef.current?.focus(), 50);
            return () => clearTimeout(timer);
        }
    }, [open]);

    // Fuzzy-filter and sort entries
    const matched: MatchedEntry[] = React.useMemo(() => {
        if (!filter.trim()) {
            return entries.map((entry) => ({entry, indices: [], searchText: getSearchText(entry)}));
        }

        const results: (MatchedEntry & {score: number})[] = [];
        for (const entry of entries) {
            const searchText = getSearchText(entry);
            const match = fuzzyMatch(searchText, filter);
            if (match) {
                results.push({entry, indices: match.indices, searchText, score: match.score});
            }
        }

        results.sort((a, b) => a.score - b.score);
        return results;
    }, [entries, filter]);

    const handleClose = () => {
        onClose();
        setFilter('');
    };

    const handleSelect = (entry: DebugEntry) => {
        onSelect(entry);
        handleClose();
    };

    return (
        <Popover
            open={open}
            anchorEl={anchorEl}
            onClose={handleClose}
            anchorOrigin={{vertical: 'bottom', horizontal: 'left'}}
            transformOrigin={{vertical: 'top', horizontal: 'left'}}
            slotProps={{paper: {sx: {width: 520, maxHeight: 440, mt: 0.5, borderRadius: 1.5}}}}
        >
            <FilterInput
                ref={inputRef}
                placeholder="Search by URL, method, or command..."
                value={filter}
                onChange={(e) => setFilter(e.target.value)}
            />
            <Box sx={{overflowY: 'auto', maxHeight: 360}}>
                {matched.length === 0 && (
                    <Box sx={{textAlign: 'center', py: 3, color: 'text.disabled'}}>
                        <Typography variant="body2">No entries match "{filter}"</Typography>
                    </Box>
                )}
                {matched.map(({entry, indices, searchText}) => {
                    const active = entry.id === currentEntryId;
                    if (isDebugEntryAboutWeb(entry)) {
                        // Split indices: method part vs path part
                        const methodLen = entry.request.method.length;
                        const methodIndices = indices.filter((i) => i < methodLen);
                        const pathIndices = indices.filter((i) => i > methodLen).map((i) => i - methodLen - 1);

                        return (
                            <EntryRow key={entry.id} active={active} onClick={() => handleSelect(entry)}>
                                <MethodLabel sx={{color: methodColor(entry.request.method, theme)}}>
                                    <HighlightedText text={entry.request.method} indices={methodIndices} />
                                </MethodLabel>
                                <PathLabel>
                                    <HighlightedText text={entry.request.path} indices={pathIndices} />
                                </PathLabel>
                                {entry.web?.request?.processingTime != null && (
                                    <Typography
                                        component="span"
                                        sx={{
                                            fontFamily: primitives.fontFamilyMono,
                                            fontSize: '10px',
                                            color: 'primary.main',
                                            flexShrink: 0,
                                        }}
                                    >
                                        {(entry.web.request.processingTime * 1000).toFixed(0)}ms
                                    </Typography>
                                )}
                                {entry.web?.memory?.peakUsage != null && (
                                    <Typography
                                        component="span"
                                        sx={{
                                            fontFamily: primitives.fontFamilyMono,
                                            fontSize: '10px',
                                            color: 'success.main',
                                            flexShrink: 0,
                                        }}
                                    >
                                        {formatBytes(entry.web.memory.peakUsage)}
                                    </Typography>
                                )}
                                <StatusLabel sx={{color: statusColor(entry.response.statusCode, theme)}}>
                                    {entry.response.statusCode}
                                </StatusLabel>
                                <TimeLabel>{formatDate(entry.web.request.startTime)}</TimeLabel>
                            </EntryRow>
                        );
                    }
                    if (isDebugEntryAboutConsole(entry)) {
                        const commandText = entry.command?.input ?? 'Unknown';
                        return (
                            <EntryRow key={entry.id} active={active} onClick={() => handleSelect(entry)}>
                                <Icon sx={{fontSize: 14, color: 'text.disabled'}}>terminal</Icon>
                                <PathLabel>
                                    <HighlightedText text={commandText} indices={indices} />
                                </PathLabel>
                                {entry.console?.request?.processingTime != null && (
                                    <Typography
                                        component="span"
                                        sx={{
                                            fontFamily: primitives.fontFamilyMono,
                                            fontSize: '10px',
                                            color: 'primary.main',
                                            flexShrink: 0,
                                        }}
                                    >
                                        {(entry.console.request.processingTime * 1000).toFixed(0)}ms
                                    </Typography>
                                )}
                                {entry.console?.memory?.peakUsage != null && (
                                    <Typography
                                        component="span"
                                        sx={{
                                            fontFamily: primitives.fontFamilyMono,
                                            fontSize: '10px',
                                            color: 'success.main',
                                            flexShrink: 0,
                                        }}
                                    >
                                        {formatBytes(entry.console.memory.peakUsage)}
                                    </Typography>
                                )}
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
                        <EntryRow key={entry.id} active={active} onClick={() => handleSelect(entry)}>
                            <PathLabel>
                                <HighlightedText text={entry.id} indices={indices} />
                            </PathLabel>
                        </EntryRow>
                    );
                })}
            </Box>
            {filter && (
                <CountLabel>
                    {matched.length} of {entries.length} entries
                </CountLabel>
            )}
        </Popover>
    );
};
