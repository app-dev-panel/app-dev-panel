import {LiveFeedPanel} from '@app-dev-panel/panel/Application/Component/LiveFeedPanel';
import {NotificationCenter} from '@app-dev-panel/panel/Application/Component/NotificationCenter';
import {AiChatPopup} from '@app-dev-panel/toolbar/Module/Toolbar/Component/Toolbar/AiChatPopup';

import {DuckIcon} from '@app-dev-panel/panel/Application/Component/DuckIcon';
import {useFramesEntries} from '@app-dev-panel/panel/Module/Frames/Context/Context';
import {
    useGetMcpSettingsQuery,
    useUpdateMcpSettingsMutation,
} from '@app-dev-panel/panel/Module/Inspector/API/Inspector';
import {useOpenApiEntries} from '@app-dev-panel/panel/Module/OpenApi/Context/Context';
import {useSelector} from '@app-dev-panel/panel/store';
import {
    changeAutoLatest,
    changeShowInactiveCollectors,
    changeThemeMode,
    setEditorConfig,
    toggleLiveFeed,
} from '@app-dev-panel/sdk/API/Application/ApplicationContext';
import {changeEntryAction, useDebugEntry} from '@app-dev-panel/sdk/API/Debug/Context';
import {DebugEntry, debugApi, useLazyGetDebugQuery} from '@app-dev-panel/sdk/API/Debug/Debug';
import {addLiveDump, addLiveLog, useLiveCount} from '@app-dev-panel/sdk/API/Debug/LiveContext';
import {setFloatingOpen} from '@app-dev-panel/sdk/API/Llm/AiChatSlice';
import {ErrorFallback} from '@app-dev-panel/sdk/Component/ErrorFallback';
import {CommandPalette} from '@app-dev-panel/sdk/Component/Layout/CommandPalette';
import {EntrySelector} from '@app-dev-panel/sdk/Component/Layout/EntrySelector';
import {TopBar} from '@app-dev-panel/sdk/Component/Layout/TopBar';
import {UnifiedSidebar} from '@app-dev-panel/sdk/Component/Layout/UnifiedSidebar';
import {selectUnreadCount} from '@app-dev-panel/sdk/Component/Notifications';
import {PageHeaderSlotProvider} from '@app-dev-panel/sdk/Component/PageHeader';
import {ScrollTopButton} from '@app-dev-panel/sdk/Component/ScrollTop';
import {componentTokens} from '@app-dev-panel/sdk/Component/Theme/tokens';
import {useCopyAsImage} from '@app-dev-panel/sdk/Component/useCopyAsImage';
import {EventTypesEnum, useServerSentEvents} from '@app-dev-panel/sdk/Component/useServerSentEvents';
import {compareCollectorWeight, getCollectorIcon, getCollectorLabel} from '@app-dev-panel/sdk/Helper/collectorMeta';
import {CollectorsMap} from '@app-dev-panel/sdk/Helper/collectors';
import {getCollectedCountByCollector} from '@app-dev-panel/sdk/Helper/collectorsTotal';
import {isDebugEntryAboutConsole, isDebugEntryAboutWeb} from '@app-dev-panel/sdk/Helper/debugEntry';
import {type EditorConfig, defaultEditorConfig} from '@app-dev-panel/sdk/Helper/editorUrl';
import {formatMillisecondsAsDuration} from '@app-dev-panel/sdk/Helper/formatDate';
import Box from '@mui/material/Box';
import CssBaseline from '@mui/material/CssBaseline';
import Drawer from '@mui/material/Drawer';
import Fab from '@mui/material/Fab';
import Tooltip from '@mui/material/Tooltip';
import {styled, useTheme as useMuiTheme} from '@mui/material/styles';
import useMediaQuery from '@mui/material/useMediaQuery';
import * as React from 'react';
import {useCallback, useEffect, useMemo, useReducer, useRef, useState} from 'react';
import {ErrorBoundary} from 'react-error-boundary';
import {useDispatch} from 'react-redux';
import {Outlet, useLocation, useNavigate, useSearchParams} from 'react-router';

