import {useLazyGetCollectorInfoQuery} from '@app-dev-panel/sdk/API/Debug/Debug';
import {ExceptionPreview} from '@app-dev-panel/sdk/Component/ExceptionPreview';
import {CollectorsMap} from '@app-dev-panel/sdk/Helper/collectors';
import CloseIcon from '@mui/icons-material/Close';
import {Box, CircularProgress, Dialog, DialogContent, DialogTitle, IconButton, Typography} from '@mui/material';
import {useEffect, useState} from 'react';

type ExceptionSummary = {class: string; message: string; line: string; file: string; code: string};

type ExceptionFullData = {
    class: string;
    message: string;
    line: string;
    file: string;
    code: string;
    trace: unknown[];
    traceAsString: string;
};

type ExceptionModalProps = {open: boolean; onClose: () => void; debugEntryId: string; summary: ExceptionSummary};

export const ExceptionModal = ({open, onClose, debugEntryId, summary}: ExceptionModalProps) => {
    const [fetchCollector, {isFetching}] = useLazyGetCollectorInfoQuery();
    const [exceptions, setExceptions] = useState<ExceptionFullData[] | null>(null);

    useEffect(() => {
        if (!open) {
            return;
        }
        let cancelled = false;
        (async () => {
            const result = await fetchCollector({id: debugEntryId, collector: CollectorsMap.ExceptionCollector});
            if (cancelled) {
                return;
            }
            const data = (result.data ?? []) as ExceptionFullData[];
            setExceptions(data);
        })();
        return () => {
            cancelled = true;
        };
    }, [open, debugEntryId, fetchCollector]);

    const items: ExceptionFullData[] =
        exceptions && exceptions.length > 0 ? exceptions : [{...summary, trace: [], traceAsString: ''}];

    return (
        <Dialog open={open} onClose={onClose} maxWidth="md" fullWidth scroll="paper">
            <DialogTitle sx={{display: 'flex', alignItems: 'center', gap: 1, pr: 6}}>
                <Typography
                    component="span"
                    sx={{fontFamily: 'JetBrains Mono, monospace', fontSize: 14, fontWeight: 600, color: 'error.main'}}
                >
                    {summary.class}
                </Typography>
                <IconButton
                    aria-label="close"
                    onClick={onClose}
                    size="small"
                    sx={{position: 'absolute', right: 8, top: 8}}
                >
                    <CloseIcon fontSize="small" />
                </IconButton>
            </DialogTitle>
            <DialogContent dividers>
                {isFetching && exceptions === null ? (
                    <Box sx={{display: 'flex', justifyContent: 'center', py: 4}}>
                        <CircularProgress size={24} />
                    </Box>
                ) : (
                    items.map((exception, index) => (
                        <ExceptionPreview
                            key={index}
                            class={exception.class}
                            message={exception.message}
                            line={String(exception.line)}
                            file={exception.file}
                            code={String(exception.code)}
                            trace={exception.trace ?? []}
                            traceAsString={exception.traceAsString ?? ''}
                        />
                    ))
                )}
            </DialogContent>
        </Dialog>
    );
};
