import {setIFrameHeight, setToolbarOpen} from '@app-dev-panel/sdk/API/Application/ApplicationContext';
import {addCurrentPageRequestId, changeEntryAction, useDebugEntry} from '@app-dev-panel/sdk/API/Debug/Context';
import {debugApi, DebugEntry, useGetDebugQuery} from '@app-dev-panel/sdk/API/Debug/Debug';
import {DuckIcon} from '@app-dev-panel/sdk/Component/SvgIcon/DuckIcon';
import {isDebugEntryAboutConsole, isDebugEntryAboutWeb} from '@app-dev-panel/sdk/Helper/debugEntry';
import {DebugEntriesListModal} from '@app-dev-panel/toolbar/Module/Toolbar/Component/DebugEntriesListModal';
import {CommandItem} from '@app-dev-panel/toolbar/Module/Toolbar/Component/Toolbar/Console/CommandItem';
import {DatabaseItem} from '@app-dev-panel/toolbar/Module/Toolbar/Component/Toolbar/DatabaseItem';
import {DeprecationItem} from '@app-dev-panel/toolbar/Module/Toolbar/Component/Toolbar/DeprecationItem';
import {EventsItem} from '@app-dev-panel/toolbar/Module/Toolbar/Component/Toolbar/EventsItem';
import {ExceptionItem} from '@app-dev-panel/toolbar/Module/Toolbar/Component/Toolbar/ExceptionItem';
import {HttpClientItem} from '@app-dev-panel/toolbar/Module/Toolbar/Component/Toolbar/HttpClientItem';
import {LogsItem} from '@app-dev-panel/toolbar/Module/Toolbar/Component/Toolbar/LogsItem';
import {MemoryItem} from '@app-dev-panel/toolbar/Module/Toolbar/Component/Toolbar/MemoryItem';
import {RequestTimeItem} from '@app-dev-panel/toolbar/Module/Toolbar/Component/Toolbar/RequestTimeItem';
import {ValidatorItem} from '@app-dev-panel/toolbar/Module/Toolbar/Component/Toolbar/ValidatorItem';
import {RequestItem} from '@app-dev-panel/toolbar/Module/Toolbar/Component/Toolbar/Web/RequestItem';
import {useSelector} from '@app-dev-panel/toolbar/store';
import DragHandleIcon from '@mui/icons-material/DragHandle';
import FormatListBulletedIcon from '@mui/icons-material/FormatListBulleted';
import OpenInNewIcon from '@mui/icons-material/OpenInNew';
import WebAssetIcon from '@mui/icons-material/WebAsset';
import WebAssetOffIcon from '@mui/icons-material/WebAssetOff';
import {Box, Chip, Divider, IconButton, Paper, Portal, Stack, Tooltip, useTheme} from '@mui/material';
import {ForwardedRef, forwardRef, useCallback, useEffect, useRef, useState} from 'react';
import {ErrorBoundary, type FallbackProps} from 'react-error-boundary';
import {useDispatch} from 'react-redux';

/**
 * Delta-based resize hook. Tracks mouse movement delta from drag start,
 * independent of container/body position. Fixes the "runaway handle" bug
 * that occurs with react-resizable-layout inside sticky-positioned containers.
 */
