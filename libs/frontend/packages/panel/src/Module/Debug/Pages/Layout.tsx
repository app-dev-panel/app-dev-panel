import ModuleLoader from '@app-dev-panel/panel/Application/Pages/RemoteComponent';
import {AssetBundlePanel} from '@app-dev-panel/panel/Module/Debug/Component/Panel/AssetBundlePanel';
import {AuthorizationPanel} from '@app-dev-panel/panel/Module/Debug/Component/Panel/AuthorizationPanel';
import {CachePanel} from '@app-dev-panel/panel/Module/Debug/Component/Panel/CachePanel';
import {CodeCoveragePanel} from '@app-dev-panel/panel/Module/Debug/Component/Panel/CodeCoveragePanel';
import {CommandPanel} from '@app-dev-panel/panel/Module/Debug/Component/Panel/CommandPanel';
import {DatabasePanel} from '@app-dev-panel/panel/Module/Debug/Component/Panel/DatabasePanel';
import {ElasticsearchPanel} from '@app-dev-panel/panel/Module/Debug/Component/Panel/ElasticsearchPanel';
import {EnvironmentPanel} from '@app-dev-panel/panel/Module/Debug/Component/Panel/EnvironmentPanel';
import {EventPanel} from '@app-dev-panel/panel/Module/Debug/Component/Panel/EventPanel';
import {ExceptionPanel} from '@app-dev-panel/panel/Module/Debug/Component/Panel/ExceptionPanel';
import {IOPanel} from '@app-dev-panel/panel/Module/Debug/Component/Panel/IOPanel';
import {MailerPanel} from '@app-dev-panel/panel/Module/Debug/Component/Panel/MailerPanel';
import {MiddlewarePanel} from '@app-dev-panel/panel/Module/Debug/Component/Panel/MiddlewarePanel';
import {OpenTelemetryPanel} from '@app-dev-panel/panel/Module/Debug/Component/Panel/OpenTelemetryPanel';
import {QueuePanel} from '@app-dev-panel/panel/Module/Debug/Component/Panel/QueuePanel';
import {RedisPanel} from '@app-dev-panel/panel/Module/Debug/Component/Panel/RedisPanel';
import {RequestPanel} from '@app-dev-panel/panel/Module/Debug/Component/Panel/RequestPanel';
import {RouterPanel} from '@app-dev-panel/panel/Module/Debug/Component/Panel/RouterPanel';
import {ServicesPanel} from '@app-dev-panel/panel/Module/Debug/Component/Panel/ServicesPanel';
import {TemplatePanel} from '@app-dev-panel/panel/Module/Debug/Component/Panel/TemplatePanel';
import {TimelinePanel} from '@app-dev-panel/panel/Module/Debug/Component/Panel/TimelinePanel';
import {TranslatorPanel} from '@app-dev-panel/panel/Module/Debug/Component/Panel/TranslatorPanel';
import {UnifiedLogPanel} from '@app-dev-panel/panel/Module/Debug/Component/Panel/UnifiedLogPanel';
import {ValidatorPanel} from '@app-dev-panel/panel/Module/Debug/Component/Panel/ValidatorPanel';
import {DumpPage} from '@app-dev-panel/panel/Module/Debug/Pages/DumpPage';
import {useSelector} from '@app-dev-panel/panel/store';
import {changeEntryAction, useDebugEntry} from '@app-dev-panel/sdk/API/Debug/Context';
import {useGetDebugQuery, useLazyGetCollectorInfoQuery} from '@app-dev-panel/sdk/API/Debug/Debug';
import {DuckIcon} from '@app-dev-panel/sdk/Component/DuckIcon';
import {EmptyState} from '@app-dev-panel/sdk/Component/EmptyState';
import {ErrorFallback} from '@app-dev-panel/sdk/Component/ErrorFallback';
import {InfoBox} from '@app-dev-panel/sdk/Component/InfoBox';
import {CollectorsMap} from '@app-dev-panel/sdk/Helper/collectors';
import {isDebugEntryAboutConsole, isDebugEntryAboutWeb} from '@app-dev-panel/sdk/Helper/debugEntry';
import {extractErrorMessage} from '@app-dev-panel/sdk/Helper/extractErrorMessage';
import {Alert, AlertTitle, Box, Button, LinearProgress} from '@mui/material';
import * as React from 'react';
import {useCallback, useEffect, useMemo, useState} from 'react';
import {ErrorBoundary} from 'react-error-boundary';
import {useDispatch} from 'react-redux';
import {Outlet, useNavigate, useSearchParams} from 'react-router';

