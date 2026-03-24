import {NotificationCenter} from '@app-dev-panel/panel/Application/Component/NotificationCenter';

import {
    useGetMcpSettingsQuery,
    useUpdateMcpSettingsMutation,
} from '@app-dev-panel/panel/Module/Inspector/API/Inspector';
import {useSelector} from '@app-dev-panel/panel/store';
import {
    changeAutoLatest,
    changeEditorCustomTemplate,
    changeEditorPreset,
    changeShowInactiveCollectors,
    changeThemeMode,
} from '@app-dev-panel/sdk/API/Application/ApplicationContext';
import {changeEntryAction, useDebugEntry} from '@app-dev-panel/sdk/API/Debug/Context';
import {DebugEntry, debugApi, useLazyGetDebugQuery} from '@app-dev-panel/sdk/API/Debug/Debug';
import {ErrorFallback} from '@app-dev-panel/sdk/Component/ErrorFallback';
import {CommandPalette} from '@app-dev-panel/sdk/Component/Layout/CommandPalette';
import {EntrySelector} from '@app-dev-panel/sdk/Component/Layout/EntrySelector';
import {TopBar} from '@app-dev-panel/sdk/Component/Layout/TopBar';
import {UnifiedSidebar} from '@app-dev-panel/sdk/Component/Layout/UnifiedSidebar';
import {selectUnreadCount} from '@app-dev-panel/sdk/Component/Notifications';
import {ScrollTopButton} from '@app-dev-panel/sdk/Component/ScrollTop';
import {componentTokens} from '@app-dev-panel/sdk/Component/Theme/tokens';
import {EventTypesEnum, useServerSentEvents} from '@app-dev-panel/sdk/Component/useServerSentEvents';
import {compareCollectorWeight, getCollectorIcon, getCollectorLabel} from '@app-dev-panel/sdk/Helper/collectorMeta';
import {CollectorsMap} from '@app-dev-panel/sdk/Helper/collectors';
import {getCollectedCountByCollector} from '@app-dev-panel/sdk/Helper/collectorsTotal';
import {isDebugEntryAboutConsole, isDebugEntryAboutWeb} from '@app-dev-panel/sdk/Helper/debugEntry';
import {type EditorPreset, defaultEditorConfig} from '@app-dev-panel/sdk/Helper/editorUrl';
import {formatMillisecondsAsDuration} from '@app-dev-panel/sdk/Helper/formatDate';
import Box from '@mui/material/Box';
import CssBaseline from '@mui/material/CssBaseline';
import {styled} from '@mui/material/styles';
import * as React from 'react';
import {useCallback, useEffect, useMemo, useState} from 'react';
import {ErrorBoundary} from 'react-error-boundary';
import {useDispatch} from 'react-redux';
import {Outlet, useLocation, useNavigate, useSearchParams} from 'react-router-dom';

// ---------------------------------------------------------------------------
// Collectors hidden from sidebar (shown in overview instead)
// ---------------------------------------------------------------------------
const hiddenCollectors = new Set<string>([
    CollectorsMap.WebAppInfoCollector,
    CollectorsMap.ConsoleAppInfoCollector,
    CollectorsMap.HttpStreamCollector,
]);

// ---------------------------------------------------------------------------
// Static inspector sub-items
// ---------------------------------------------------------------------------
const inspectorChildren = [
    {key: '/inspector/config', icon: 'settings', label: 'Configuration'},
    {key: '/inspector/events', icon: 'bolt', label: 'Event Listeners'},
    {key: '/inspector/routes', icon: 'alt_route', label: 'Routes'},
    {key: '/inspector/tests', icon: 'science', label: 'Tests'},
    {key: '/inspector/analyse', icon: 'analytics', label: 'Analyse'},
    {key: '/inspector/files', icon: 'folder_open', label: 'File Explorer'},
    {key: '/inspector/translations', icon: 'translate', label: 'Translations'},
    {key: '/inspector/commands', icon: 'terminal', label: 'Commands'},
    {key: '/inspector/database', icon: 'storage', label: 'Database'},
    {key: '/inspector/cache', icon: 'cached', label: 'Cache'},
    {key: '/inspector/git', icon: 'code', label: 'Git'},
    {key: '/inspector/phpinfo', icon: 'info', label: 'PHP Info'},
    {key: '/inspector/composer', icon: 'inventory_2', label: 'Composer'},
    {key: '/inspector/opcache', icon: 'speed', label: 'Opcache'},
];

