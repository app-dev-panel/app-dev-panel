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
import {dispatchWindowEvent} from '@app-dev-panel/sdk/Helper/dispatchWindowEvent';
import {DebugEntriesListModal} from '@app-dev-panel/toolbar/Module/Toolbar/Component/DebugEntriesListModal';
import {AiChatPopup} from '@app-dev-panel/toolbar/Module/Toolbar/Component/Toolbar/AiChatPopup';
import {CommandItem} from '@app-dev-panel/toolbar/Module/Toolbar/Component/Toolbar/Console/CommandItem';
import {DatabaseItem} from '@app-dev-panel/toolbar/Module/Toolbar/Component/Toolbar/DatabaseItem';
import {DeprecationItem} from '@app-dev-panel/toolbar/Module/Toolbar/Component/Toolbar/DeprecationItem';
import {EventsItem} from '@app-dev-panel/toolbar/Module/Toolbar/Component/Toolbar/EventsItem';
import {ExceptionItem} from '@app-dev-panel/toolbar/Module/Toolbar/Component/Toolbar/ExceptionItem';
import {
    FloatMetrics,
    RequestHeroBar,
    SideMetrics,
} from '@app-dev-panel/toolbar/Module/Toolbar/Component/Toolbar/FloatMetrics';
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

/**
 * Resolve the path under `baseUrl` where the panel SPA is mounted.
 * Set by `ToolbarInjector` (PHP) from `PanelConfig::viewerBasePath`.
 * Defaults to `/debug` to match the default adapter setup. Trailing slashes
 * are stripped so `panelMount + '/...'` never produces a `//...` sequence
 * which `new URL()` would parse as a protocol-relative authority.
 */
const getPanelMountPath = (): string => {
    const raw = window.__adp_panel_url;
    if (!raw) return '/debug';
    const trimmed = raw.replace(/\/+$/, '');
    return trimmed.length > 0 ? trimmed : '';
};

/**
 * Build the initial iframe URL combining origin + panel mount + panel-internal
 * route + a forced `toolbar=0` flag. Uses the URL API so trailing/leading
 * slashes and existing query strings are handled safely.
 */
const buildIframeSrc = (baseUrl: string, panelMount: string, internalPath: string | null): string => {
    const path = panelMount + (internalPath ?? '');
    const url = new URL(path, baseUrl);
    url.searchParams.set('toolbar', '0');
    return url.toString();
};