// ---------------------------------------------------------------------------
// UI state reducer
// ---------------------------------------------------------------------------

type UIState = {
    mobileMenuOpen: boolean;
    notificationAnchor: HTMLElement | null;
    entrySelectorAnchor: HTMLElement | null;
    paletteOpen: boolean;
};

type UIAction =
    | {type: 'OPEN_MOBILE_MENU'}
    | {type: 'CLOSE_MOBILE_MENU'}
    | {type: 'TOGGLE_NOTIFICATIONS'; anchor: HTMLElement | null}
    | {type: 'CLOSE_NOTIFICATIONS'}
    | {type: 'TOGGLE_ENTRY_SELECTOR'; anchor: HTMLElement | null}
    | {type: 'CLOSE_ENTRY_SELECTOR'}
    | {type: 'TOGGLE_PALETTE'}
    | {type: 'OPEN_PALETTE'}
    | {type: 'CLOSE_PALETTE'};

const initialUIState: UIState = {
    mobileMenuOpen: false,
    notificationAnchor: null,
    entrySelectorAnchor: null,
    paletteOpen: false,
};

function uiReducer(state: UIState, action: UIAction): UIState {
    switch (action.type) {
        case 'OPEN_MOBILE_MENU':
            return {...state, mobileMenuOpen: true};
        case 'CLOSE_MOBILE_MENU':
            return {...state, mobileMenuOpen: false};
        case 'TOGGLE_NOTIFICATIONS':
            return {...state, notificationAnchor: state.notificationAnchor ? null : action.anchor};
        case 'CLOSE_NOTIFICATIONS':
            return {...state, notificationAnchor: null};
        case 'TOGGLE_ENTRY_SELECTOR':
            return {...state, entrySelectorAnchor: state.entrySelectorAnchor ? null : action.anchor};
        case 'CLOSE_ENTRY_SELECTOR':
            return {...state, entrySelectorAnchor: null};
        case 'TOGGLE_PALETTE':
            return {...state, paletteOpen: !state.paletteOpen};
        case 'OPEN_PALETTE':
            return {...state, paletteOpen: true};
        case 'CLOSE_PALETTE':
            return {...state, paletteOpen: false};
    }
}

// ---------------------------------------------------------------------------
// Collectors hidden from sidebar (shown in overview instead)
// ---------------------------------------------------------------------------
const hiddenCollectors = new Set<string>([
    CollectorsMap.WebAppInfoCollector,
    CollectorsMap.ConsoleAppInfoCollector,
    CollectorsMap.HttpStreamCollector,
    CollectorsMap.DeprecationCollector,
    CollectorsMap.VarDumperCollector,
    CollectorsMap.HttpClientCollector,
    CollectorsMap.RequestCollector,
    CollectorsMap.CommandCollector,
]);

// ---------------------------------------------------------------------------
// Derive a short label for an Open API / Frames entry URL
// ---------------------------------------------------------------------------
const labelFromUrl = (url: string): string => {
    try {
        const parsed = new URL(url);
        const segments = parsed.pathname.split('/').filter(Boolean);
        return segments[segments.length - 1] || parsed.host;
    } catch {
        return url;
    }
};

// ---------------------------------------------------------------------------
// Static inspector sub-items
// ---------------------------------------------------------------------------
const inspectorChildren = [
    {key: '/inspector/config', icon: 'settings', label: 'Configuration'},
    {key: '/inspector/events', icon: 'bolt', label: 'Event Listeners'},
    {key: '/inspector/routes', icon: 'alt_route', label: 'Routes'},
    {key: '/inspector/code-quality', icon: 'verified', label: 'Code Quality'},
    {key: '/inspector/files', icon: 'folder_open', label: 'File Explorer'},
    {key: '/inspector/translations', icon: 'translate', label: 'Translations'},
    {key: '/inspector/commands', icon: 'terminal', label: 'Commands'},
    {key: '/inspector/storage', icon: 'storage', label: 'Storage'},
    {key: '/inspector/authorization', icon: 'shield', label: 'Authorization'},
    {key: '/inspector/environment', icon: 'settings_suggest', label: 'Environment'},
    {key: '/inspector/http-mock', icon: 'cloud_queue', label: 'HTTP Mock'},
];