// ---------------------------------------------------------------------------
// Collector data renderer
// ---------------------------------------------------------------------------

// ---------------------------------------------------------------------------
// UnifiedLogWrapper — fetches deprecation & dump data alongside log data
// ---------------------------------------------------------------------------

const UnifiedLogWrapper = ({logs}: {logs: any}) => {
    const debugEntry = useDebugEntry();
    const [fetchCollector] = useLazyGetCollectorInfoQuery();
    const [deprecations, setDeprecations] = useState<any[]>([]);
    const [dumps, setDumps] = useState<any[]>([]);

    const collectors = useMemo(() => {
        if (!debugEntry) return new Set<string>();
        return new Set(debugEntry.collectors.map((c: any) => (typeof c === 'string' ? c : c.id)));
    }, [debugEntry]);

    useEffect(() => {
        if (!debugEntry) return;
        if (collectors.has(CollectorsMap.DeprecationCollector)) {
            fetchCollector({id: debugEntry.id, collector: CollectorsMap.DeprecationCollector}).then(({data}) => {
                setDeprecations(Array.isArray(data) ? data : []);
            });
        } else {
            setDeprecations([]);
        }
        if (collectors.has(CollectorsMap.VarDumperCollector)) {
            fetchCollector({id: debugEntry.id, collector: CollectorsMap.VarDumperCollector}).then(({data}) => {
                setDumps(Array.isArray(data) ? data : []);
            });
        } else {
            setDumps([]);
        }
    }, [debugEntry, collectors, fetchCollector]);

    return <UnifiedLogPanel logs={logs} deprecations={deprecations} dumps={dumps} />;
};

// ---------------------------------------------------------------------------
// IOWrapper — fetches both filesystem and HTTP data for the unified I/O panel
// ---------------------------------------------------------------------------

const IOWrapper = ({primaryCollector, primaryData}: {primaryCollector: string; primaryData: any}) => {
    const debugEntry = useDebugEntry();
    const [fetchCollector] = useLazyGetCollectorInfoQuery();
    const [secondaryData, setSecondaryData] = useState<any>(null);

    const isFilesystemPrimary = primaryCollector === CollectorsMap.FilesystemStreamCollector;
    const secondaryCollector = isFilesystemPrimary
        ? CollectorsMap.HttpClientCollector
        : CollectorsMap.FilesystemStreamCollector;

    const collectors = useMemo(() => {
        if (!debugEntry) return new Set<string>();
        return new Set(debugEntry.collectors.map((c: any) => (typeof c === 'string' ? c : c.id)));
    }, [debugEntry]);

    useEffect(() => {
        if (!debugEntry) return;
        if (collectors.has(secondaryCollector)) {
            fetchCollector({id: debugEntry.id, collector: secondaryCollector}).then(({data, isError}) => {
                if (!isError && data) {
                    setSecondaryData(data);
                } else {
                    setSecondaryData(null);
                }
            });
        } else {
            setSecondaryData(null);
        }
    }, [debugEntry, collectors, secondaryCollector, fetchCollector]);

    const filesystem = isFilesystemPrimary ? primaryData : secondaryData;
    const http = isFilesystemPrimary ? secondaryData : primaryData;

    return <IOPanel filesystem={filesystem} http={http} />;
};

// ---------------------------------------------------------------------------
// Collector data renderer
// ---------------------------------------------------------------------------

