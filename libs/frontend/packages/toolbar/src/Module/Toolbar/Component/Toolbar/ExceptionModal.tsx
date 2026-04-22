import {useLazyGetCollectorInfoQuery} from '@app-dev-panel/sdk/API/Debug/Debug';
import {ExceptionPreview} from '@app-dev-panel/sdk/Component/ExceptionPreview';
import {CollectorsMap} from '@app-dev-panel/sdk/Helper/collectors';
import CloseIcon from '@mui/icons-material/Close';
import {Box, CircularProgress, Dialog, DialogContent, Divider, IconButton} from '@mui/material';
import React, {useEffect, useState} from 'react';

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
    const stopDrag = (event: React.MouseEvent | React.PointerEvent) => {
        event.stopPropagation();
    };

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
        <Dialog
            open={open}
            onClose={onClose}
            maxWidth="md"
            fullWidth
            scroll="paper"
            slotProps={{
                paper: {
                    onMouseDown: stopDrag,
                    onPointerDown: stopDrag,
                    onClick: (e: React.MouseEvent) => e.stopPropagation(),
                },
                backdrop: {onMouseDown: stopDrag, onPointerDown: stopDrag},
            }}
        >
            <Box sx={{display: 'flex', justifyContent: 'flex-end', px: 1, pt: 1}}>
                <IconButton aria-label="close" onClick={onClose} size="small">
                    <CloseIcon fontSize="small" />
                </IconButton>
            </Box>
            <DialogContent sx={{pt: 0}}>
                {isFetching && exceptions === null ? (
                    <Box sx={{display: 'flex', justifyContent: 'center', py: 4}}>
                        <CircularProgress size={24} />
                    </Box>
                ) : (
                    items.map((exception, index) => (
                        <Box key={index} sx={{mb: index < items.length - 1 ? 2 : 0}}>
                            {index > 0 && <Divider sx={{mb: 2}}>Caused by</Divider>}
                            <ExceptionPreview
                                class={exception.class}
                                message={exception.message}
                                line={String(exception.line)}
                                file={exception.file}
                                code={String(exception.code)}
                                trace={exception.trace ?? []}
                                traceAsString={exception.traceAsString ?? ''}
                                defaultEditorPreset="phpstorm"
                            />
                        </Box>
                    ))
                )}
            </DialogContent>
        </Dialog>
    );
};