const DebugIFrame = forwardRef(
    ({baseUrlState, iframeEnabled, iframeSrc}: DebugIFrameProps, ref: ForwardedRef<HTMLIFrameElement>) => {
        // Lock the src at mount time via `useState` with a lazy initializer:
        // computed once on first render and never recomputed on subsequent
        // renders, even when `iframeSrc` / `baseUrlState` props change.
        // Subsequent navigations are routed through postMessage
        // (router.navigate) so the iframe never fully reloads — changing the
        // src attribute would discard panel state, scroll, filters, and
        // re-fetch every API resource on every chip click.
        const [src] = useState(() => buildIframeSrc(baseUrlState, getPanelMountPath(), iframeSrc));
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
    const [iframeReady, setIframeReady] = useState(false);
    const [chatOpen, setChatOpen] = useState(false);
    const [position, setPosition] = useState<ToolbarPosition>(toolbarPosition);
    const [floatPos, setFloatPos] = useState(savedFloatRect ?? {x: 0, y: 0, width: 320, height: 360});

    const widgetRef = useRef<HTMLDivElement>(null);

    useEffect(() => setIsToolbarOpened(toolbarOpenState), [toolbarOpenState]);
    useEffect(() => setPosition(toolbarPosition), [toolbarPosition]);

    const iframeRef = useRef<HTMLIFrameElement>(null);

    // Queue for navigation requests that arrive before the panel has booted.
    // Set inside event handlers (pre-flush) and consumed by the ready-flush
    // effect; never read or written during render.
    const pendingNavUrlRef = useRef<string | null>(null);

    // Unmounting the iframe must always clear `iframeReady` so the next open
    // triggers a proper cold start (src-based load) instead of posting messages
    // to a stale or not-yet-booted contentWindow. Drop any pending navigation
    // too — it was targeted at the now-destroyed panel instance.
    const closeIframe = useCallback(() => {
        setIframeEnabled(false);
        setIframeReady(false);
        pendingNavUrlRef.current = null;
    }, []);

    const onToolbarClickHandler = useCallback(() => {
        const next = !isToolbarOpened;
        setIsToolbarOpened(next);
        dispatch(setToolbarOpen(next));
        if (!next && iframeEnabled) closeIframe();
    }, [isToolbarOpened, iframeEnabled, closeIframe, dispatch]);

    const onChangeHandler = useCallback(
        (entry: DebugEntry) => {
            setSelectedEntry(entry);
            setIsToolbarOpened(true);
            dispatch(setToolbarOpen(true));
            dispatch(changeEntryAction(entry));
        },
        [dispatch],
    );

    const [open, setOpen] = useState(false);
    const handleDebugWindowOpen = useCallback(() => {
        const url = new URL(getPanelMountPath(), baseUrlState);
        if (debugEntry) {
            url.searchParams.set('debugEntry', debugEntry.id);
        }
        window.open(url.toString());
    }, [debugEntry, baseUrlState]);

    const handleClickOpen = useCallback(() => setOpen(true), []);
    const handleClose = useCallback(() => setOpen(false), []);

    // Track when the embedded panel finishes booting so we can switch from
    // src-based loading (cold start) to postMessage-based navigation.
    // Validate the source — any frame on the page can postMessage, but only
    // our own iframe's contentWindow should flip the ready flag.
    useEffect(() => {
        const isPanelLoaded = (data: unknown): data is {event: 'panel.loaded'} =>
            typeof data === 'object' && data !== null && 'event' in data && data.event === 'panel.loaded';

        const listener = (event: MessageEvent) => {
            if (event.source !== iframeRef.current?.contentWindow) return;
            if (isPanelLoaded(event.data)) setIframeReady(true);
        };
        window.addEventListener('message', listener);
        return () => window.removeEventListener('message', listener);
    }, []);

    // Flush a single pending navigation on the `iframeReady` false→true edge.
    // Deps intentionally exclude `iframeSrc` — that would re-dispatch
    // `router.navigate` on every hot-path click (since the hot path already
    // calls `dispatchWindowEvent` synchronously and also `setIframeSrc`),
    // causing duplicate postMessages.
    useEffect(() => {
        if (!iframeReady) return;
        const pending = pendingNavUrlRef.current;
        if (!pending) return;
        const contentWindow = iframeRef.current?.contentWindow;
        if (!contentWindow) return;
        dispatchWindowEvent(contentWindow, 'router.navigate', pending);
        pendingNavUrlRef.current = null;
    }, [iframeReady]);

    const iframeRouteNavigate = useCallback(
        (url: string) => {
            if (!activeComponents.iframe) return;
            setIframeSrc(url);
            // Hot path: panel already mounted and booted — navigate via postMessage
            // to preserve panel state and avoid a full iframe reload.
            if (iframeEnabled && iframeReady && iframeRef.current?.contentWindow) {
                dispatchWindowEvent(iframeRef.current.contentWindow, 'router.navigate', url);
                return;
            }
            // Already mounted but still booting: queue so the ready-flush
            // effect can dispatch the latest requested URL via postMessage.
            if (iframeEnabled) {
                pendingNavUrlRef.current = url;
                return;
            }
            // Cold start: mount the iframe. `iframeSrc` (just set above)
            // becomes the initial src that `DebugIFrame` locks in — no need
            // to queue, the browser load carries the URL to the panel.
            setIframeEnabled(true);
        },
        [iframeEnabled, iframeReady, activeComponents],
    );

    const toggleIframeHandler = useCallback(() => {
        if (!activeComponents.iframe) return;
        if (iframeEnabled) {
            closeIframe();
        } else {
            setIframeEnabled(true);
        }
    }, [activeComponents, iframeEnabled, closeIframe]);

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
        floatSize: {width: floatPos.width, height: floatPos.height},
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
                        <>
                            <Box
                                component="span"
                                sx={{
                                    fontSize: 12,
                                    fontWeight: 600,
                                    color: 'text.secondary',
                                    fontFamily: "'JetBrains Mono', monospace",
                                }}
                            >
                                {isDebugEntryAboutWeb(selectedEntry)
                                    ? `${selectedEntry.response?.statusCode}`
                                    : isDebugEntryAboutConsole(selectedEntry)
                                      ? `exit ${selectedEntry.command?.exitCode}`
                                      : ''}
                            </Box>
                            {(selectedEntry.web || selectedEntry.console) && (
                                <Box
                                    component="span"
                                    sx={{
                                        fontSize: 12,
                                        color: 'text.disabled',
                                        fontFamily: "'JetBrains Mono', monospace",
                                    }}
                                >
                                    {Math.round(
                                        (selectedEntry.web || selectedEntry.console)!.request.processingTime * 1000,
                                    )}
                                    ms
                                </Box>
                            )}
                        </>
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
                        ref={widgetRef}
                        elevation={0}
                        {...dragHandleProps}
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
                            cursor: 'grab',
                            '&:active': {cursor: 'grabbing'},
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
                        <Box sx={{flex: 1}} />
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
                <AiChatPopup
                    open={chatOpen}
                    onClose={() => setChatOpen(false)}
                    entry={selectedEntry ?? null}
                    toolbarPosition={position}
                />
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
                    {selectedEntry && <RequestHeroBar entry={selectedEntry} />}
                    {selectedEntry && <SideMetrics entry={selectedEntry} />}
                    <Divider />
                    <Box sx={{p: 1, display: 'flex', gap: 0.5}}>{actionButtons}</Box>
                </Paper>
                <SnapZones activeZone={snapZone} />
                <AiChatPopup
                    open={chatOpen}
                    onClose={() => setChatOpen(false)}
                    entry={selectedEntry ?? null}
                    toolbarPosition={position}
                />
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
                {selectedEntry && <RequestHeroBar entry={selectedEntry} />}
                {selectedEntry && (
                    <Box sx={{flex: 1, overflowY: 'auto', px: 1, py: 0.75}}>
                        <FloatMetrics entry={selectedEntry} iframeUrlHandler={iframeRouteNavigate} />
                    </Box>
                )}
                <Box
                    sx={{
                        display: 'flex',
                        gap: 0.5,
                        px: 1,
                        py: 0.5,
                        borderTop: 1,
                        borderColor: 'divider',
                        flexShrink: 0,
                    }}
                >
                    {activeComponents.iframe && (
                        <Box
                            onClick={toggleIframeHandler}
                            sx={{
                                flex: 1,
                                py: 0.5,
                                border: 1,
                                borderColor: 'divider',
                                borderRadius: 1.5,
                                fontSize: 11,
                                color: 'text.secondary',
                                display: 'flex',
                                alignItems: 'center',
                                justifyContent: 'center',
                                gap: 0.5,
                                cursor: 'pointer',
                                '&:hover': {
                                    borderColor: 'primary.main',
                                    color: 'primary.main',
                                    bgcolor: 'primary.light',
                                },
                            }}
                        >
                            <WebAssetIcon sx={{fontSize: 14}} /> Panel
                        </Box>
                    )}
                    <Box
                        onClick={handleClickOpen}
                        sx={{
                            flex: 1,
                            py: 0.5,
                            border: 1,
                            borderColor: 'divider',
                            borderRadius: 1.5,
                            fontSize: 11,
                            color: 'text.secondary',
                            display: 'flex',
                            alignItems: 'center',
                            justifyContent: 'center',
                            gap: 0.5,
                            cursor: 'pointer',
                            '&:hover': {borderColor: 'primary.main', color: 'primary.main', bgcolor: 'primary.light'},
                        }}
                    >
                        <FormatListBulletedIcon sx={{fontSize: 14}} /> History
                    </Box>
                </Box>
            </Paper>
            <SnapZones activeZone={snapZone} />
            <AiChatPopup
                open={chatOpen}
                onClose={() => setChatOpen(false)}
                entry={selectedEntry ?? null}
                toolbarPosition={position}
            />
            <DebugEntriesListModal open={open} onClick={onChangeHandler} onClose={handleClose} />
        </Portal>
    );
};