const useBottomResize = ({
    initial,
    min,
    max,
    onResizeEnd,
}: {
    initial: number;
    min: number;
    max: number;
    onResizeEnd: (height: number) => void;
}) => {
    const [height, setHeight] = useState(initial || 400);
    const [isDragging, setIsDragging] = useState(false);
    const dragRef = useRef<{startY: number; startHeight: number} | null>(null);
    const heightRef = useRef(height);
    heightRef.current = height;

    const separatorRef = useRef<HTMLElement | null>(null);

    const onPointerDown = useCallback((e: React.PointerEvent) => {
        e.preventDefault();
        e.stopPropagation();
        const target = e.currentTarget as HTMLElement;
        separatorRef.current = target;
        target.setPointerCapture(e.pointerId);
        dragRef.current = {startY: e.clientY, startHeight: heightRef.current};
        setIsDragging(true);
    }, []);

    const onPointerMove = useCallback(
        (e: React.PointerEvent) => {
            if (!dragRef.current) return;
            const delta = dragRef.current.startY - e.clientY;
            const next = Math.min(max, Math.max(min, dragRef.current.startHeight + delta));
            setHeight(next);
            heightRef.current = next;
        },
        [min, max],
    );

    const onPointerUp = useCallback(
        (e: React.PointerEvent) => {
            if (!dragRef.current) return;
            separatorRef.current?.releasePointerCapture(e.pointerId);
            setIsDragging(false);
            onResizeEnd(heightRef.current);
            dragRef.current = null;
        },
        [onResizeEnd],
    );

    const onLostPointerCapture = useCallback(() => {
        if (!dragRef.current) return;
        setIsDragging(false);
        onResizeEnd(heightRef.current);
        dragRef.current = null;
    }, [onResizeEnd]);

    const separatorProps = {
        role: 'separator' as const,
        'aria-valuenow': height,
        'aria-valuemin': min,
        'aria-valuemax': max,
        'aria-orientation': 'horizontal' as const,
        'aria-disabled': false,
        onPointerDown,
        onPointerMove,
        onPointerUp,
        onLostPointerCapture,
    };

    return {height, setHeight, isDragging, separatorProps};
};

const ToolbarErrorFallback = ({resetErrorBoundary}: FallbackProps) => (
    <Chip
        label="Toolbar error"
        size="small"
        color="error"
        variant="outlined"
        onClick={resetErrorBoundary}
        sx={{height: 32, borderRadius: 1, fontSize: 12, cursor: 'pointer'}}
    />
);

const serviceWorker = navigator?.serviceWorker;

type DebugIFrameProps = {baseUrlState: string; iframeEnabled: boolean; iframeSrc: string | null};

const DebugIFrame = forwardRef(
    ({baseUrlState, iframeEnabled, iframeSrc}: DebugIFrameProps, ref: ForwardedRef<HTMLIFrameElement>) => {
        const src = iframeSrc
            ? baseUrlState + iframeSrc + (iframeSrc.includes('?') ? '&' : '?') + 'toolbar=0'
            : baseUrlState + '/debug?toolbar=0';
        return (
            <iframe
                ref={ref}
                src={src}
                style={{height: '100%', width: '100%', border: 'none'}}
                hidden={!iframeEnabled}
                loading="lazy"
            />
        );
    },
);

