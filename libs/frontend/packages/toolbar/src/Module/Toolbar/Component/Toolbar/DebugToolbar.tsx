import {
    setIFrameHeight,
    setToolbarFloatRect,
    setToolbarOpen,
    setToolbarPosition,
    type ToolbarPosition,
} from '@app-dev-panel/sdk/API/Application/ApplicationContext';
import {addCurrentPageRequestId, changeEntryAction, useDebugEntry} from '@app-dev-panel/sdk/API/Debug/Context';
import {debugApi, DebugEntry, useGetDebugQuery} from '@app-dev-panel/sdk/API/Debug/Debug';
import {DuckIcon} from '@app-dev-panel/sdk/Component/SvgIcon/DuckIcon';
import {isDebugEntryAboutConsole, isDebugEntryAboutWeb} from '@app-dev-panel/sdk/Helper/debugEntry';
import {DebugEntriesListModal} from '@app-dev-panel/toolbar/Module/Toolbar/Component/DebugEntriesListModal';
import {AiChatPopup} from '@app-dev-panel/toolbar/Module/Toolbar/Component/Toolbar/AiChatPopup';
import {CommandItem} from '@app-dev-panel/toolbar/Module/Toolbar/Component/Toolbar/Console/CommandItem';
import {DatabaseItem} from '@app-dev-panel/toolbar/Module/Toolbar/Component/Toolbar/DatabaseItem';
import {DeprecationItem} from '@app-dev-panel/toolbar/Module/Toolbar/Component/Toolbar/DeprecationItem';
import {EventsItem} from '@app-dev-panel/toolbar/Module/Toolbar/Component/Toolbar/EventsItem';
import {ExceptionItem} from '@app-dev-panel/toolbar/Module/Toolbar/Component/Toolbar/ExceptionItem';
import {HttpClientItem} from '@app-dev-panel/toolbar/Module/Toolbar/Component/Toolbar/HttpClientItem';
import {LogsItem} from '@app-dev-panel/toolbar/Module/Toolbar/Component/Toolbar/LogsItem';
import {MemoryItem} from '@app-dev-panel/toolbar/Module/Toolbar/Component/Toolbar/MemoryItem';
import {RequestTimeItem} from '@app-dev-panel/toolbar/Module/Toolbar/Component/Toolbar/RequestTimeItem';
import {ResizeGrip} from '@app-dev-panel/toolbar/Module/Toolbar/Component/Toolbar/ResizeGrip';
import {SnapZones} from '@app-dev-panel/toolbar/Module/Toolbar/Component/Toolbar/SnapZones';
import {useDrag} from '@app-dev-panel/toolbar/Module/Toolbar/Component/Toolbar/useDrag';
import {ValidatorItem} from '@app-dev-panel/toolbar/Module/Toolbar/Component/Toolbar/ValidatorItem';
import {RequestItem} from '@app-dev-panel/toolbar/Module/Toolbar/Component/Toolbar/Web/RequestItem';
import {useSelector} from '@app-dev-panel/toolbar/store';
import DragHandleIcon from '@mui/icons-material/DragHandle';
import FormatListBulletedIcon from '@mui/icons-material/FormatListBulleted';
import OpenInNewIcon from '@mui/icons-material/OpenInNew';
import SmartToyIcon from '@mui/icons-material/SmartToy';
import WebAssetIcon from '@mui/icons-material/WebAsset';
import WebAssetOffIcon from '@mui/icons-material/WebAssetOff';
import {Box, Chip, Divider, IconButton, Paper, Portal, Stack, Tooltip, useTheme} from '@mui/material';
import {ForwardedRef, forwardRef, useCallback, useEffect, useRef, useState} from 'react';
import {ErrorBoundary, type FallbackProps} from 'react-error-boundary';
import {useDispatch} from 'react-redux';

/**
 * Delta-based resize hook for the iframe panel.
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
    const [, setIsDragging] = useState(false);
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

    return {height, setHeight, separatorProps};
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

/** Metric items rendered in a row */
const MetricItems = ({entry, iframeRouteNavigate}: {entry: DebugEntry; iframeRouteNavigate: (url: string) => void}) => (
    <ErrorBoundary FallbackComponent={ToolbarErrorFallback} resetKeys={[entry.id]}>
        <Stack direction="row" alignItems="center" spacing={0.5} sx={{flexWrap: 'nowrap'}}>
            {isDebugEntryAboutWeb(entry) && <RequestItem data={entry} />}
            {isDebugEntryAboutConsole(entry) && <CommandItem data={entry} />}
            <ExceptionItem data={entry} iframeUrlHandler={iframeRouteNavigate} />
            <RequestTimeItem data={entry} iframeUrlHandler={iframeRouteNavigate} />
            <MemoryItem data={entry} iframeUrlHandler={iframeRouteNavigate} />
            <DatabaseItem data={entry} iframeUrlHandler={iframeRouteNavigate} />
            <HttpClientItem data={entry} iframeUrlHandler={iframeRouteNavigate} />
            <LogsItem data={entry} iframeUrlHandler={iframeRouteNavigate} />
            <EventsItem data={entry} iframeUrlHandler={iframeRouteNavigate} />
            <ValidatorItem data={entry} iframeUrlHandler={iframeRouteNavigate} />
            <DeprecationItem data={entry} iframeUrlHandler={iframeRouteNavigate} />
        </Stack>
    </ErrorBoundary>
);

