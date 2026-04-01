import {setIFrameHeight, setToolbarOpen} from '@app-dev-panel/sdk/API/Application/ApplicationContext';
import {addCurrentPageRequestId, changeEntryAction, useDebugEntry} from '@app-dev-panel/sdk/API/Debug/Context';
import {debugApi, DebugEntry, useGetDebugQuery} from '@app-dev-panel/sdk/API/Debug/Debug';
import {DuckIcon} from '@app-dev-panel/sdk/Component/SvgIcon/DuckIcon';
import {isDebugEntryAboutConsole, isDebugEntryAboutWeb} from '@app-dev-panel/sdk/Helper/debugEntry';
import {IFrameWrapper} from '@app-dev-panel/sdk/Helper/IFrameWrapper';
import {DebugEntriesListModal} from '@app-dev-panel/toolbar/Module/Toolbar/Component/DebugEntriesListModal';
import {CommandItem} from '@app-dev-panel/toolbar/Module/Toolbar/Component/Toolbar/Console/CommandItem';
import {DateItem} from '@app-dev-panel/toolbar/Module/Toolbar/Component/Toolbar/DateItem';
import {EventsItem} from '@app-dev-panel/toolbar/Module/Toolbar/Component/Toolbar/EventsItem';
import {LogsItem} from '@app-dev-panel/toolbar/Module/Toolbar/Component/Toolbar/LogsItem';
import {MemoryItem} from '@app-dev-panel/toolbar/Module/Toolbar/Component/Toolbar/MemoryItem';
import {RequestTimeItem} from '@app-dev-panel/toolbar/Module/Toolbar/Component/Toolbar/RequestTimeItem';
import {ValidatorItem} from '@app-dev-panel/toolbar/Module/Toolbar/Component/Toolbar/ValidatorItem';
import {RequestItem} from '@app-dev-panel/toolbar/Module/Toolbar/Component/Toolbar/Web/RequestItem';
import {RouterItem} from '@app-dev-panel/toolbar/Module/Toolbar/Component/Toolbar/Web/RouterItem';
import {useSelector} from '@app-dev-panel/toolbar/store';
import DragHandleIcon from '@mui/icons-material/DragHandle';
import FormatListBulletedIcon from '@mui/icons-material/FormatListBulleted';
import OpenInNewIcon from '@mui/icons-material/OpenInNew';
import WebAssetIcon from '@mui/icons-material/WebAsset';
import WebAssetOffIcon from '@mui/icons-material/WebAssetOff';
import {Box, Divider, IconButton, Paper, Portal, Stack, Tooltip, useTheme} from '@mui/material';
import {ForwardedRef, forwardRef, useCallback, useEffect, useRef, useState} from 'react';
import {useDispatch} from 'react-redux';
import {useResizable} from 'react-resizable-layout';

const serviceWorker = navigator?.serviceWorker;

type DebugIFrameProps = {baseUrlState: string; iframeEnabled: boolean};

const DebugIFrame = forwardRef(
    ({baseUrlState, iframeEnabled}: DebugIFrameProps, ref: ForwardedRef<HTMLIFrameElement>) => {
        return (
            <iframe
                ref={ref}
                src={baseUrlState + `/debug?toolbar=0`}
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
    const [iframeWrapper, setIframeWrapper] = useState<IFrameWrapper | null>(null);

    useEffect(() => {
        if (iframeRef.current) {
            setIframeWrapper(new IFrameWrapper(iframeRef.current));
        }
    }, [iframeRef.current]);

    const iframeRouteNavigate = useCallback(
        (url: string) => {
            if (!activeComponents.iframe) {
                return;
            }
            if (!iframeEnabled) {
                setIframeEnabled(true);
            }
            iframeWrapper?.dispatchEvent('router.navigate', url);
        },
        [iframeWrapper, activeComponents],
    );

    const toggleIframeHandler = useCallback(() => {
        if (!activeComponents.iframe) {
            return;
        }
        setIframeEnabled((value) => !value);
    }, [activeComponents]);

    const iframeContainerRef = useRef<HTMLDivElement | undefined>(undefined);
    const {position, separatorProps, setPosition} = useResizable({
        axis: 'y',
        initial: iframeHeight,
        min: 100,
        max: 1000,
        reverse: true,
        disabled: !isToolbarOpened,
        containerRef: iframeContainerRef,
        onResizeEnd: (e) => {
            dispatch(setIFrameHeight(e.position));
        },
    });
    useEffect(() => {
        setPosition(iframeHeight);
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
                        <Stack direction="row" alignItems="center" spacing={0.25} sx={{flexWrap: 'nowrap'}}>
                            {isDebugEntryAboutWeb(selectedEntry) && <RequestItem data={selectedEntry} />}
                            {isDebugEntryAboutConsole(selectedEntry) && <CommandItem data={selectedEntry} />}

                            <RequestTimeItem data={selectedEntry} iframeUrlHandler={iframeRouteNavigate} />
                            <MemoryItem data={selectedEntry} iframeUrlHandler={iframeRouteNavigate} />

                            {isDebugEntryAboutWeb(selectedEntry) && <RouterItem data={selectedEntry} />}

                            <LogsItem data={selectedEntry} iframeUrlHandler={iframeRouteNavigate} />
                            <EventsItem data={selectedEntry} iframeUrlHandler={iframeRouteNavigate} />
                            <ValidatorItem data={selectedEntry} iframeUrlHandler={iframeRouteNavigate} />

                            <DateItem data={selectedEntry} />
                        </Stack>
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
            </Box>

            <DebugEntriesListModal open={open} onClick={onChangeHandler} onClose={handleClose} />
            {iframeEnabled && (
                <div ref={iframeContainerRef} style={{height: position, overflow: 'hidden'}}>
                    <DebugIFrame ref={iframeRef} baseUrlState={baseUrlState} iframeEnabled={iframeEnabled} />
                </div>
            )}
        </Portal>
    );
};
