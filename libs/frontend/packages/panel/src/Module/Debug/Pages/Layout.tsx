import {useBreadcrumbs} from '@app-dev-panel/panel/Application/Context/BreadcrumbsContext';
import ModuleLoader from '@app-dev-panel/panel/Application/Pages/RemoteComponent';
import {AssetBundlePanel} from '@app-dev-panel/panel/Module/Debug/Component/Panel/AssetBundlePanel';
import {CachePanel} from '@app-dev-panel/panel/Module/Debug/Component/Panel/CachePanel';
import {DatabasePanel} from '@app-dev-panel/panel/Module/Debug/Component/Panel/DatabasePanel';
import {EnvironmentPanel} from '@app-dev-panel/panel/Module/Debug/Component/Panel/EnvironmentPanel';
import {EventPanel} from '@app-dev-panel/panel/Module/Debug/Component/Panel/EventPanel';
import {ExceptionPanel} from '@app-dev-panel/panel/Module/Debug/Component/Panel/ExceptionPanel';
import {FilesystemPanel} from '@app-dev-panel/panel/Module/Debug/Component/Panel/FilesystemPanel';
import {HttpClientPanel} from '@app-dev-panel/panel/Module/Debug/Component/Panel/HttpClientPanel';
import {LogPanel} from '@app-dev-panel/panel/Module/Debug/Component/Panel/LogPanel';
import {MailerPanel} from '@app-dev-panel/panel/Module/Debug/Component/Panel/MailerPanel';
import {MessengerPanel} from '@app-dev-panel/panel/Module/Debug/Component/Panel/MessengerPanel';
import {MiddlewarePanel} from '@app-dev-panel/panel/Module/Debug/Component/Panel/MiddlewarePanel';
import {QueuePanel} from '@app-dev-panel/panel/Module/Debug/Component/Panel/QueuePanel';
import {RequestPanel} from '@app-dev-panel/panel/Module/Debug/Component/Panel/RequestPanel';
import {RouterPanel} from '@app-dev-panel/panel/Module/Debug/Component/Panel/RouterPanel';
import {SecurityPanel} from '@app-dev-panel/panel/Module/Debug/Component/Panel/SecurityPanel';
import {ServicesPanel} from '@app-dev-panel/panel/Module/Debug/Component/Panel/ServicesPanel';
import {TimelinePanel} from '@app-dev-panel/panel/Module/Debug/Component/Panel/TimelinePanel';
import {TwigPanel} from '@app-dev-panel/panel/Module/Debug/Component/Panel/TwigPanel';
import {ValidatorPanel} from '@app-dev-panel/panel/Module/Debug/Component/Panel/ValidatorPanel';
import {VarDumperPanel} from '@app-dev-panel/panel/Module/Debug/Component/Panel/VarDumperPanel';
import {WebViewPanel} from '@app-dev-panel/panel/Module/Debug/Component/Panel/WebViewPanel';
import {DumpPage} from '@app-dev-panel/panel/Module/Debug/Pages/DumpPage';
import {useSelector} from '@app-dev-panel/panel/store';
import {useDebugEntry} from '@app-dev-panel/sdk/API/Debug/Context';
import {useLazyGetCollectorInfoQuery} from '@app-dev-panel/sdk/API/Debug/Debug';
import {ErrorFallback} from '@app-dev-panel/sdk/Component/ErrorFallback';
import {InfoBox} from '@app-dev-panel/sdk/Component/InfoBox';
import {CollectorsMap} from '@app-dev-panel/sdk/Helper/collectors';
import {HelpOutline} from '@mui/icons-material';
import {Alert, AlertTitle, Box, LinearProgress} from '@mui/material';
import * as React from 'react';
import {useCallback, useEffect, useMemo, useState} from 'react';
import {ErrorBoundary} from 'react-error-boundary';
import {Outlet} from 'react-router';
import {useSearchParams} from 'react-router-dom';

// ---------------------------------------------------------------------------
// Collector data renderer
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
        [CollectorsMap.HttpClientCollector]: (data: any) => <HttpClientPanel data={data} />,
        [CollectorsMap.RequestCollector]: (data: any) => <RequestPanel data={data} />,
        [CollectorsMap.MiddlewareCollector]: (data: any) => <MiddlewarePanel {...data} />,
        [CollectorsMap.EventCollector]: (data: any) => <EventPanel events={data} />,
        [CollectorsMap.ExceptionCollector]: (data: any) => <ExceptionPanel exceptions={data} />,
        [CollectorsMap.VarDumperCollector]: (data: any) => <VarDumperPanel data={data} />,
        [CollectorsMap.CacheCollector]: (data: any) => <CachePanel data={data} />,
        [CollectorsMap.EnvironmentCollector]: (data: any) => <EnvironmentPanel data={data} />,
        [CollectorsMap.TemplateCollector]: (data: any) => <TwigPanel data={data} />,
        [CollectorsMap.SecurityCollector]: (data: any) => <SecurityPanel data={data} />,
        [CollectorsMap.MessageBusCollector]: (data: any) => <MessengerPanel data={data} />,
        [CollectorsMap.QueueCollector]: (data: any) => <QueuePanel data={data} />,
        [CollectorsMap.RouterCollector]: (data: any) => <RouterPanel data={data} />,
        [CollectorsMap.ValidatorCollector]: (data: any) => <ValidatorPanel data={data} />,
        [CollectorsMap.ViewCollector]: (data: any) => <WebViewPanel data={data} />,
        [CollectorsMap.AssetBundleCollector]: (data: any) => <AssetBundlePanel data={data} />,
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
// Debug Layout — collector data resolver (shell provided by main Layout)
// ---------------------------------------------------------------------------

const Layout = () => {
    const debugEntry = useDebugEntry();
    const [searchParams] = useSearchParams();
    const [selectedCollector, setSelectedCollector] = useState<string>(() => searchParams.get('collector') || '');
    const [collectorData, setCollectorData] = useState<any>(undefined);
    const [collectorInfo, collectorQueryInfo] = useLazyGetCollectorInfoQuery();

    const clearCollectorAndData = useCallback(() => {
        setSelectedCollector('');
        setCollectorData(null);
    }, []);

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
                    return;
                }
                setSelectedCollector(collector);
                setCollectorData(data);
            })
            .catch(clearCollectorAndData);
    }, [searchParams, debugEntry, collectorInfo, clearCollectorAndData]);

    const collectorName = useMemo(() => selectedCollector.split('\\').pop(), [selectedCollector]);
    useBreadcrumbs(() => ['Debug', collectorName]);

    if (!debugEntry) {
        return <Outlet />;
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