type CollectorDataProps = {collectorData: any; selectedCollector: string};
function CollectorData({collectorData, selectedCollector}: CollectorDataProps) {
    const baseUrl = useSelector((state) => state.application.baseUrl) as string;
    const pages: {[name: string]: (data: any) => React.JSX.Element} = {
        [CollectorsMap.MailerCollector]: (data: any) => <MailerPanel data={data} />,
        [CollectorsMap.ServiceCollector]: (data: any) => <ServicesPanel data={data} />,
        [CollectorsMap.TimelineCollector]: (data: any) => <TimelinePanel data={data} />,
        [CollectorsMap.LogCollector]: (data: any) => <UnifiedLogWrapper logs={data} />,
        [CollectorsMap.DatabaseCollector]: (data: any) => <DatabasePanel data={data} />,
        [CollectorsMap.FilesystemStreamCollector]: (data: any) => (
            <IOWrapper primaryCollector={CollectorsMap.FilesystemStreamCollector} primaryData={data} />
        ),
        [CollectorsMap.HttpClientCollector]: (data: any) => (
            <IOWrapper primaryCollector={CollectorsMap.HttpClientCollector} primaryData={data} />
        ),
        [CollectorsMap.RequestCollector]: (data: any) => <RequestPanel data={data} />,
        [CollectorsMap.CommandCollector]: (data: any) => <CommandPanel data={data} />,
        [CollectorsMap.MiddlewareCollector]: (data: any) => <MiddlewarePanel {...data} />,
        [CollectorsMap.EventCollector]: (data: any) => <EventPanel events={data} />,
        [CollectorsMap.ExceptionCollector]: (data: any) => <ExceptionPanel exceptions={data} />,
        [CollectorsMap.DeprecationCollector]: (data: any) => (
            <UnifiedLogPanel logs={[]} deprecations={data} dumps={[]} />
        ),
        [CollectorsMap.VarDumperCollector]: (data: any) => <UnifiedLogPanel logs={[]} deprecations={[]} dumps={data} />,
        [CollectorsMap.CacheCollector]: (data: any) => <CachePanel data={data} />,
        [CollectorsMap.EnvironmentCollector]: (data: any) => <EnvironmentPanel data={data} />,
        [CollectorsMap.TemplateCollector]: (data: any) => <TemplatePanel data={data} />,
        [CollectorsMap.AuthorizationCollector]: (data: any) => <AuthorizationPanel data={data} />,
        [CollectorsMap.QueueCollector]: (data: any) => <QueuePanel data={data} />,
        [CollectorsMap.RouterCollector]: (data: any) => <RouterPanel data={data} />,
        [CollectorsMap.ValidatorCollector]: (data: any) => <ValidatorPanel data={data} />,
        [CollectorsMap.AssetBundleCollector]: (data: any) => <AssetBundlePanel data={data} />,
        [CollectorsMap.OpenTelemetryCollector]: (data: any) => <OpenTelemetryPanel data={data} />,
        [CollectorsMap.TranslatorCollector]: (data: any) => <TranslatorPanel data={data} />,
        [CollectorsMap.ElasticsearchCollector]: (data: any) => <ElasticsearchPanel data={data} />,
        [CollectorsMap.RedisCollector]: (data: any) => <RedisPanel data={data} />,
        [CollectorsMap.CodeCoverageCollector]: (data: any) => <CodeCoveragePanel data={data} />,
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
        icon={<DuckIcon />}
    />
));

// ---------------------------------------------------------------------------
// EvictedEntryState — shown when a collector fetch returns 404 because the
// debug entry has been evicted from the FIFO storage. Offers recovery actions
// (jump to the latest known entry or go to the full entry list).
// ---------------------------------------------------------------------------

export type EvictedEntryStateProps = {
    entryId: string;
    error: unknown;
    latestEntryId?: string;
    onOpenLatest: () => void;
    onViewAllEntries: () => void;
};

export const EvictedEntryState = ({
    entryId,
    error,
    latestEntryId,
    onOpenLatest,
    onViewAllEntries,
}: EvictedEntryStateProps) => {
    const hasLatest = Boolean(latestEntryId) && latestEntryId !== entryId;
    const serverMessage = extractErrorMessage(error);
    const description = hasLatest
        ? 'This debug entry was evicted from storage when newer requests arrived. Open a different entry to continue.'
        : 'This debug entry was evicted from storage and no other entries are available yet.';
    return (
        <EmptyState
            icon="archive"
            title="Debug entry is no longer available"
            severity="error"
            description={
                <>
                    {description}
                    <Box
                        component="span"
                        sx={{
                            display: 'block',
                            mt: 1,
                            fontSize: '12px',
                            color: 'text.disabled',
                            fontFamily: 'monospace',
                        }}
                    >
                        ID: {entryId}
                    </Box>
                    {serverMessage && (
                        <Box
                            component="span"
                            sx={{display: 'block', mt: 0.5, fontSize: '12px', color: 'text.disabled'}}
                        >
                            {serverMessage}
                        </Box>
                    )}
                </>
            }
            action={
                <Box sx={{display: 'flex', gap: 1, flexWrap: 'wrap', justifyContent: 'center'}}>
                    {hasLatest && (
                        <Button variant="contained" size="small" onClick={onOpenLatest}>
                            Open latest entry
                        </Button>
                    )}
                    <Button variant="outlined" size="small" onClick={onViewAllEntries}>
                        View all entries
                    </Button>
                </Box>
            }
        />
    );
};