type DebugToolbarProps = {activeComponents: {iframe: boolean}};
export const DebugToolbar = ({activeComponents}: DebugToolbarProps) => {
    const dispatch = useDispatch();
    const theme = useTheme();

    useEffect(() => {
        const onMessageHandler = (event: MessageEvent) => {
            if (!event.data.payload?.headers || !('x-debug-id' in event.data.payload.headers)) {
                return;
            }
            dispatch(debugApi.util.invalidateTags(['debug/list']));
            dispatch(addCurrentPageRequestId(event.data.payload.headers['x-debug-id']));
        };
        serviceWorker?.addEventListener('message', onMessageHandler);
        return () => {
            serviceWorker?.removeEventListener('message', onMessageHandler);
        };
    }, []);

    const [isToolbarOpened, setIsToolbarOpened] = useState<boolean>(false);
    const getDebugQuery = useGetDebugQuery();
    const debugEntry = useDebugEntry();

    const [selectedEntry, setSelectedEntry] = useState(debugEntry);

    useEffect(() => {
        if (!getDebugQuery.isFetching && getDebugQuery.data && getDebugQuery.data.length > 0) {
            setSelectedEntry(getDebugQuery.data[0]);
        }
    }, [getDebugQuery.isFetching]);

    const toolbarOpenState = useSelector((state) => state.application.toolbarOpen);
    const iframeHeight = useSelector((state) => state.application.iframeHeight);
    const baseUrlState = useSelector((state) => state.application.baseUrl);

    const [iframeEnabled, setIframeEnabled] = useState(false);

    useEffect(() => setIsToolbarOpened(toolbarOpenState), [toolbarOpenState]);

    const onToolbarClickHandler = useCallback(() => {
        const next = !isToolbarOpened;
        setIsToolbarOpened(next);
        dispatch(setToolbarOpen(next));
        if (!next && iframeEnabled) {
            setIframeEnabled(false);
        }
    }, [isToolbarOpened, iframeEnabled]);

    const onChangeHandler = useCallback((entry: DebugEntry) => {
        setSelectedEntry(entry);
        setIsToolbarOpened(true);
        dispatch(setToolbarOpen(true));
        dispatch(changeEntryAction(entry));
    }, []);

    const [open, setOpen] = useState(false);

    const handleDebugWindowOpen = useCallback(() => {
        window.open(debugEntry ? baseUrlState + '/debug?debugEntry=' + debugEntry.id : baseUrlState + '/debug');
    }, [debugEntry]);

    const handleClickOpen = useCallback(() => setOpen(true), []);
    const handleClose = useCallback(() => setOpen(false), []);

    const iframeRef = useRef<HTMLIFrameElement | undefined>(undefined);
    const [iframeSrc, setIframeSrc] = useState<string | null>(null);

    const iframeRouteNavigate = useCallback(
        (url: string) => {
            if (!activeComponents.iframe) return;

            // Navigate by changing the iframe src — works across any origin combination
            setIframeSrc(url);
            if (!iframeEnabled) {
                setIframeEnabled(true);
            }
        },
        [iframeEnabled, activeComponents],
    );

    const toggleIframeHandler = useCallback(() => {
        if (!activeComponents.iframe) {
            return;
        }
        setIframeEnabled((value) => !value);
    }, [activeComponents]);

    const {
        height: panelHeight,
        setHeight: setPanelHeight,
        separatorProps,
    } = useBottomResize({
        initial: iframeHeight,
        min: 100,
        max: 1000,
        onResizeEnd: (h) => {
            dispatch(setIFrameHeight(h));
        },
    });
    useEffect(() => {
        if (iframeHeight != null) {
            setPanelHeight(iframeHeight);
        }
    }, [iframeHeight]);

    const actionButtonSx = {p: 0.5, color: 'text.secondary', '&:hover': {color: 'text.primary'}};

    // Collapsed state: small pill in bottom-right corner
    if (!isToolbarOpened) {
        return (
            <Portal>
                <Paper
                    elevation={2}
                    role="button"
                    tabIndex={0}
                    onClick={onToolbarClickHandler}
                    aria-label="Open debug toolbar"
                    sx={{
                        position: 'fixed',
                        bottom: 16,
                        right: 16,
                        borderRadius: 24,
                        py: 0.75,
                        px: 1.5,
                        display: 'flex',
                        alignItems: 'center',
                        gap: 0.75,
                        cursor: 'pointer',
                        border: 1,
                        borderColor: 'divider',
                        transition: 'box-shadow 200ms ease, transform 200ms ease',
                        zIndex: 1300,
                        '&:hover': {boxShadow: theme.shadows[3], transform: 'scale(1.05)'},
                    }}
                >
                    <DuckIcon sx={{fontSize: 28}} />
                    {selectedEntry && (
                        <Box component="span" sx={{fontSize: 12, fontWeight: 600, color: 'text.secondary', pr: 0.25}}>
                            {isDebugEntryAboutWeb(selectedEntry)
                                ? `${selectedEntry.response.statusCode}`
                                : isDebugEntryAboutConsole(selectedEntry)
                                  ? `exit ${selectedEntry.command?.exitCode}`
                                  : ''}
                        </Box>
                    )}
                </Paper>
                <DebugEntriesListModal open={open} onClick={onChangeHandler} onClose={handleClose} />
            </Portal>
        );
    }

    // Expanded state: full toolbar bar at bottom
    return (
        <Portal>
            <Box sx={{position: 'sticky', bottom: 0, zIndex: 1300}}>
                {/* Resize handle (only when iframe is active) */}
                {iframeEnabled && (
                    <Box
                        {...separatorProps}
                        sx={{
                            position: 'absolute',
                            top: -6,
                            left: 0,
                            right: 0,
                            height: 12,
                            display: 'flex',
                            alignItems: 'center',
                            justifyContent: 'center',
                            cursor: 'row-resize',
                            zIndex: 1,
                            '&:hover': {bgcolor: 'action.hover'},
                        }}
                    >
                        <DragHandleIcon sx={{fontSize: 16, color: 'text.disabled'}} />
                    </Box>
                )}

                <Paper
                    elevation={0}
                    sx={{
                        borderTop: 1,
                        borderColor: 'divider',
                        borderRadius: 0,
                        px: 1,
                        py: 0.5,
                        display: 'flex',
                        alignItems: 'center',
                        gap: 0.5,
                        minHeight: 40,
                    }}
                >
                    {/* Logo / toggle button */}
                    <Tooltip title="Collapse toolbar" arrow>
                        <IconButton
                            onClick={onToolbarClickHandler}
                            aria-label="Collapse toolbar"
                            size="small"
                            sx={actionButtonSx}
                        >
                            <DuckIcon sx={{fontSize: 22}} />
                        </IconButton>
                    </Tooltip>

                    <Divider orientation="vertical" flexItem sx={{mx: 0.25}} />

                    {/* Metric items */}
                    {selectedEntry && (
                        <ErrorBoundary FallbackComponent={ToolbarErrorFallback} resetKeys={[selectedEntry.id]}>
                            <Stack direction="row" alignItems="center" spacing={0.5} sx={{flexWrap: 'nowrap'}}>
                                {isDebugEntryAboutWeb(selectedEntry) && <RequestItem data={selectedEntry} />}
                                {isDebugEntryAboutConsole(selectedEntry) && <CommandItem data={selectedEntry} />}

                                <ExceptionItem data={selectedEntry} iframeUrlHandler={iframeRouteNavigate} />

                                <RequestTimeItem data={selectedEntry} iframeUrlHandler={iframeRouteNavigate} />
                                <MemoryItem data={selectedEntry} iframeUrlHandler={iframeRouteNavigate} />

                                <DatabaseItem data={selectedEntry} iframeUrlHandler={iframeRouteNavigate} />
                                <HttpClientItem data={selectedEntry} iframeUrlHandler={iframeRouteNavigate} />

                                <LogsItem data={selectedEntry} iframeUrlHandler={iframeRouteNavigate} />
                                <EventsItem data={selectedEntry} iframeUrlHandler={iframeRouteNavigate} />
                                <ValidatorItem data={selectedEntry} iframeUrlHandler={iframeRouteNavigate} />
                                <DeprecationItem data={selectedEntry} iframeUrlHandler={iframeRouteNavigate} />
                            </Stack>
                        </ErrorBoundary>
                    )}

                    {/* Spacer */}
                    <Box sx={{flex: 1}} />

                    {/* Action buttons */}
                    <Divider orientation="vertical" flexItem sx={{mx: 0.25}} />

                    <Stack direction="row" spacing={0.25} alignItems="center">
                        {activeComponents.iframe && (
                            <Tooltip title={iframeEnabled ? 'Close panel' : 'Open panel'} arrow>
                                <IconButton
                                    onClick={toggleIframeHandler}
                                    aria-label="Toggle debug panel"
                                    size="small"
                                    sx={actionButtonSx}
                                >
                                    {iframeEnabled ? (
                                        <WebAssetOffIcon sx={{fontSize: 18}} />
                                    ) : (
                                        <WebAssetIcon sx={{fontSize: 18}} />
                                    )}
                                </IconButton>
                            </Tooltip>
                        )}
                        <Tooltip title="Debug entries" arrow>
                            <IconButton
                                onClick={handleClickOpen}
                                aria-label="List debug entries"
                                size="small"
                                sx={actionButtonSx}
                            >
                                <FormatListBulletedIcon sx={{fontSize: 18}} />
                            </IconButton>
                        </Tooltip>
                        <Tooltip title="Open in new window" arrow>
                            <IconButton
                                onClick={handleDebugWindowOpen}
                                aria-label="Open debug panel"
                                size="small"
                                sx={actionButtonSx}
                            >
                                <OpenInNewIcon sx={{fontSize: 18}} />
                            </IconButton>
                        </Tooltip>
                    </Stack>
                </Paper>

                {iframeEnabled && (
                    <div style={{height: panelHeight, overflow: 'hidden'}}>
                        <DebugIFrame
                            ref={iframeRef}
                            baseUrlState={baseUrlState}
                            iframeEnabled={iframeEnabled}
                            iframeSrc={iframeSrc}
                        />
                    </div>
                )}
            </Box>

            <DebugEntriesListModal open={open} onClick={onChangeHandler} onClose={handleClose} />
        </Portal>
    );
};