/** Metric items rendered vertically for side rail */
const MetricItemsVertical = ({
    entry,
    iframeRouteNavigate,
}: {
    entry: DebugEntry;
    iframeRouteNavigate: (url: string) => void;
}) => (
    <ErrorBoundary FallbackComponent={ToolbarErrorFallback} resetKeys={[entry.id]}>
        <Stack direction="column" spacing={0} sx={{flex: 1, overflowY: 'auto', py: 0.5}}>
            {isDebugEntryAboutWeb(entry) && <RequestItem data={entry} />}
            {isDebugEntryAboutConsole(entry) && <CommandItem data={entry} />}
            <ExceptionItem data={entry} iframeUrlHandler={iframeRouteNavigate} />
            <RequestTimeItem data={entry} iframeUrlHandler={iframeRouteNavigate} />
            <MemoryItem data={entry} iframeUrlHandler={iframeRouteNavigate} />
            <DatabaseItem data={entry} iframeUrlHandler={iframeRouteNavigate} />
            <HttpClientItem data={entry} iframeUrlHandler={iframeRouteNavigate} />
            <LogsItem data={entry} iframeUrlHandler={iframeRouteNavigate} />
            <EventsItem data={entry} iframeUrlHandler={iframeRouteNavigate} />
            <ValidatorItem data={entry} iframeUrlHandler={iframeRouteNavigate} />
            <DeprecationItem data={entry} iframeUrlHandler={iframeRouteNavigate} />
        </Stack>
    </ErrorBoundary>
);

