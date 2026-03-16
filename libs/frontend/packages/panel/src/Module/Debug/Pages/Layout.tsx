import {useBreadcrumbs} from '@app-dev-panel/panel/Application/Context/BreadcrumbsContext';
import ModuleLoader from '@app-dev-panel/panel/Application/Pages/RemoteComponent';
import {DatabasePanel} from '@app-dev-panel/panel/Module/Debug/Component/Panel/DatabasePanel';
import {EventPanel} from '@app-dev-panel/panel/Module/Debug/Component/Panel/EventPanel';
import {ExceptionPanel} from '@app-dev-panel/panel/Module/Debug/Component/Panel/ExceptionPanel';
import {FilesystemPanel} from '@app-dev-panel/panel/Module/Debug/Component/Panel/FilesystemPanel';
import {LogPanel} from '@app-dev-panel/panel/Module/Debug/Component/Panel/LogPanel';
import {MailerPanel} from '@app-dev-panel/panel/Module/Debug/Component/Panel/MailerPanel';
import {MiddlewarePanel} from '@app-dev-panel/panel/Module/Debug/Component/Panel/MiddlewarePanel';
import {RequestPanel} from '@app-dev-panel/panel/Module/Debug/Component/Panel/RequestPanel';
import {ServicesPanel} from '@app-dev-panel/panel/Module/Debug/Component/Panel/ServicesPanel';
import {TimelinePanel} from '@app-dev-panel/panel/Module/Debug/Component/Panel/TimelinePanel';
import {VarDumperPanel} from '@app-dev-panel/panel/Module/Debug/Component/Panel/VarDumperPanel';
import {DumpPage} from '@app-dev-panel/panel/Module/Debug/Pages/DumpPage';
import {useDoRequestMutation, usePostCurlBuildMutation} from '@app-dev-panel/panel/Module/Inspector/API/Inspector';
import {useSelector} from '@app-dev-panel/panel/store';
import {changeAutoLatest} from '@app-dev-panel/sdk/API/Application/ApplicationContext';
import {changeEntryAction, useDebugEntry} from '@app-dev-panel/sdk/API/Debug/Context';
import {DebugEntry, useLazyGetCollectorInfoQuery, useLazyGetDebugQuery} from '@app-dev-panel/sdk/API/Debug/Debug';
import {ErrorFallback} from '@app-dev-panel/sdk/Component/ErrorFallback';
import {FullScreenCircularProgress} from '@app-dev-panel/sdk/Component/FullScreenCircularProgress';
import {InfoBox} from '@app-dev-panel/sdk/Component/InfoBox';
import {CollectorSidebar} from '@app-dev-panel/sdk/Component/Layout/CollectorSidebar';
import {CommandPalette} from '@app-dev-panel/sdk/Component/Layout/CommandPalette';
import {ContentPanel} from '@app-dev-panel/sdk/Component/Layout/ContentPanel';
import {EntrySelector} from '@app-dev-panel/sdk/Component/Layout/EntrySelector';
import {TopBar} from '@app-dev-panel/sdk/Component/Layout/TopBar';
import {ScrollTopButton} from '@app-dev-panel/sdk/Component/ScrollTop';
import {componentTokens} from '@app-dev-panel/sdk/Component/Theme/tokens';
import {EventTypesEnum, useServerSentEvents} from '@app-dev-panel/sdk/Component/useServerSentEvents';
import {compareCollectorWeight, getCollectorIcon, getCollectorLabel} from '@app-dev-panel/sdk/Helper/collectorMeta';
import {CollectorsMap} from '@app-dev-panel/sdk/Helper/collectors';
import {getCollectedCountByCollector} from '@app-dev-panel/sdk/Helper/collectorsTotal';
import {isDebugEntryAboutWeb} from '@app-dev-panel/sdk/Helper/debugEntry';
import {formatMillisecondsAsDuration} from '@app-dev-panel/sdk/Helper/formatDate';
import {EmojiObjects, HelpOutline} from '@mui/icons-material';
import {Alert, AlertTitle, Box, LinearProgress, Link, Typography} from '@mui/material';
import {styled} from '@mui/material/styles';
import clipboardCopy from 'clipboard-copy';
import * as React from 'react';
import {useCallback, useEffect, useMemo, useState} from 'react';
import {ErrorBoundary} from 'react-error-boundary';
import {useDispatch} from 'react-redux';
import {Outlet} from 'react-router';
import {useSearchParams} from 'react-router-dom';

// ---------------------------------------------------------------------------
// Styled layout containers
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

