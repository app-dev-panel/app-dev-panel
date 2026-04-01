import {setIFrameHeight, setToolbarOpen} from '@app-dev-panel/sdk/API/Application/ApplicationContext';
import {addCurrentPageRequestId, changeEntryAction, useDebugEntry} from '@app-dev-panel/sdk/API/Debug/Context';
import {debugApi, DebugEntry, useGetDebugQuery} from '@app-dev-panel/sdk/API/Debug/Debug';
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
import BugReportOutlinedIcon from '@mui/icons-material/BugReportOutlined';
import DragHandleIcon from '@mui/icons-material/DragHandle';
import FormatListBulletedIcon from '@mui/icons-material/FormatListBulleted';
import OpenInNewIcon from '@mui/icons-material/OpenInNew';
import WebAssetIcon from '@mui/icons-material/WebAsset';
import WebAssetOffIcon from '@mui/icons-material/WebAssetOff';
import {Box, Divider, IconButton, Paper, Portal, Stack, Tooltip, useTheme} from '@mui/material';
import {styled} from '@mui/material/styles';
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

const ToolbarRoot = styled(Paper)(({theme}) => ({
    borderTop: `1px solid ${theme.palette.divider}`,
    borderRadius: 0,
    padding: theme.spacing(0.5, 1),
    display: 'flex',
    alignItems: 'center',
    gap: theme.spacing(0.5),
    minHeight: 40,
}));

const MetricsGroup = styled(Stack)(({theme}) => ({
    flexDirection: 'row',
    alignItems: 'center',
    gap: theme.spacing(0.25),
    flexWrap: 'nowrap',
}));

const ActionButton = styled(IconButton)(({theme}) => ({
    padding: theme.spacing(0.5),
    color: theme.palette.text.secondary,
    '&:hover': {color: theme.palette.text.primary},
}));

const ResizeHandle = styled('div')(({theme}) => ({
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
    '&:hover': {backgroundColor: theme.palette.action.hover},
    '& .MuiSvgIcon-root': {fontSize: 16, color: theme.palette.text.disabled},
}));

const CollapsedPill = styled(Paper)(({theme}) => ({
    position: 'fixed',
    bottom: theme.spacing(2),
    right: theme.spacing(2),
    borderRadius: 20,
    padding: theme.spacing(0.5),
    display: 'flex',
    alignItems: 'center',
    gap: theme.spacing(0.5),
    cursor: 'pointer',
    border: `1px solid ${theme.palette.divider}`,
    transition: 'box-shadow 200ms ease',
    zIndex: 1300,
    '&:hover': {boxShadow: theme.shadows[3]},
}));

