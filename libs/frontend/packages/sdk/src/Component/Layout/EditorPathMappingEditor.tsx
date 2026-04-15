import {Box, Icon, IconButton, TextField, Typography} from '@mui/material';
import React, {useCallback, useEffect, useState} from 'react';

type PathMappingRow = {remote: string; local: string};

type Props = {mapping: Record<string, string>; onChange?: (mapping: Record<string, string>) => void};

function mappingToRows(mapping: Record<string, string>): PathMappingRow[] {
    return Object.entries(mapping).map(([remote, local]) => ({remote, local}));
}

function rowsToMapping(rows: PathMappingRow[]): Record<string, string> {
    const result: Record<string, string> = {};
    for (const {remote, local} of rows) {
        const trimmed = remote.trim();
        if (trimmed === '') continue;
        result[trimmed] = local;
    }
    return result;
}

function mappingsEqual(a: Record<string, string>, b: Record<string, string>): boolean {
    const aKeys = Object.keys(a);
    const bKeys = Object.keys(b);
    if (aKeys.length !== bKeys.length) return false;
    for (const key of aKeys) {
        if (a[key] !== b[key]) return false;
    }
    return true;
}

export const EditorPathMappingEditor = React.memo(({mapping, onChange}: Props) => {
    const [rows, setRows] = useState<PathMappingRow[]>(() => mappingToRows(mapping));

    // Sync local rows when the external mapping changes (e.g. cleared from another tab).
    // Intentionally omits `rows` from deps — local edits should not re-trigger this effect.
    useEffect(() => {
        setRows((prev) => (mappingsEqual(rowsToMapping(prev), mapping) ? prev : mappingToRows(mapping)));
    }, [mapping]);

    const commit = useCallback(
        (nextRows: PathMappingRow[]) => {
            if (!onChange) return;
            const next = rowsToMapping(nextRows);
            if (!mappingsEqual(next, mapping)) {
                onChange(next);
            }
        },
        [onChange, mapping],
    );

    const handleChange = useCallback((index: number, field: 'remote' | 'local', value: string) => {
        setRows((prev) => {
            const next = [...prev];
            next[index] = {...next[index], [field]: value};
            return next;
        });
    }, []);

    const handleBlur = useCallback(() => {
        commit(rows);
    }, [commit, rows]);

    const handleRemove = useCallback(
        (index: number) => {
            setRows((prev) => {
                const next = prev.filter((_, i) => i !== index);
                commit(next);
                return next;
            });
        },
        [commit],
    );

    const handleAdd = useCallback(() => {
        setRows((prev) => [...prev, {remote: '', local: ''}]);
    }, []);

    // Detect duplicate remote keys (for warning highlight). Empty rows are ignored.
    const remoteCounts = new Map<string, number>();
    for (const {remote} of rows) {
        const trimmed = remote.trim();
        if (trimmed === '') continue;
        remoteCounts.set(trimmed, (remoteCounts.get(trimmed) ?? 0) + 1);
    }

    return (
        <Box>
            <Typography variant="body2" sx={{mb: 0.5, color: 'text.secondary'}}>
                Path mapping (remote → local)
            </Typography>
            <Typography variant="caption" sx={{display: 'block', mb: 1, color: 'text.secondary'}}>
                Map container paths to local paths so Open in Editor works from Docker/WSL/remote.
            </Typography>
            {rows.map((row, index) => {
                const trimmed = row.remote.trim();
                const isDuplicate = trimmed !== '' && (remoteCounts.get(trimmed) ?? 0) > 1;
                return (
                    <Box key={index} sx={{display: 'flex', alignItems: 'center', gap: 1, mb: 1}}>
                        <TextField
                            size="small"
                            fullWidth
                            label="Remote"
                            placeholder="/app"
                            value={row.remote}
                            onChange={(e) => handleChange(index, 'remote', e.target.value)}
                            onBlur={handleBlur}
                            error={isDuplicate}
                            helperText={isDuplicate ? 'Duplicate — last wins' : undefined}
                            inputProps={{'aria-label': `Remote path ${index + 1}`}}
                        />
                        <Icon sx={{color: 'text.secondary', flexShrink: 0}}>arrow_forward</Icon>
                        <TextField
                            size="small"
                            fullWidth
                            label="Local"
                            placeholder="/Users/me/project"
                            value={row.local}
                            onChange={(e) => handleChange(index, 'local', e.target.value)}
                            onBlur={handleBlur}
                            inputProps={{'aria-label': `Local path ${index + 1}`}}
                        />
                        <IconButton
                            size="small"
                            onClick={() => handleRemove(index)}
                            aria-label={`Remove mapping ${index + 1}`}
                            sx={{flexShrink: 0}}
                        >
                            <Icon sx={{fontSize: 18}}>close</Icon>
                        </IconButton>
                    </Box>
                );
            })}
            <IconButton size="small" onClick={handleAdd} aria-label="Add mapping" sx={{mt: 0.5}}>
                <Icon sx={{fontSize: 18}}>add</Icon>
            </IconButton>
        </Box>
    );
});

EditorPathMappingEditor.displayName = 'EditorPathMappingEditor';