// ---------------------------------------------------------------------------
// Collector data renderer (preserved from original)
// ---------------------------------------------------------------------------

type CollectorDataProps = {collectorData: any; selectedCollector: string};
function CollectorData({collectorData, selectedCollector}: CollectorDataProps) {
    const baseUrl = useSelector((state) => state.application.baseUrl) as string;
    const pages: {[name: string]: (data: any) => JSX.Element} = {
        [CollectorsMap.MailerCollector]: (data: any) => <MailerPanel data={data} />,
        [CollectorsMap.ServiceCollector]: (data: any) => <ServicesPanel data={data} />,
        [CollectorsMap.TimelineCollector]: (data: any) => <TimelinePanel data={data} />,
        [CollectorsMap.LogCollector]: (data: any) => <LogPanel data={data} />,
        [CollectorsMap.DatabaseCollector]: (data: any) => <DatabasePanel data={data} />,
        [CollectorsMap.FilesystemStreamCollector]: (data: any) => <FilesystemPanel data={data} />,
        [CollectorsMap.RequestCollector]: (data: any) => <RequestPanel data={data} />,
        [CollectorsMap.MiddlewareCollector]: (data: any) => <MiddlewarePanel {...data} />,
        [CollectorsMap.EventCollector]: (data: any) => <EventPanel events={data} />,
        [CollectorsMap.ExceptionCollector]: (data: any) => <ExceptionPanel exceptions={data} />,
        [CollectorsMap.VarDumperCollector]: (data: any) => <VarDumperPanel data={data} />,
        default: (data: any) => {
            if (typeof data === 'object' && data.__isPanelRemote__) {
                return (
                    <React.Suspense fallback={`Loading`}>
                        <ModuleLoader
                            url={baseUrl + data.url}
                            module={data.module}
                            scope={data.scope}
                            props={{data: data.data}}
                        />
                    </React.Suspense>
                );
            }
            if (typeof data === 'string') {
                try {
                    JSON.parse(data);
                } catch (e) {
                    if (e instanceof SyntaxError) {
                        return (
                            <Box component="pre" sx={{whiteSpace: 'pre-wrap', wordBreak: 'break-word'}}>
                                {data}
                            </Box>
                        );
                    }
                    console.error(e);
                }
            }
            return <DumpPage data={data} />;
        },
    };

    if (selectedCollector === '') {
        return <Outlet />;
    }

    const renderPage = selectedCollector in pages ? pages[selectedCollector] : pages.default;
    return renderPage(collectorData);
}

function HttpRequestError({error}: {error: any}) {
    console.error(error);
    return (
        <Box m={2}>
            <Alert severity="error">
                <AlertTitle>Something went wrong:</AlertTitle>
                <pre>{error?.toString() || 'unknown'}</pre>
            </Alert>
        </Box>
    );
}

const EmptyCollectorsInfoBox = React.memo(() => (
    <InfoBox
        title="Collectors are empty"
        text="Looks like debugger was inactive or it did not have any active collectors during the request"
        severity="info"
        icon={<HelpOutline />}
    />
));

// ---------------------------------------------------------------------------
// Main Layout — new floating sidebar + centered layout
// ---------------------------------------------------------------------------