type DebugToolbarProps = {activeComponents: {iframe: boolean}};
export const DebugToolbar = ({activeComponents}: DebugToolbarProps) => {
    const dispatch = useDispatch();
    const theme = useTheme();

    // Service worker listener for debug IDs
    useEffect(() => {
        const onMessageHandler = (event: MessageEvent) => {
            if (!event.data.payload?.headers || !('x-debug-id' in event.data.payload.headers)) return;
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
    const toolbarPosition = useSelector((state) => state.application.toolbarPosition) ?? 'bottom';
    const savedFloatRect = useSelector((state) => state.application.toolbarFloatRect);

    const [iframeEnabled, setIframeEnabled] = useState(false);
    const [iframeSrc, setIframeSrc] = useState<string | null>(null);
    const [chatOpen, setChatOpen] = useState(false);
    const [position, setPosition] = useState<ToolbarPosition>(toolbarPosition);
    const [floatPos, setFloatPos] = useState(savedFloatRect ?? {x: 0, y: 0, width: 320, height: 360});

    const widgetRef = useRef<HTMLDivElement>(null);

    useEffect(() => setIsToolbarOpened(toolbarOpenState), [toolbarOpenState]);
    useEffect(() => setPosition(toolbarPosition), [toolbarPosition]);

    const onToolbarClickHandler = useCallback(() => {
        const next = !isToolbarOpened;
        setIsToolbarOpened(next);
        dispatch(setToolbarOpen(next));
        if (!next && iframeEnabled) setIframeEnabled(false);
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

    const iframeRouteNavigate = useCallback(
        (url: string) => {
            if (!activeComponents.iframe) return;
            setIframeSrc(url);
            if (!iframeEnabled) setIframeEnabled(true);
        },
        [iframeEnabled, activeComponents],
    );

    const toggleIframeHandler = useCallback(() => {
        if (!activeComponents.iframe) return;
        setIframeEnabled((v) => !v);
    }, [activeComponents]);

    const {
        height: panelHeight,
        setHeight: setPanelHeight,
        separatorProps,
    } = useBottomResize({initial: iframeHeight, min: 100, max: 1000, onResizeEnd: (h) => dispatch(setIFrameHeight(h))});
    useEffect(() => {
        if (iframeHeight != null) setPanelHeight(iframeHeight);
    }, [iframeHeight]);

    // === Snap / Drag logic ===
    const snapTo = useCallback(
        (newPos: ToolbarPosition) => {
            setPosition(newPos);
            dispatch(setToolbarPosition(newPos));
        },
        [dispatch],
    );

    const {isDragging, snapZone, dragHandleProps} = useDrag({
        onDragEnd: (zone) => {
            if (zone) {
                snapTo(zone);
            } else {
                snapTo('float');
                const rect = widgetRef.current?.getBoundingClientRect();
                if (rect) {
                    const newRect = {x: rect.left, y: rect.top, width: floatPos.width, height: floatPos.height};
                    setFloatPos(newRect);
                    dispatch(setToolbarFloatRect(newRect));
                }
            }
        },
        onPositionChange: (x, y) => {
            if (position !== 'float') {
                setPosition('float');
            }
            setFloatPos((prev) => ({...prev, x, y}));
        },
        getWidgetRect: () => widgetRef.current?.getBoundingClientRect() ?? null,
    });

    // Float resize
    const handleResize = useCallback((dx: number, dy: number) => {
        setFloatPos((prev) => ({
            x: prev.x + dx,
            y: prev.y + dy,
            width: Math.max(260, prev.width - dx),
            height: Math.max(200, prev.height - dy),
        }));
    }, []);

    const handleResizeEnd = useCallback(() => {
        dispatch(setToolbarFloatRect(floatPos));
    }, [floatPos, dispatch]);

    const actionButtonSx = {p: 0.5, color: 'text.secondary', '&:hover': {color: 'text.primary'}};

    // === Collapsed state ===
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

    const isBottom = position === 'bottom';
    const isSide = position === 'right' || position === 'left';

    // Shared action buttons
    const actionButtons = (
        <Stack direction="row" spacing={0.25} alignItems="center">
            {activeComponents.iframe && (
                <Tooltip title={iframeEnabled ? 'Close panel' : 'Open panel'} arrow>
                    <IconButton onClick={toggleIframeHandler} size="small" sx={actionButtonSx}>
                        {iframeEnabled ? <WebAssetOffIcon sx={{fontSize: 18}} /> : <WebAssetIcon sx={{fontSize: 18}} />}
                    </IconButton>
                </Tooltip>
            )}
            <Tooltip title="AI Chat" arrow>
                <IconButton
                    onClick={() => setChatOpen((v) => !v)}
                    size="small"
                    sx={{...actionButtonSx, ...(chatOpen && {color: 'primary.main'})}}
                >
                    <SmartToyIcon sx={{fontSize: 18}} />
                </IconButton>
            </Tooltip>
            <Tooltip title="Debug entries" arrow>
                <IconButton onClick={handleClickOpen} size="small" sx={actionButtonSx}>
                    <FormatListBulletedIcon sx={{fontSize: 18}} />
                </IconButton>
            </Tooltip>
            <Tooltip title="Open in new window" arrow>
                <IconButton onClick={handleDebugWindowOpen} size="small" sx={actionButtonSx}>
                    <OpenInNewIcon sx={{fontSize: 18}} />
                </IconButton>
            </Tooltip>
        </Stack>
    );

    // === BOTTOM BAR: single horizontal row ===
    if (isBottom) {
        return (
            <Portal>
                <Box sx={{position: 'sticky', bottom: 0, zIndex: 1300}}>
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
                        <Tooltip title="Collapse toolbar" arrow>
                            <IconButton onClick={onToolbarClickHandler} size="small" sx={actionButtonSx}>
                                <DuckIcon sx={{fontSize: 22}} />
                            </IconButton>
                        </Tooltip>
                        <Divider orientation="vertical" flexItem sx={{mx: 0.25}} />
                        {selectedEntry && (
                            <MetricItems entry={selectedEntry} iframeRouteNavigate={iframeRouteNavigate} />
                        )}
                        <Box
                            {...dragHandleProps}
                            sx={{flex: 1, minHeight: 32, cursor: 'grab', '&:active': {cursor: 'grabbing'}}}
                        />
                        <Divider orientation="vertical" flexItem sx={{mx: 0.25}} />
                        {actionButtons}
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
                <SnapZones activeZone={snapZone} />
                <AiChatPopup open={chatOpen} onClose={() => setChatOpen(false)} entry={selectedEntry ?? null} />
                <DebugEntriesListModal open={open} onClick={onChangeHandler} onClose={handleClose} />
            </Portal>
        );
    }

    // === SIDE RAIL: vertical panel ===
    if (isSide) {
        return (
            <Portal>
                <Paper
                    ref={widgetRef}
                    elevation={0}
                    sx={{
                        position: 'fixed',
                        top: 0,
                        [position]: 0,
                        bottom: 0,
                        width: 260,
                        zIndex: 1300,
                        borderRadius: 0,
                        display: 'flex',
                        flexDirection: 'column',
                        ...(position === 'right'
                            ? {borderLeft: 1, borderColor: 'divider'}
                            : {borderRight: 1, borderColor: 'divider'}),
                        boxShadow: theme.shadows[4],
                    }}
                >
                    <Box
                        {...dragHandleProps}
                        sx={{
                            display: 'flex',
                            alignItems: 'center',
                            px: 1.5,
                            py: 1,
                            gap: 1,
                            borderBottom: 1,
                            borderColor: 'divider',
                            cursor: 'grab',
                            '&:active': {cursor: 'grabbing'},
                        }}
                    >
                        <DuckIcon sx={{fontSize: 22}} />
                        <Box sx={{fontSize: 14, fontWeight: 600, flex: 1}}>Debug</Box>
                        <IconButton onClick={onToolbarClickHandler} size="small" sx={actionButtonSx}>
                            <Box component="span" sx={{fontSize: 14}}>
                                ✕
                            </Box>
                        </IconButton>
                    </Box>
                    {selectedEntry && (
                        <MetricItemsVertical entry={selectedEntry} iframeRouteNavigate={iframeRouteNavigate} />
                    )}
                    <Divider />
                    <Box sx={{p: 1, display: 'flex', gap: 0.5}}>{actionButtons}</Box>
                </Paper>
                <SnapZones activeZone={snapZone} />
                <AiChatPopup open={chatOpen} onClose={() => setChatOpen(false)} entry={selectedEntry ?? null} />
                <DebugEntriesListModal open={open} onClick={onChangeHandler} onClose={handleClose} />
            </Portal>
        );
    }

    // === FLOAT: draggable/resizable card ===
    return (
        <Portal>
            <Paper
                ref={widgetRef}
                elevation={4}
                sx={{
                    position: 'fixed',
                    left: floatPos.x,
                    top: floatPos.y,
                    width: floatPos.width,
                    height: floatPos.height,
                    zIndex: 1300,
                    borderRadius: '14px',
                    border: 1,
                    borderColor: 'divider',
                    display: 'flex',
                    flexDirection: 'column',
                    overflow: 'hidden',
                    transition: isDragging ? 'none' : 'box-shadow 200ms ease',
                }}
            >
                <ResizeGrip onResize={handleResize} onResizeEnd={handleResizeEnd} />
                <Box
                    {...dragHandleProps}
                    sx={{
                        display: 'flex',
                        alignItems: 'center',
                        px: 1,
                        py: 0.75,
                        gap: 0.75,
                        borderBottom: 1,
                        borderColor: 'divider',
                        cursor: 'grab',
                        '&:active': {cursor: 'grabbing'},
                        userSelect: 'none',
                        flexShrink: 0,
                    }}
                >
                    <DuckIcon sx={{fontSize: 20}} />
                    <Box sx={{fontSize: 12, fontWeight: 600, flex: 1}}>Debug</Box>
                    <IconButton onClick={() => setChatOpen((v) => !v)} size="small" sx={actionButtonSx}>
                        <SmartToyIcon sx={{fontSize: 16}} />
                    </IconButton>
                    <IconButton onClick={handleDebugWindowOpen} size="small" sx={actionButtonSx}>
                        <OpenInNewIcon sx={{fontSize: 16}} />
                    </IconButton>
                </Box>
                {selectedEntry && (
                    <Box sx={{flex: 1, overflowY: 'auto', px: 0.5, py: 0.5}}>
                        <MetricItems entry={selectedEntry} iframeRouteNavigate={iframeRouteNavigate} />
                    </Box>
                )}
                <Box sx={{display: 'flex', gap: 0.5, p: 0.75, borderTop: 1, borderColor: 'divider', flexShrink: 0}}>
                    {activeComponents.iframe && (
                        <Tooltip title="Toggle panel" arrow>
                            <IconButton onClick={toggleIframeHandler} size="small" sx={actionButtonSx}>
                                {iframeEnabled ? (
                                    <WebAssetOffIcon sx={{fontSize: 16}} />
                                ) : (
                                    <WebAssetIcon sx={{fontSize: 16}} />
                                )}
                            </IconButton>
                        </Tooltip>
                    )}
                    <Tooltip title="Debug entries" arrow>
                        <IconButton onClick={handleClickOpen} size="small" sx={actionButtonSx}>
                            <FormatListBulletedIcon sx={{fontSize: 16}} />
                        </IconButton>
                    </Tooltip>
                </Box>
            </Paper>
            <SnapZones activeZone={snapZone} />
            <AiChatPopup open={chatOpen} onClose={() => setChatOpen(false)} entry={selectedEntry ?? null} />
            <DebugEntriesListModal open={open} onClick={onChangeHandler} onClose={handleClose} />
        </Portal>
    );
};