type DebugToolbarProps = {activeComponents: {iframe: boolean}};
export const DebugToolbar = ({activeComponents}: DebugToolbarProps) => {
    const dispatch = useDispatch();

    useEffect(() => {
        const onMessageHandler = (event: MessageEvent) => {
            if (!event.data.payload || !('x-debug-id' in event.data.payload.headers)) {
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

    useEffect(() => setIsToolbarOpened(toolbarOpenState), [toolbarOpenState]);

    const onToolbarClickHandler = () => {
        setIsToolbarOpened((v) => {
            dispatch(setToolbarOpen(!v));
            if (iframeEnabled) {
                setIframeEnabled(false);
            }
            return !v;
        });
    };

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

    const [iframeEnabled, setIframeEnabled] = useState(false);
    const toggleIframeHandler = useCallback(() => {
        if (!activeComponents.iframe) {
            return;
        }
        setIframeEnabled((value) => !value);
    }, [activeComponents]);

    const theme = useTheme();
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

    if (getDebugQuery.isLoading) {
        return null;
    }

    // Collapsed state: small pill in bottom-right corner
    if (!isToolbarOpened) {
        return (
            <Portal>
                <CollapsedPill elevation={2} onClick={onToolbarClickHandler} aria-label="Open debug toolbar">
                    <BugReportOutlinedIcon sx={{fontSize: 20, color: 'primary.main', ml: 0.5}} />
                    {selectedEntry && (
                        <Box
                            component="span"
                            sx={{
                                fontSize: 11,
                                fontWeight: 600,
                                color: 'text.secondary',
                                pr: 0.5,
                                fontFamily: theme.typography.fontFamily,
                            }}
                        >
                            {isDebugEntryAboutWeb(selectedEntry)
                                ? `${selectedEntry.response.statusCode}`
                                : isDebugEntryAboutConsole(selectedEntry)
                                  ? `exit ${selectedEntry.command?.exitCode}`
                                  : ''}
                        </Box>
                    )}
                </CollapsedPill>
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
                    <ResizeHandle {...separatorProps}>
                        <DragHandleIcon />
                    </ResizeHandle>
                )}

                <ToolbarRoot elevation={0}>
                    {/* Logo / toggle button */}
                    <Tooltip title="Collapse toolbar" arrow>
                        <ActionButton onClick={onToolbarClickHandler} aria-label="Collapse toolbar" size="small">
                            <BugReportOutlinedIcon sx={{color: 'primary.main', fontSize: 20}} />
                        </ActionButton>
                    </Tooltip>

                    <Divider orientation="vertical" flexItem sx={{mx: 0.25}} />

                    {/* Metric items */}
                    {selectedEntry && (
                        <MetricsGroup>
                            {isDebugEntryAboutWeb(selectedEntry) && <RequestItem data={selectedEntry} />}
                            {isDebugEntryAboutConsole(selectedEntry) && <CommandItem data={selectedEntry} />}

                            <RequestTimeItem data={selectedEntry} iframeUrlHandler={iframeRouteNavigate} />
                            <MemoryItem data={selectedEntry} iframeUrlHandler={iframeRouteNavigate} />

                            {isDebugEntryAboutWeb(selectedEntry) && <RouterItem data={selectedEntry} />}

                            <LogsItem data={selectedEntry} iframeUrlHandler={iframeRouteNavigate} />
                            <EventsItem data={selectedEntry} iframeUrlHandler={iframeRouteNavigate} />
                            <ValidatorItem data={selectedEntry} iframeUrlHandler={iframeRouteNavigate} />

                            <DateItem data={selectedEntry} />
                        </MetricsGroup>
                    )}

                    {/* Spacer */}
                    <Box sx={{flex: 1}} />

                    {/* Action buttons */}
                    <Divider orientation="vertical" flexItem sx={{mx: 0.25}} />

                    <Stack direction="row" spacing={0.25} alignItems="center">
                        {activeComponents.iframe && (
                            <Tooltip title={iframeEnabled ? 'Close panel' : 'Open panel'} arrow>
                                <ActionButton
                                    onClick={toggleIframeHandler}
                                    aria-label="Toggle debug panel"
                                    size="small"
                                >
                                    {iframeEnabled ? (
                                        <WebAssetOffIcon sx={{fontSize: 18}} />
                                    ) : (
                                        <WebAssetIcon sx={{fontSize: 18}} />
                                    )}
                                </ActionButton>
                            </Tooltip>
                        )}
                        <Tooltip title="Debug entries" arrow>
                            <ActionButton onClick={handleClickOpen} aria-label="List debug entries" size="small">
                                <FormatListBulletedIcon sx={{fontSize: 18}} />
                            </ActionButton>
                        </Tooltip>
                        <Tooltip title="Open in new window" arrow>
                            <ActionButton onClick={handleDebugWindowOpen} aria-label="Open debug panel" size="small">
                                <OpenInNewIcon sx={{fontSize: 18}} />
                            </ActionButton>
                        </Tooltip>
                    </Stack>
                </ToolbarRoot>
            </Box>

            <DebugEntriesListModal open={open} onClick={onChangeHandler} onClose={handleClose} />
            <div ref={iframeContainerRef} style={{height: position, overflow: 'hidden'}} hidden={!iframeEnabled}>
                <DebugIFrame ref={iframeRef} baseUrlState={baseUrlState} iframeEnabled={iframeEnabled} />
            </div>
        </Portal>
    );
};