const Layout = () => {
    const dispatch = useDispatch();
    const [autoLatest, setAutoLatest] = useState<boolean>(false);
    const debugEntry = useDebugEntry();
    const [searchParams, setSearchParams] = useSearchParams();
    const [getDebugQuery, getDebugQueryInfo] = useLazyGetDebugQuery();
    const [selectedCollector, setSelectedCollector] = useState<string>(() => searchParams.get('collector') || '');
    const [collectorData, setCollectorData] = useState<any>(undefined);
    const [collectorInfo, collectorQueryInfo] = useLazyGetCollectorInfoQuery();
    const [postCurlBuildInfo, postCurlBuildQueryInfo] = usePostCurlBuildMutation();
    const autoLatestState = useSelector((state) => state.application.autoLatest);
    const backendUrl = useSelector((state) => state.application.baseUrl) as string;

    const onRefreshHandler = useCallback(() => {
        getDebugQuery();
    }, [getDebugQuery]);
    useEffect(onRefreshHandler, [onRefreshHandler]);

    useEffect(() => {
        setAutoLatest(autoLatestState);
    }, [autoLatestState]);

    useEffect(() => {
        if (getDebugQueryInfo.isSuccess && getDebugQueryInfo.data && getDebugQueryInfo.data.length) {
            if (!searchParams.has('debugEntry')) {
                changeEntry(getDebugQueryInfo.data[0]);
                return;
            }
            const entry = getDebugQueryInfo.data.find((entry) => entry.id === searchParams.get('debugEntry'));
            if (!entry) {
                changeEntry(getDebugQueryInfo.data[0]);
            }
        }
    }, [getDebugQueryInfo.isSuccess, getDebugQueryInfo.data]);

    const clearCollectorAndData = () => {
        searchParams.delete('collector');
        setSelectedCollector('');
        setCollectorData(null);
    };

    useEffect(() => {
        const collector = searchParams.get('collector') || '';
        if (collector.trim() === '') {
            clearCollectorAndData();
            return;
        }
        if (!debugEntry) {
            return;
        }
        collectorInfo({id: debugEntry.id, collector})
            .then(({data, isError}) => {
                if (isError) {
                    clearCollectorAndData();
                    changeEntry(null);
                    return;
                }
                setSelectedCollector(collector);
                setCollectorData(data);
            })
            .catch(clearCollectorAndData);
    }, [searchParams, debugEntry]);

    useEffect(() => {
        if (debugEntry) {
            setSearchParams((params) => {
                params.set('debugEntry', debugEntry.id);
                return params;
            });
        } else {
            setSearchParams({});
        }
    }, [debugEntry, setSearchParams]);

    const changeEntry = useCallback(
        (entry: DebugEntry | null) => {
            dispatch(changeEntryAction(entry ? entry : null));
        },
        [dispatch],
    );
    const collectorName = useMemo(() => selectedCollector.split('\\').pop(), [selectedCollector]);

    // Build sidebar navigation items from the debug entry's collectors
    const sidebarItems = useMemo(() => {
        if (!debugEntry) return [];
        return [...debugEntry.collectors]
            .filter((c): c is string => typeof c === 'string')
            .sort(compareCollectorWeight)
            .map((collector) => {
                const count = getCollectedCountByCollector(collector as CollectorsMap, debugEntry);
                const isException = collector === CollectorsMap.ExceptionCollector && count && count > 0;
                return {
                    key: collector,
                    icon: getCollectorIcon(collector),
                    label: getCollectorLabel(collector),
                    badge: count,
                    badgeVariant: (isException ? 'error' : 'default') as 'error' | 'default',
                };
            });
    }, [debugEntry]);

    const handleCollectorClick = useCallback(
        (key: string) => {
            if (!debugEntry) return;
            setSearchParams((params) => {
                params.set('collector', key);
                params.set('debugEntry', debugEntry.id);
                return params;
            });
        },
        [debugEntry, setSearchParams],
    );

    const handleOverviewClick = useCallback(() => {
        setSearchParams((params) => {
            params.delete('collector');
            return params;
        });
    }, [setSearchParams]);

    // TopBar request info
    const topBarMethod = debugEntry && isDebugEntryAboutWeb(debugEntry) ? debugEntry.request?.method : undefined;
    const topBarPath = debugEntry && isDebugEntryAboutWeb(debugEntry) ? debugEntry.request?.path : undefined;
    const topBarStatus = debugEntry && isDebugEntryAboutWeb(debugEntry) ? debugEntry.response?.statusCode : undefined;
    const topBarDuration =
        debugEntry && isDebugEntryAboutWeb(debugEntry) && debugEntry.web?.request?.processingTime
            ? formatMillisecondsAsDuration(debugEntry.web.request.processingTime)
            : undefined;

    // Navigate between entries
    const entries = getDebugQueryInfo.data ?? [];
    const currentIndex = debugEntry ? entries.findIndex((e) => e.id === debugEntry.id) : -1;

    const handlePrevEntry = useCallback(() => {
        if (currentIndex > 0) {
            changeEntry(entries[currentIndex - 1]);
        }
    }, [currentIndex, entries]);

    const handleNextEntry = useCallback(() => {
        if (currentIndex < entries.length - 1) {
            changeEntry(entries[currentIndex + 1]);
        }
    }, [currentIndex, entries]);

    // Entry selector popover
    const [entrySelectorAnchor, setEntrySelectorAnchor] = useState<HTMLElement | null>(null);
    const handleEntryClick = useCallback((e?: React.MouseEvent) => {
        const target = (e?.currentTarget as HTMLElement) ?? null;
        setEntrySelectorAnchor((prev) => (prev ? null : target));
    }, []);

    // Command palette
    const [paletteOpen, setPaletteOpen] = useState(false);
    const handleSearchClick = useCallback(() => setPaletteOpen(true), []);
    const handlePaletteClose = useCallback(() => setPaletteOpen(false), []);

    // Ctrl+K shortcut
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

    const [doRequest, doRequestInfo] = useDoRequestMutation();
    const repeatRequestHandler = useCallback(async () => {
        if (!debugEntry) return;
        try {
            await doRequest({id: debugEntry.id});
        } catch (e) {
            console.error(e);
        }
        getDebugQuery();
    }, [debugEntry]);
    const copyCurlHandler = useCallback(async () => {
        if (!debugEntry) return;
        const result = await postCurlBuildInfo(debugEntry.id);
        if ('error' in result) {
            console.error(result.error);
            return;
        }
        console.log(result.data.command);
        clipboardCopy(result.data.command);
    }, [debugEntry]);
    const onEntryChangeHandler = useCallback(changeEntry, [changeEntry]);

    const onUpdatesHandler = useCallback(async (event: MessageEvent) => {
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
    }, []);
    useServerSentEvents(backendUrl, onUpdatesHandler, autoLatest);

    const autoLatestHandler = () => {
        setAutoLatest((prev) => {
            dispatch(changeAutoLatest(!prev));
            return !prev;
        });
    };

    useBreadcrumbs(() => ['Debug', collectorName]);

    if (getDebugQueryInfo.isLoading) {
        return <FullScreenCircularProgress />;
    }

    if (getDebugQueryInfo.data && getDebugQueryInfo.data.length === 0) {
        return (
            <InfoBox
                title="No debug entries found"
                text={
                    <>
                        <Typography>Make sure you have enabled debugger and run your application.</Typography>
                        <Typography>
                            Check the "app-dev-panel/kernel" in the "params.php" on the backend or with{' '}
                            <Link href="/inspector/config/parameters?filter=app-dev-panel/kernel">Inspector</Link>.
                        </Typography>
                        <Typography>
                            See more information on the link{' '}
                            <Link href="https://github.com/app-dev-panel/app-dev-panel">
                                https://github.com/app-dev-panel/app-dev-panel
                            </Link>
                            .
                        </Typography>
                    </>
                }
                severity="info"
                icon={<EmojiObjects />}
            />
        );
    }

    return (
        <>
            <Box sx={{display: 'flex', flexDirection: 'column', height: '100vh'}}>
                <TopBar
                    method={topBarMethod}
                    path={topBarPath}
                    status={topBarStatus}
                    duration={topBarDuration}
                    onPrevEntry={handlePrevEntry}
                    onNextEntry={handleNextEntry}
                    onEntryClick={handleEntryClick}
                    onSearchClick={handleSearchClick}
                />
                <EntrySelector
                    anchorEl={entrySelectorAnchor}
                    open={Boolean(entrySelectorAnchor)}
                    onClose={() => setEntrySelectorAnchor(null)}
                    entries={entries}
                    currentEntryId={debugEntry?.id}
                    onSelect={changeEntry}
                />
                <MainArea>
                    <MainInner>
                        {sidebarItems.length === 0 ? (
                            <ContentPanel>
                                <EmptyCollectorsInfoBox />
                            </ContentPanel>
                        ) : (
                            <>
                                <CollectorSidebar
                                    items={sidebarItems}
                                    activeKey={selectedCollector}
                                    onItemClick={handleCollectorClick}
                                    onOverviewClick={handleOverviewClick}
                                />
                                <ContentPanel>
                                    {selectedCollector ? (
                                        <>
                                            {collectorQueryInfo.isFetching && <LinearProgress />}
                                            {collectorQueryInfo.isError && (
                                                <HttpRequestError
                                                    error={
                                                        (collectorQueryInfo.error as any)?.error ||
                                                        (collectorQueryInfo.error as any)
                                                    }
                                                />
                                            )}
                                            {collectorQueryInfo.isSuccess && (
                                                <ErrorBoundary
                                                    FallbackComponent={ErrorFallback}
                                                    resetKeys={[
                                                        window.location.pathname,
                                                        window.location.search,
                                                        debugEntry,
                                                    ]}
                                                >
                                                    <CollectorData
                                                        selectedCollector={selectedCollector}
                                                        collectorData={collectorData}
                                                    />
                                                </ErrorBoundary>
                                            )}
                                        </>
                                    ) : (
                                        <Outlet />
                                    )}
                                </ContentPanel>
                            </>
                        )}
                    </MainInner>
                </MainArea>
            </Box>
            <ScrollTopButton bottomOffset={false} />
            <CommandPalette open={paletteOpen} onClose={handlePaletteClose} />
        </>
    );
};
export {Layout};