// ---------------------------------------------------------------------------
// Styled components
// ---------------------------------------------------------------------------

const MainArea = styled(Box)(({theme}) => ({
    flex: 1,
    display: 'flex',
    justifyContent: 'center',
    padding: theme.spacing(1),
    gap: theme.spacing(1),
    overflow: 'auto',
    [theme.breakpoints.up('sm')]: {padding: componentTokens.mainGap, gap: componentTokens.mainGap},
}));

const MainInner = styled(Box, {shouldForwardProp: (p) => p !== 'expanded'})<{expanded?: boolean}>(({expanded}) => ({
    display: 'flex',
    width: '100%',
    maxWidth: expanded ? 'none' : componentTokens.mainMaxWidth,
    gap: componentTokens.mainGap,
}));

const ContentStack = styled(Box)({
    position: 'relative',
    flex: 1,
    minWidth: 0,
    display: 'flex',
    flexDirection: 'column',
});

// Positioned over ContentArea's top border. Pages that opt into the chip
// PageHeader variant portal their title into this slot.
const ContentHeaderSlot = styled('div')(({theme}) => ({
    position: 'absolute',
    top: 0,
    left: theme.spacing(3),
    transform: 'translateY(-50%)',
    zIndex: 2,
    pointerEvents: 'none',
    [theme.breakpoints.up('sm')]: {left: theme.spacing(4)},
}));

const ContentArea = styled(Box)(({theme}) => ({
    flex: 1,
    minWidth: 0,
    borderRadius: componentTokens.contentPanel.borderRadius,
    backgroundColor: theme.palette.background.paper,
    border: `1px solid ${theme.palette.divider}`,
    padding: theme.spacing(2, 1.5),
    overflowY: 'auto',
    [theme.breakpoints.up('sm')]: {padding: theme.spacing(3.5, 4.5)},
}));

// ---------------------------------------------------------------------------
// Layout component
// ---------------------------------------------------------------------------