// ---------------------------------------------------------------------------
// Debug Layout — collector data resolver (shell provided by main Layout)
// ---------------------------------------------------------------------------

const Layout = () => {
    const debugEntry = useDebugEntry();
    const [searchParams] = useSearchParams();
    const navigate = useNavigate();
    const {data: entriesList} = useGetDebugQuery();
    const [selectedCollector, setSelectedCollector] = useState<string>(() => searchParams.get('collector') || '');
    const [collectorData, setCollectorData] = useState<any>(undefined);
    const [entryMissingError, setEntryMissingError] = useState<unknown>(null);
    const [collectorInfo, collectorQueryInfo] = useLazyGetCollectorInfoQuery();

    const clearCollectorAndData = useCallback(() => {
        setSelectedCollector('');
        setCollectorData(null);
    }, []);

    useEffect(() => {
        const collector = searchParams.get('collector') || '';
        if (collector.trim() === '') {
            clearCollectorAndData();
            setEntryMissingError(null);
            return;
        }
        if (!debugEntry) {
            return;
        }
        // Resolve virtual EntryCollector to the real collector based on entry type
        let resolvedCollector = collector;
        if (collector === CollectorsMap.EntryCollector) {
            if (isDebugEntryAboutWeb(debugEntry)) {
                resolvedCollector = CollectorsMap.RequestCollector;
            } else if (isDebugEntryAboutConsole(debugEntry)) {
                resolvedCollector = CollectorsMap.CommandCollector;
            } else {
                clearCollectorAndData();
                setEntryMissingError(null);
                return;
            }
        }
        collectorInfo({id: debugEntry.id, collector: resolvedCollector})
            .then(({error, data, isError}) => {
                if (isError) {
                    // 404 means the entry (or at least this collector for it) has been
                    // dropped from the FIFO storage — show an actionable eviction state
                    // instead of silently blanking the panel. Any other error falls
                    // through to the generic HttpRequestError path.
                    if ((error as {status?: unknown} | undefined)?.status === 404) {
                        setEntryMissingError(error);
                        return;
                    }
                    clearCollectorAndData();
                    setEntryMissingError(null);
                    return;
                }
                setSelectedCollector(resolvedCollector);
                setCollectorData(data);
                setEntryMissingError(null);
            })
            .catch(() => {
                clearCollectorAndData();
                setEntryMissingError(null);
            });
    }, [searchParams, debugEntry, collectorInfo, clearCollectorAndData]);

    const dispatch = useDispatch();

    const latestEntry = useMemo(() => {
        if (!entriesList || entriesList.length === 0) return undefined;
        return entriesList[0];
    }, [entriesList]);

    const handleOpenLatest = useCallback(() => {
        if (!latestEntry) return;
        // Update Redux and URL in the same tick so Debug Layout's next effect
        // fires against the new id instead of the evicted one (avoids a
        // redundant 404 round-trip during the transition).
        dispatch(changeEntryAction(latestEntry));
        setEntryMissingError(null);
        const next = new URLSearchParams(searchParams);
        next.set('debugEntry', latestEntry.id);
        navigate({pathname: '/debug', search: next.toString()}, {replace: true});
    }, [dispatch, latestEntry, navigate, searchParams]);

    const handleAllEntries = useCallback(() => {
        navigate('/debug/list');
    }, [navigate]);

    if (!debugEntry) {
        return <Outlet />;
    }

    if (entryMissingError) {
        return (
            <EvictedEntryState
                entryId={debugEntry.id}
                error={entryMissingError}
                latestEntryId={latestEntry?.id}
                onOpenLatest={handleOpenLatest}
                onViewAllEntries={handleAllEntries}
            />
        );
    }

    if (debugEntry.collectors.length === 0) {
        return <EmptyCollectorsInfoBox />;
    }

    if (selectedCollector) {
        return (
            <>
                {collectorQueryInfo.isFetching && <LinearProgress />}
                {collectorQueryInfo.isError && (
                    <HttpRequestError
                        error={(collectorQueryInfo.error as any)?.error || (collectorQueryInfo.error as any)}
                    />
                )}
                {collectorQueryInfo.isSuccess && (
                    <ErrorBoundary FallbackComponent={ErrorFallback} resetKeys={[selectedCollector, debugEntry]}>
                        <CollectorData selectedCollector={selectedCollector} collectorData={collectorData} />
                    </ErrorBoundary>
                )}
            </>
        );
    }

    return <Outlet />;
};
export {Layout};