// ---------------------------------------------------------------------------
// Styled components
// ---------------------------------------------------------------------------

const MainArea = styled(Box)({
    flex: 1,
    overflow: 'hidden',
    display: 'flex',
    justifyContent: 'center',
    padding: componentTokens.mainGap,
    gap: componentTokens.mainGap,
});

const MainInner = styled(Box)({
    display: 'flex',
    width: '100%',
    maxWidth: componentTokens.mainMaxWidth,
    gap: componentTokens.mainGap,
});

const ContentArea = styled(Box)(({theme}) => ({
    flex: 1,
    minWidth: 0,
    borderRadius: componentTokens.contentPanel.borderRadius,
    backgroundColor: theme.palette.background.paper,
    border: `1px solid ${theme.palette.divider}`,
    padding: theme.spacing(3.5, 4.5),
    overflowY: 'auto',
}));

// ---------------------------------------------------------------------------
// Layout component
// ---------------------------------------------------------------------------

export const Layout = React.memo(({children}: React.PropsWithChildren) => {
    const location = useLocation();
    const navigate = useNavigate();
    const dispatch = useDispatch();
    const [searchParams, setSearchParams] = useSearchParams();

    // Debug entry state
    const debugEntry = useDebugEntry();
    const [getDebugQuery, getDebugQueryInfo] = useLazyGetDebugQuery();
    const backendUrl = useSelector((state) => state.application.baseUrl) as string;
    const autoLatestState = useSelector((state) => state.application.autoLatest);
    const [autoLatest, setAutoLatest] = useState<boolean>(false);
    const themeMode = useSelector((state) => state.application.themeMode) as string | undefined;
    const currentMode = themeMode || 'system';
    const showInactiveCollectors = useSelector((state) => state.application.showInactiveCollectors) as boolean;
    const editorConfig = useSelector((state) => state.application.editorConfig) ?? defaultEditorConfig;
    const notificationCount = useSelector(selectUnreadCount);

    // MCP settings
    const {data: mcpSettings} = useGetMcpSettingsQuery();
    const [updateMcpSettings] = useUpdateMcpSettingsMutation();

    // Notification center popover
    const [notificationAnchor, setNotificationAnchor] = useState<HTMLElement | null>(null);
    const handleNotificationsClick = useCallback((e: React.MouseEvent<HTMLElement>) => {
        setNotificationAnchor((prev) => (prev ? null : e.currentTarget));
    }, []);
    const handleNotificationsClose = useCallback(() => setNotificationAnchor(null), []);

    // Fetch debug entries on mount and when backend URL changes
    useEffect(() => {
        dispatch(changeEntryAction(null));
        dispatch(debugApi.util.resetApiState());
        getDebugQuery();
    }, [getDebugQuery, backendUrl, dispatch]);

    useEffect(() => {
        setAutoLatest(autoLatestState);
    }, [autoLatestState]);

    // Auto-select first entry when data loads
    useEffect(() => {
        if (getDebugQueryInfo.isSuccess && getDebugQueryInfo.data && getDebugQueryInfo.data.length) {
            if (!debugEntry) {
                dispatch(changeEntryAction(getDebugQueryInfo.data[0]));
            }
        }
    }, [getDebugQueryInfo.isSuccess, getDebugQueryInfo.data, dispatch, debugEntry]);

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
            if (data.type && data.type === EventTypesEnum.DebugUpdated) {
                const result = await getDebugQuery();
                if ('data' in result && result.data.length > 0) {
                    changeEntry(result.data[0]);
                }
            }
        },
        [getDebugQuery, changeEntry],
    );
    useServerSentEvents(backendUrl, onUpdatesHandler, autoLatest);

    // Entry navigation
    const entries = getDebugQueryInfo.data ?? [];
    const currentIndex = debugEntry ? entries.findIndex((e) => e.id === debugEntry.id) : -1;

    const handlePrevEntry = useCallback(() => {
        if (currentIndex > 0) changeEntry(entries[currentIndex - 1]);
    }, [currentIndex, entries, changeEntry]);

    const handleNextEntry = useCallback(() => {
        if (currentIndex < entries.length - 1) changeEntry(entries[currentIndex + 1]);
    }, [currentIndex, entries, changeEntry]);

    // Entry selector popover
    const [entrySelectorAnchor, setEntrySelectorAnchor] = useState<HTMLElement | null>(null);
    const handleEntryClick = useCallback((e?: React.MouseEvent) => {
        const target = (e?.currentTarget as HTMLElement) ?? null;
        setEntrySelectorAnchor((prev) => (prev ? null : target));
    }, []);

    // Theme toggle
    const handleThemeToggle = useCallback(() => {
        const next = currentMode === 'dark' ? 'light' : 'dark';
        dispatch(changeThemeMode(next as 'light' | 'dark'));
    }, [dispatch, currentMode]);

    // Command palette
    const [paletteOpen, setPaletteOpen] = useState(false);
    const handleSearchClick = useCallback(() => setPaletteOpen(true), []);
    const handleLogoClick = useCallback(() => navigate('/'), [navigate]);
    const handlePaletteClose = useCallback(() => setPaletteOpen(false), []);

    useEffect(() => {
        const handler = (e: KeyboardEvent) => {
            if ((e.metaKey || e.ctrlKey) && e.key === 'k') {
                e.preventDefault();
                setPaletteOpen((prev) => !prev);
            }
        };
        document.addEventListener('keydown', handler);
        return () => document.removeEventListener('keydown', handler);
    }, []);

    const autoLatestHandler = useCallback(() => {
        setAutoLatest((prev) => {
            dispatch(changeAutoLatest(!prev));
            return !prev;
        });
    }, [dispatch]);

    const handleShowInactiveCollectorsChange = useCallback(
        (value: boolean) => {
            dispatch(changeShowInactiveCollectors(value));
        },
        [dispatch],
    );

    const handleEditorPresetChange = useCallback(
        (preset: EditorPreset) => {
            dispatch(changeEditorPreset(preset));
        },
        [dispatch],
    );

    const handleEditorCustomTemplateChange = useCallback(
        (template: string) => {
            dispatch(changeEditorCustomTemplate(template));
        },
        [dispatch],
    );

    const handleMcpEnabledChange = useCallback(
        (value: boolean) => {
            updateMcpSettings({enabled: value});
        },
        [updateMcpSettings],
    );

    // TopBar debug info
    const topBarMethod = debugEntry && isDebugEntryAboutWeb(debugEntry) ? debugEntry.request?.method : undefined;
    const topBarPath = debugEntry && isDebugEntryAboutWeb(debugEntry) ? debugEntry.request?.path : undefined;
    const topBarStatus = debugEntry && isDebugEntryAboutWeb(debugEntry) ? debugEntry.response?.statusCode : undefined;
    const topBarDuration =
        debugEntry && isDebugEntryAboutWeb(debugEntry) && debugEntry.web?.request?.processingTime
            ? formatMillisecondsAsDuration(debugEntry.web.request.processingTime)
            : undefined;

    // Build sidebar sections
    const selectedCollector = searchParams.get('collector') || '';

    const debugChildren = useMemo(() => {
        const entriesList = [{key: '__entries__', icon: 'list', label: 'All Entries'}];
        if (!debugEntry) return entriesList;
        const overview = [{key: '__overview__', icon: 'grid_view', label: 'Overview'}];
        const isWeb = isDebugEntryAboutWeb(debugEntry);
        const isConsole = isDebugEntryAboutConsole(debugEntry);
        const collectors = [...debugEntry.collectors]
            .map((c) => (typeof c === 'string' ? c : c.id))
            .filter((c) => !hiddenCollectors.has(c))
            .filter((c) => {
                if (isWeb && c === CollectorsMap.CommandCollector) return false;
                if (isConsole && c === CollectorsMap.RequestCollector) return false;
                return true;
            })
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
            .filter((c) => showInactiveCollectors || (c.badge != null && c.badge > 0));
        return [...overview, ...collectors, ...entriesList];
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
            {key: 'llm', icon: 'psychology', label: 'LLM', href: '/llm'},
            {key: 'open-api', icon: 'data_object', label: 'Open API', href: '/open-api'},
            {key: 'frames', icon: 'web', label: 'Frames', href: '/frames'},
        ],
        [debugChildren],
    );

    // Determine active child key
    const activeChildKey = useMemo(() => {
        if (location.pathname === '/debug/list') {
            return '__entries__';
        }
        if (location.pathname.startsWith('/debug')) {
            return selectedCollector || '__overview__';
        }
        if (location.pathname.startsWith('/inspector')) {
            // Find matching inspector child
            const match = inspectorChildren.find((c) => location.pathname.startsWith(c.key));
            return match?.key;
        }
        return undefined;
    }, [location.pathname, selectedCollector]);

    const handleNavigate = useCallback(
        (href: string) => {
            navigate(href);
        },
        [navigate],
    );

    const handleChildClick = useCallback(
        (sectionKey: string, childKey: string) => {
            if (sectionKey === 'debug') {
                if (childKey === '__overview__') {
                    navigate('/debug');
                    return;
                }
                if (childKey === '__entries__') {
                    navigate('/debug/list');
                    return;
                }
                // Navigate to debug page with collector
                if (debugEntry) {
                    navigate(`/debug?collector=${encodeURIComponent(childKey)}&debugEntry=${debugEntry.id}`);
                }
            } else if (sectionKey === 'inspector') {
                navigate(childKey);
            }
        },
        [navigate, debugEntry],
    );

    return (
        <>
            <CssBaseline />
            <Box sx={{display: 'flex', flexDirection: 'column', height: '100vh'}}>
                <TopBar
                    method={topBarMethod}
                    path={topBarPath}
                    status={topBarStatus}
                    duration={topBarDuration}
                    autoRefresh={autoLatest}
                    showInactiveCollectors={showInactiveCollectors}
                    onPrevEntry={handlePrevEntry}
                    onNextEntry={handleNextEntry}
                    onEntryClick={handleEntryClick}
                    onSearchClick={handleSearchClick}
                    onThemeToggle={handleThemeToggle}
                    onAutoRefreshToggle={autoLatestHandler}
                    onShowInactiveCollectorsChange={handleShowInactiveCollectorsChange}
                    mcpEnabled={mcpSettings?.enabled}
                    onMcpEnabledChange={handleMcpEnabledChange}
                    editorPreset={editorConfig.editor}
                    editorCustomTemplate={editorConfig.customUrlTemplate}
                    onEditorPresetChange={handleEditorPresetChange}
                    onEditorCustomTemplateChange={handleEditorCustomTemplateChange}
                    notificationCount={notificationCount}
                    onNotificationsClick={handleNotificationsClick}
                    onLogoClick={handleLogoClick}
                />
                <EntrySelector
                    anchorEl={entrySelectorAnchor}
                    open={Boolean(entrySelectorAnchor)}
                    onClose={() => setEntrySelectorAnchor(null)}
                    entries={entries}
                    currentEntryId={debugEntry?.id}
                    onSelect={changeEntry}
                    onAllClick={() => navigate('/debug/list')}
                />
                <NotificationCenter
                    anchorEl={notificationAnchor}
                    open={Boolean(notificationAnchor)}
                    onClose={handleNotificationsClose}
                />

                <MainArea>
                    <MainInner>
                        <UnifiedSidebar
                            sections={sidebarSections}
                            activePath={location.pathname}
                            activeChildKey={activeChildKey}
                            onNavigate={handleNavigate}
                            onChildClick={handleChildClick}
                        />
                        <ContentArea>
                            <ErrorBoundary FallbackComponent={ErrorFallback} resetKeys={[location.pathname]}>
                                <Outlet />
                            </ErrorBoundary>
                        </ContentArea>
                    </MainInner>
                </MainArea>
            </Box>
            {children}
            <ScrollTopButton bottomOffset={!!children} />
            <CommandPalette open={paletteOpen} onClose={handlePaletteClose} extraItems={paletteCollectorItems} />
        </>
    );
});