export const Layout = React.memo(({children}: React.PropsWithChildren) => {
    const location = useLocation();
    const navigate = useNavigate();
    const dispatch = useDispatch();
    const [searchParams] = useSearchParams();
    const muiTheme = useMuiTheme();
    const isMobile = useMediaQuery(muiTheme.breakpoints.down('md'));
    const [ui, dispatchUI] = useReducer(uiReducer, initialUIState);
    const handleMenuClick = useCallback(() => dispatchUI({type: 'OPEN_MOBILE_MENU'}), []);
    const handleMenuClose = useCallback(() => dispatchUI({type: 'CLOSE_MOBILE_MENU'}), []);

    // Debug entry state
    const debugEntry = useDebugEntry();
    const [getDebugQuery, getDebugQueryInfo] = useLazyGetDebugQuery();
    const backendUrl = useSelector((state) => state.application.baseUrl);
    const autoLatest = useSelector((state) => state.application.autoLatest);
    const themeMode = useSelector((state) => state.application.themeMode);
    const currentMode = themeMode || 'system';
    const showInactiveCollectors = useSelector((state) => state.application.showInactiveCollectors);
    const editorConfig = useSelector((state) => state.application.editorConfig) ?? defaultEditorConfig;
    const notificationCount = useSelector(selectUnreadCount);
    const liveFeedCount = useLiveCount();
    const liveFeedOpen = useSelector((state) => state.application.liveFeedOpen ?? false);
    const aiChatOpen = useSelector((state) => state.aiChat?.floatingOpen ?? false);
    const handleAiChatToggle = useCallback(() => dispatch(setFloatingOpen(!aiChatOpen)), [dispatch, aiChatOpen]);
    const handleAiChatClose = useCallback(() => dispatch(setFloatingOpen(false)), [dispatch]);

    // Copy as image
    const {copyToClipboard: copyAsImage, downloadAsPng, isCapturing, targetRef: contentRef} = useCopyAsImage();

    // Slot for pages that portal a compact chip header onto ContentArea's border.
    const [headerSlotEl, setHeaderSlotEl] = useState<HTMLElement | null>(null);

    // MCP settings
    const {data: mcpSettings} = useGetMcpSettingsQuery();
    const [updateMcpSettings] = useUpdateMcpSettingsMutation();

    // Notification center popover
    const handleNotificationsClick = useCallback((e: React.MouseEvent<HTMLElement>) => {
        dispatchUI({type: 'TOGGLE_NOTIFICATIONS', anchor: e.currentTarget});
    }, []);
    const handleNotificationsClose = useCallback(() => dispatchUI({type: 'CLOSE_NOTIFICATIONS'}), []);

    // Fetch debug entries on mount and when backend URL changes
    useEffect(() => {
        dispatch(changeEntryAction(null));
        dispatch(debugApi.util.resetApiState());
        getDebugQuery();
    }, [getDebugQuery, backendUrl, dispatch]);

    // Entry selection rules:
    // - Honor `?debugEntry=<id>` from the URL on first load and when the URL
    //   genuinely changes (the embedded toolbar's iframe navigates via
    //   postMessage which rewrites the query string — we want to follow it).
    // - Don't re-apply the URL when only the entries list refreshes (SSE),
    //   otherwise the user's manual prev/next-arrow navigation (which updates
    //   Redux but not the URL) would be overwritten every second.
    // - If the URL pins an entry that hasn't arrived in the list yet, wait for
    //   SSE to bring it in; don't flash the latest entry in the meantime.
    // - If the URL has no `debugEntry` and nothing is selected yet, fall back
    //   to the latest entry.
    const lastSyncedUrlEntryIdRef = useRef<string | null>(null);
    useEffect(() => {
        if (!getDebugQueryInfo.isSuccess || !getDebugQueryInfo.data?.length) return;

        const requestedId = searchParams.get('debugEntry');
        if (requestedId) {
            if (requestedId === lastSyncedUrlEntryIdRef.current) return;
            const requested = getDebugQueryInfo.data.find((e) => e.id === requestedId);
            if (!requested) return; // pinned but not loaded yet — wait for SSE
            lastSyncedUrlEntryIdRef.current = requestedId;
            if (debugEntry?.id !== requestedId) {
                dispatch(changeEntryAction(requested));
            }
            return;
        }

        if (!debugEntry) {
            dispatch(changeEntryAction(getDebugQueryInfo.data[0]));
        }
    }, [getDebugQueryInfo.isSuccess, getDebugQueryInfo.data, dispatch, debugEntry, searchParams]);

    // SSE for auto-refresh
    const changeEntry = useCallback(
        (entry: DebugEntry | null) => {
            dispatch(changeEntryAction(entry));
        },
        [dispatch],
    );

    const onUpdatesHandler = useCallback(
        async (event: MessageEvent) => {
            let data;
            try {
                data = JSON.parse(event.data);
            } catch {
                return;
            }
            if (!data.type) return;

            if (data.type === EventTypesEnum.DebugUpdated || data.type === EventTypesEnum.EntryCreated) {
                const result = await getDebugQuery();
                if ('data' in result && result.data.length > 0) {
                    changeEntry(result.data[0]);
                }
            } else if (data.type === EventTypesEnum.LiveLog) {
                try {
                    const payload = typeof data.payload === 'string' ? JSON.parse(data.payload) : data.payload;
                    if (payload && typeof payload === 'object') {
                        dispatch(
                            addLiveLog({
                                level: String(payload.level ?? 'debug'),
                                message: String(payload.message ?? ''),
                                context: payload.context,
                            }),
                        );
                    }
                } catch {
                    /* ignore malformed payloads */
                }
            } else if (data.type === EventTypesEnum.LiveDump) {
                try {
                    const payload = typeof data.payload === 'string' ? JSON.parse(data.payload) : data.payload;
                    if (payload && typeof payload === 'object') {
                        dispatch(addLiveDump({variable: payload, line: payload.$__line__$ ?? undefined}));
                    }
                } catch {
                    /* ignore malformed payloads */
                }
            }
        },
        [getDebugQuery, changeEntry, dispatch],
    );
    // Always subscribe so the Live Feed receives logs/dumps independently of autoLatest.
    // autoLatest only controls whether the entry list jumps to newest entry on entry-created.
    useServerSentEvents(backendUrl, onUpdatesHandler);

    // Entry navigation
    const entries = getDebugQueryInfo.data ?? [];
    const currentIndex = debugEntry ? entries.findIndex((e) => e.id === debugEntry.id) : -1;

    const handlePrevEntry = useCallback(() => {
        if (currentIndex > 0) changeEntry(entries[currentIndex - 1]);
    }, [currentIndex, entries, changeEntry]);

    const handleNextEntry = useCallback(() => {
        if (currentIndex < entries.length - 1) changeEntry(entries[currentIndex + 1]);
    }, [currentIndex, entries, changeEntry]);

    const handleRefresh = useCallback(() => {
        getDebugQuery();
    }, [getDebugQuery]);

    // Entry selector popover
    const handleEntryClick = useCallback((e?: React.MouseEvent) => {
        const target = (e?.currentTarget as HTMLElement) ?? null;
        dispatchUI({type: 'TOGGLE_ENTRY_SELECTOR', anchor: target});
    }, []);

    // Theme toggle
    const handleThemeToggle = useCallback(() => {
        const next = currentMode === 'dark' ? 'light' : 'dark';
        dispatch(changeThemeMode(next as 'light' | 'dark'));
    }, [dispatch, currentMode]);

    // Command palette
    const handleLiveFeedClick = useCallback(() => dispatch(toggleLiveFeed()), [dispatch]);
    const handleSearchClick = useCallback(() => dispatchUI({type: 'OPEN_PALETTE'}), []);
    const handleLogoClick = useCallback(() => navigate('/'), [navigate]);
    const handlePaletteClose = useCallback(() => dispatchUI({type: 'CLOSE_PALETTE'}), []);
    const handleEntrySelectorClose = useCallback(() => dispatchUI({type: 'CLOSE_ENTRY_SELECTOR'}), []);
    const handleAllEntriesClick = useCallback(() => navigate('/debug/list'), [navigate]);

    useEffect(() => {
        const handler = (e: KeyboardEvent) => {
            if ((e.metaKey || e.ctrlKey) && e.key === 'k') {
                e.preventDefault();
                dispatchUI({type: 'TOGGLE_PALETTE'});
            }
        };
        document.addEventListener('keydown', handler);
        return () => document.removeEventListener('keydown', handler);
    }, []);

    const autoLatestHandler = useCallback(() => {
        dispatch(changeAutoLatest(!autoLatest));
    }, [dispatch, autoLatest]);

    const handleShowInactiveCollectorsChange = useCallback(
        (value: boolean) => {
            dispatch(changeShowInactiveCollectors(value));
        },
        [dispatch],
    );

    const handleEditorConfigChange = useCallback(
        (config: EditorConfig) => {
            dispatch(setEditorConfig(config));
        },
        [dispatch],
    );

    const handleMcpEnabledChange = useCallback(
        (value: boolean) => {
            updateMcpSettings({enabled: value});
        },
        [updateMcpSettings],
    );

    const handleOpenWebsite = useCallback(() => {
        if (!backendUrl) {
            return;
        }
        window.open(backendUrl, '_blank', 'noopener,noreferrer');
    }, [backendUrl]);

    // TopBar debug info — support both web requests and console commands
    const isWeb = debugEntry ? isDebugEntryAboutWeb(debugEntry) : false;
    const isConsole = debugEntry ? isDebugEntryAboutConsole(debugEntry) : false;

    const topBarMethod = isWeb ? debugEntry!.request?.method : isConsole ? 'CLI' : undefined;
    const topBarPath = isWeb
        ? debugEntry!.request?.path
        : isConsole
          ? (debugEntry!.command?.input ?? debugEntry!.command?.name ?? 'Unknown command')
          : undefined;
    const topBarStatus = isWeb
        ? debugEntry!.response?.statusCode
        : isConsole
          ? (debugEntry!.command?.exitCode ?? 0)
          : undefined;
    const topBarDuration = isWeb
        ? debugEntry!.web?.request?.processingTime
            ? formatMillisecondsAsDuration(debugEntry!.web.request.processingTime)
            : undefined
        : isConsole
          ? debugEntry!.console?.request?.processingTime
              ? formatMillisecondsAsDuration(debugEntry!.console.request.processingTime)
              : undefined
          : undefined;

    // Build sidebar sections
    const selectedCollector = searchParams.get('collector') || '';
    const openApiEntries = useOpenApiEntries();
    const framesEntries = useFramesEntries();

    const openApiChildren = useMemo(
        () => Object.keys(openApiEntries).map((name) => ({key: name, icon: 'description', label: labelFromUrl(name)})),
        [openApiEntries],
    );

    const framesChildren = useMemo(
        () => Object.keys(framesEntries).map((name) => ({key: name, icon: 'web_asset', label: labelFromUrl(name)})),
        [framesEntries],
    );

    const debugChildren = useMemo(() => {
        const entriesList = [{key: '__entries__', icon: 'list', label: 'All Entries'}];
        if (!debugEntry) return entriesList;
        const entryIsWeb = isDebugEntryAboutWeb(debugEntry);
        const hasRequestOrCommand = debugEntry.collectors.some((c) => {
            const id = typeof c === 'string' ? c : c.id;
            return id === CollectorsMap.RequestCollector || id === CollectorsMap.CommandCollector;
        });
        const entryItem = hasRequestOrCommand
            ? [{key: CollectorsMap.EntryCollector, icon: entryIsWeb ? 'http' : 'terminal', label: 'Request'}]
            : [];
        const collectors = [...debugEntry.collectors]
            .map((c) => (typeof c === 'string' ? c : c.id))
            .filter((c) => !hiddenCollectors.has(c))
            .sort(compareCollectorWeight)
            .map((collector) => {
                const count = getCollectedCountByCollector(collector as CollectorsMap, debugEntry);
                const isException = collector === CollectorsMap.ExceptionCollector && !!count && count > 0;
                return {
                    key: collector,
                    icon: getCollectorIcon(collector),
                    label: getCollectorLabel(collector),
                    badge: count,
                    badgeVariant: (isException ? 'error' : 'default') as 'error' | 'default',
                };
            })
            .filter((c) => showInactiveCollectors || c.badge == null || c.badge > 0);
        return [...entryItem, ...collectors, ...entriesList];
    }, [debugEntry, showInactiveCollectors]);

    // Build extra items for CommandPalette from current debug entry's collectors
    const paletteCollectorItems = useMemo(() => {
        if (!debugEntry) return [];
        return debugChildren
            .filter((c) => c.key !== '__entries__' && c.key !== '__overview__')
            .map((c) => ({
                icon: c.icon,
                label: `Debug > ${c.label}`,
                path: `/debug?collector=${encodeURIComponent(c.key)}&debugEntry=${debugEntry.id}`,
                section: 'Collectors',
            }));
    }, [debugChildren, debugEntry]);

    const sidebarSections = useMemo(
        () => [
            {key: 'home', icon: 'home', label: 'Home', href: '/'},
            {key: 'debug', icon: 'bug_report', label: 'Debug', href: '/debug', children: debugChildren},
            {key: 'inspector', icon: 'search', label: 'Inspector', href: '/inspector', children: inspectorChildren},
            {
                key: 'llm',
                icon: 'psychology',
                label: 'LLM',
                href: '/llm',
                children: [{key: '/llm/mcp', icon: 'hub', label: 'MCP'}],
            },
            {key: 'open-api', icon: 'data_object', label: 'Open API', href: '/open-api', children: openApiChildren},
            {key: 'frames', icon: 'web', label: 'Frames', href: '/frames', children: framesChildren},
        ],
        [debugChildren, openApiChildren, framesChildren],
    );

    // Determine active child key
    const activeChildKey = useMemo(() => {
        if (location.pathname === '/debug/list') {
            return '__entries__';
        }
        if (location.pathname.startsWith('/debug')) {
            // Map raw Request/Command collectors to the virtual Entry collector for sidebar highlight
            if (
                selectedCollector === CollectorsMap.RequestCollector ||
                selectedCollector === CollectorsMap.CommandCollector
            ) {
                return CollectorsMap.EntryCollector;
            }
            return selectedCollector || undefined;
        }
        if (location.pathname.startsWith('/inspector')) {
            // Find matching inspector child
            const match = inspectorChildren.find((c) => location.pathname.startsWith(c.key));
            return match?.key;
        }
        if (location.pathname.startsWith('/llm/mcp')) {
            return '/llm/mcp';
        }
        if (location.pathname.startsWith('/open-api') || location.pathname.startsWith('/frames')) {
            return searchParams.get('tab') || undefined;
        }
        return undefined;
    }, [location.pathname, selectedCollector, searchParams]);

    const handleNavigate = useCallback(
        (href: string) => {
            navigate(href);
        },
        [navigate],
    );

    const handleChildClick = useCallback(
        (sectionKey: string, childKey: string) => {
            if (sectionKey === 'debug') {
                if (childKey === '__entries__') {
                    navigate('/debug/list');
                    return;
                }
                // Navigate to debug page with collector
                if (debugEntry) {
                    navigate(`/debug?collector=${encodeURIComponent(childKey)}&debugEntry=${debugEntry.id}`);
                }
            } else if (sectionKey === 'inspector' || sectionKey === 'llm') {
                navigate(childKey);
            } else if (sectionKey === 'open-api') {
                navigate(`/open-api?tab=${encodeURIComponent(childKey)}`);
            } else if (sectionKey === 'frames') {
                navigate(`/frames?tab=${encodeURIComponent(childKey)}`);
            }
        },
        [navigate, debugEntry],
    );

    const handleMobileNavigate = useCallback(
        (href: string) => {
            handleNavigate(href);
            handleMenuClose();
        },
        [handleNavigate, handleMenuClose],
    );

    const handleMobileChildClick = useCallback(
        (sectionKey: string, childKey: string) => {
            handleChildClick(sectionKey, childKey);
            handleMenuClose();
        },
        [handleChildClick, handleMenuClose],
    );

    return (
        <>
            <CssBaseline />
            <Box sx={{display: 'flex', flexDirection: 'column', height: '100vh'}}>
                <TopBar
                    onMenuClick={isMobile ? handleMenuClick : undefined}
                    method={topBarMethod}
                    path={topBarPath}
                    status={topBarStatus}
                    duration={topBarDuration}
                    autoRefresh={autoLatest}
                    isRefreshing={getDebugQueryInfo.isFetching}
                    showInactiveCollectors={showInactiveCollectors}
                    onPrevEntry={handlePrevEntry}
                    onNextEntry={handleNextEntry}
                    onEntryClick={handleEntryClick}
                    onSearchClick={handleSearchClick}
                    onThemeToggle={handleThemeToggle}
                    onAutoRefreshToggle={autoLatestHandler}
                    onRefresh={handleRefresh}
                    onShowInactiveCollectorsChange={handleShowInactiveCollectorsChange}
                    mcpEnabled={mcpSettings?.enabled}
                    onMcpEnabledChange={handleMcpEnabledChange}
                    editorConfig={editorConfig}
                    onEditorConfigChange={handleEditorConfigChange}
                    notificationCount={notificationCount}
                    liveFeedCount={liveFeedCount}
                    liveFeedActive={liveFeedOpen}
                    onNotificationsClick={handleNotificationsClick}
                    onLiveFeedClick={handleLiveFeedClick}
                    onLogoClick={handleLogoClick}
                    onCopyAsImage={copyAsImage}
                    onDownloadAsImage={downloadAsPng}
                    isCopyingAsImage={isCapturing}
                    websiteUrl={backendUrl}
                    onOpenWebsite={handleOpenWebsite}
                />
                <EntrySelector
                    anchorEl={ui.entrySelectorAnchor}
                    open={Boolean(ui.entrySelectorAnchor)}
                    onClose={handleEntrySelectorClose}
                    entries={entries}
                    currentEntryId={debugEntry?.id}
                    onSelect={changeEntry}
                    onAllClick={handleAllEntriesClick}
                />
                <NotificationCenter
                    anchorEl={ui.notificationAnchor}
                    open={Boolean(ui.notificationAnchor)}
                    onClose={handleNotificationsClose}
                />

                {isMobile && (
                    <Drawer
                        open={ui.mobileMenuOpen}
                        onClose={handleMenuClose}
                        ModalProps={{keepMounted: true}}
                        sx={{'& .MuiDrawer-paper': {width: 240, pt: 1}}}
                    >
                        <UnifiedSidebar
                            sections={sidebarSections}
                            activePath={location.pathname}
                            activeChildKey={activeChildKey}
                            onNavigate={handleMobileNavigate}
                            onChildClick={handleMobileChildClick}
                        />
                    </Drawer>
                )}
                <MainArea>
                    <MainInner expanded={liveFeedOpen && !isMobile}>
                        {!isMobile && (
                            <UnifiedSidebar
                                sections={sidebarSections}
                                activePath={location.pathname}
                                activeChildKey={activeChildKey}
                                onNavigate={handleNavigate}
                                onChildClick={handleChildClick}
                            />
                        )}
                        <ContentStack>
                            <ContentHeaderSlot ref={setHeaderSlotEl} />
                            <ContentArea ref={contentRef}>
                                <ErrorBoundary FallbackComponent={ErrorFallback} resetKeys={[location.pathname]}>
                                    <PageHeaderSlotProvider value={headerSlotEl}>
                                        <Outlet />
                                    </PageHeaderSlotProvider>
                                </ErrorBoundary>
                            </ContentArea>
                        </ContentStack>
                        {liveFeedOpen && !isMobile && <LiveFeedPanel onClose={handleLiveFeedClick} />}
                    </MainInner>
                </MainArea>
                {isMobile && (
                    <Drawer
                        anchor="bottom"
                        open={liveFeedOpen}
                        onClose={handleLiveFeedClick}
                        ModalProps={{keepMounted: true}}
                        PaperProps={{
                            sx: {height: '85vh', borderTopLeftRadius: 16, borderTopRightRadius: 16, overflow: 'hidden'},
                        }}
                    >
                        <LiveFeedPanel onClose={handleLiveFeedClick} />
                    </Drawer>
                )}
            </Box>
            {children}
            {!aiChatOpen && !(isMobile && liveFeedOpen) && (
                <Tooltip title="Duck AI" placement="left">
                    <Fab
                        aria-label="Duck AI"
                        onClick={handleAiChatToggle}
                        sx={{
                            position: 'fixed',
                            bottom: children ? 72 : 24,
                            right: 24,
                            zIndex: 1100,
                            width: 56,
                            height: 56,
                            bgcolor: 'common.white',
                            border: '2.5px solid',
                            borderColor: 'primary.main',
                            boxShadow: '0 4px 12px rgba(37,99,235,0.2)',
                            '&:hover': {bgcolor: 'primary.light'},
                        }}
                    >
                        <DuckIcon sx={{fontSize: 36}} />
                    </Fab>
                </Tooltip>
            )}
            <AiChatPopup open={aiChatOpen} onClose={handleAiChatClose} entry={debugEntry} />
            <ScrollTopButton bottomOffset={!!children} />
            <CommandPalette open={ui.paletteOpen} onClose={handlePaletteClose} extraItems={paletteCollectorItems} />
        </>
    );
});
