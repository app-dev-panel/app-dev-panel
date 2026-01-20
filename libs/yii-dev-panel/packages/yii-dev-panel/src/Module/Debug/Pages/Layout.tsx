import {ReactJSXElement} from '@emotion/react/types/jsx-namespace';
import {Check, EmojiObjects, Error, HelpOutline, Refresh} from '@mui/icons-material';
import InboxIcon from '@mui/icons-material/Inbox';
import ListIcon from '@mui/icons-material/List';
import MailIcon from '@mui/icons-material/Mail';
import ReplayIcon from '@mui/icons-material/Replay';
import {
    Alert,
    AlertTitle,
    Autocomplete,
    Box,
    Button,
    Chip,
    CircularProgress,
    LinearProgress,
    Link,
    Stack,
    TextField,
    Tooltip,
    Typography,
} from '@mui/material';
import FormControlLabel from '@mui/material/FormControlLabel';
import Switch from '@mui/material/Switch';
import {changeAutoLatest} from '@yiisoft/yii-dev-panel-sdk/API/Application/ApplicationContext';
import {changeEntryAction, useDebugEntry} from '@yiisoft/yii-dev-panel-sdk/API/Debug/Context';
import {
    DebugEntry,
    useLazyGetCollectorInfoQuery,
    useLazyGetDebugQuery,
} from '@yiisoft/yii-dev-panel-sdk/API/Debug/Debug';
import {ErrorFallback} from '@yiisoft/yii-dev-panel-sdk/Component/ErrorFallback';
import {FullScreenCircularProgress} from '@yiisoft/yii-dev-panel-sdk/Component/FullScreenCircularProgress';
import {InfoBox} from '@yiisoft/yii-dev-panel-sdk/Component/InfoBox';
import {LinkProps, MenuPanel} from '@yiisoft/yii-dev-panel-sdk/Component/MenuPanel';
import {EventTypesEnum, useServerSentEvents} from '@yiisoft/yii-dev-panel-sdk/Component/useServerSentEvents';
import {buttonColorHttp} from '@yiisoft/yii-dev-panel-sdk/Helper/buttonColor';
import {CollectorsMap} from '@yiisoft/yii-dev-panel-sdk/Helper/collectors';
import {getCollectedCountByCollector} from '@yiisoft/yii-dev-panel-sdk/Helper/collectorsTotal';
import {isDebugEntryAboutConsole, isDebugEntryAboutWeb} from '@yiisoft/yii-dev-panel-sdk/Helper/debugEntry';
import {formatDate} from '@yiisoft/yii-dev-panel-sdk/Helper/formatDate';
import ModuleLoader from '@yiisoft/yii-dev-panel/Application/Pages/RemoteComponent';
import {DatabasePanel} from '@yiisoft/yii-dev-panel/Module/Debug/Component/Panel/DatabasePanel';
import {EventPanel} from '@yiisoft/yii-dev-panel/Module/Debug/Component/Panel/EventPanel';
import {ExceptionPanel} from '@yiisoft/yii-dev-panel/Module/Debug/Component/Panel/ExceptionPanel';
import {FilesystemPanel} from '@yiisoft/yii-dev-panel/Module/Debug/Component/Panel/FilesystemPanel';
import {LogPanel} from '@yiisoft/yii-dev-panel/Module/Debug/Component/Panel/LogPanel';
import {MailerPanel} from '@yiisoft/yii-dev-panel/Module/Debug/Component/Panel/MailerPanel';
import {MiddlewarePanel} from '@yiisoft/yii-dev-panel/Module/Debug/Component/Panel/MiddlewarePanel';
import {RequestPanel} from '@yiisoft/yii-dev-panel/Module/Debug/Component/Panel/RequestPanel';
import {ServicesPanel} from '@yiisoft/yii-dev-panel/Module/Debug/Component/Panel/ServicesPanel';
import {TimelinePanel} from '@yiisoft/yii-dev-panel/Module/Debug/Component/Panel/TimelinePanel';
import {VarDumperPanel} from '@yiisoft/yii-dev-panel/Module/Debug/Component/Panel/VarDumperPanel';
import {DumpPage} from '@yiisoft/yii-dev-panel/Module/Debug/Pages/DumpPage';
import {useDoRequestMutation, usePostCurlBuildMutation} from '@yiisoft/yii-dev-panel/Module/Inspector/API/Inspector';
import {useSelector} from '@yiisoft/yii-dev-panel/store';
import clipboardCopy from 'clipboard-copy';
import * as React from 'react';
import {HTMLAttributes, useCallback, useEffect, useMemo, useState} from 'react';
import {ErrorBoundary} from 'react-error-boundary';
import {useDispatch} from 'react-redux';
import {Outlet} from 'react-router';
import {useSearchParams} from 'react-router-dom';
import {useBreadcrumbs} from '@yiisoft/yii-dev-panel/Application/Context/BreadcrumbsContext';

function parseCollectorName(text: string) {
    return text
        .replace('Yiisoft\\Assets\\Debug\\', '')
        .replace('Yiisoft\\Db\\Debug\\', '')
        .replace('Yiisoft\\Mailer\\Debug\\', '')
        .replace('Yiisoft\\Validator\\Debug\\', '')
        .replace('Yiisoft\\Yii\\View\\Debug\\', '')
        .replace('Yiisoft\\Yii\\Queue\\Debug\\', '')
        .replace('Yiisoft\\Yii\\Debug\\Collector\\Web\\', '')
        .replace('Yiisoft\\Yii\\Debug\\Collector\\Console\\', '')
        .replace('Yiisoft\\Yii\\Debug\\Collector\\Database\\', '')
        .replace('Yiisoft\\Yii\\Debug\\Collector\\Queue\\', '')
        .replace('Yiisoft\\Yii\\Debug\\Collector\\Stream\\', '')
        .replace('Yiisoft\\Yii\\Debug\\Collector\\', '')
        .replace('Yiisoft\\Yii\\View\\Renderer\\Debug\\', '');
}

type CollectorDataProps = {
    collectorData: any;
    selectedCollector: string;
};
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
                        return <Box dangerouslySetInnerHTML={{__html: data}} />;
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

type DebugEntryAutocompleteProps = {
    data: DebugEntry[] | undefined;
    onChange: (data: DebugEntry | null) => void;
};
const DebugEntryAutocomplete = ({data, onChange}: DebugEntryAutocompleteProps) => {
    const debugEntry = useDebugEntry();

    const renderLabel = useCallback((entry: DebugEntry): string => {
        if (isDebugEntryAboutConsole(entry)) {
            return [entry.command?.exitCode === 0 ? '[OK]' : '[ERROR]', entry.command?.input].filter(Boolean).join(' ');
        }
        if (isDebugEntryAboutWeb(entry)) {
            return ['[' + entry.response.statusCode + ']', entry.request.method, entry.request.path].join(' ');
        }
        return entry.id;
    }, []);
    const renderOptions = useCallback(
        (props: HTMLAttributes<HTMLElement>, entry: DebugEntry): ReactJSXElement => (
            <Stack
                {...props}
                key={entry.id}
                component="li"
                direction="row"
                spacing={2}
                sx={{'& > img': {mr: 2, flexShrink: 0}}}
            >
                {isDebugEntryAboutWeb(entry) && (
                    <>
                        <Typography component="span" sx={{flex: 1}}>
                            <Chip
                                sx={{borderRadius: '5px 5px', margin: '0 2px'}}
                                label={`${entry.response?.statusCode} ${entry.request.method}`}
                                color={buttonColorHttp(entry.response?.statusCode)}
                            />
                            <span style={{margin: '0 2px'}}>{entry.request.path}</span>
                        </Typography>
                        <Typography component="span" sx={{margin: '0 auto'}}>
                            <span>{formatDate(entry.web.request.startTime)}</span>
                        </Typography>
                    </>
                )}
                {isDebugEntryAboutConsole(entry) && (
                    <>
                        <Typography component="span" sx={{flex: 1}}>
                            {entry.command?.exitCode === 0 ? (
                                <Chip label="OK" color={'success'} sx={{borderRadius: '5px 5px', margin: '0 2px'}} />
                            ) : (
                                <Chip
                                    label={`CODE: ${entry.command?.exitCode ?? 'Unknown'}`}
                                    color={'error'}
                                    sx={{borderRadius: '5px 5px', margin: '0 2px'}}
                                />
                            )}
                            <span style={{margin: '0 2px'}}>{entry.command?.input ?? 'Unknown'}</span>
                        </Typography>
                        <Typography component="span" sx={{margin: '0 auto'}}>
                            <span>{formatDate(entry.console.request.startTime)}</span>
                        </Typography>
                    </>
                )}
            </Stack>
        ),
        [],
    );

    return (
        <Autocomplete
            value={debugEntry}
            options={(data || []) as DebugEntry[]}
            getOptionLabel={renderLabel}
            renderOption={renderOptions}
            isOptionEqualToValue={(option, value) => option.id === value.id}
            renderInput={(params) => <TextField {...params} label="Record" />}
            onChange={(event, value) => {
                if (typeof value === 'object') {
                    onChange(value);
                } else {
                    onChange(null);
                }
            }}
            sx={{my: 1}}
        />
    );
};

const NoCollectorsInfoBox = React.memo(() => {
    return (
        <InfoBox
            title="No one collector is chosen"
            text="Select a collector from the left side panel to see more options"
            severity="info"
            icon={<HelpOutline />}
        />
    );
});

const EmptyCollectorsInfoBox = React.memo(() => (
    <InfoBox
        title="Collectors are empty"
        text="Looks like debugger was inactive or it did not have any active collectors during the request"
        severity="info"
        icon={<HelpOutline />}
    />
));

const weights = {
    [CollectorsMap.RequestCollector]: 1,
    [CollectorsMap.LogCollector]: 2,
    [CollectorsMap.DatabaseCollector]: 3,
    [CollectorsMap.EventCollector]: 4,
    [CollectorsMap.TimelineCollector]: 5,
    [CollectorsMap.VarDumperCollector]: 6,
};

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

    const changeEntry = (entry: DebugEntry | null) => {
        if (entry) {
            dispatch(changeEntryAction(entry));
            return;
        }
        dispatch(changeEntryAction(null));
    };
    const collectorName = useMemo(() => selectedCollector.split('\\').pop(), [selectedCollector]);

    const links: LinkProps[] = useMemo(
        () =>
            !debugEntry
                ? []
                : debugEntry.collectors
                      .map((collector, index) => ({
                          name: collector,
                          text: parseCollectorName(collector),
                          href: `/debug?collector=${collector}&debugEntry=${debugEntry.id}`,
                          icon: index % 2 === 0 ? <InboxIcon /> : <MailIcon />,
                          badge: getCollectedCountByCollector(collector as CollectorsMap, debugEntry),
                      }))
                      .sort((a, b) => {
                          const weightA = weights[a.name] ?? null;
                          const weightB = weights[b.name] ?? null;

                          if (weightA !== null && weightB !== null) {
                              return weightA - weightB;
                          }
                          if (weightA !== null) {
                              return -1;
                          }
                          if (weightB !== null) {
                              return 1;
                          }
                          return a.name.localeCompare(b.name);
                      }),
        [debugEntry],
    );

    const [doRequest, doRequestInfo] = useDoRequestMutation();
    const repeatRequestHandler = useCallback(async () => {
        if (!debugEntry) {
            return;
        }
        try {
            await doRequest({id: debugEntry.id});
        } catch (e) {
            console.error(e);
        }
        getDebugQuery();
    }, [debugEntry]);
    const copyCurlHandler = useCallback(async () => {
        if (!debugEntry) {
            return;
        }
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
        const data = JSON.parse(event.data);
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
                            Check the "yiisoft/yii-debug" in the "params.php" on the backend or with{' '}
                            <Link href="/inspector/config/parameters?filter=yiisoft/yii-debug">Inspector</Link>.
                        </Typography>
                        <Typography>
                            See more information on the link{' '}
                            <Link href="https://github.com/yiisoft/yii-debug">
                                https://github.com/yiisoft/yii-debug
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
            <DebugEntryAutocomplete data={getDebugQueryInfo.data} onChange={onEntryChangeHandler} />
            <Stack direction="row" spacing={1}>
                <Tooltip title="List">
                    <span>
                        <Button href="/debug/list" startIcon={<ListIcon />}>
                            List
                        </Button>
                    </span>
                </Tooltip>
                <Tooltip title="Refresh the list">
                    <span>
                        <Button
                            onClick={onRefreshHandler}
                            disabled={getDebugQueryInfo.isFetching}
                            startIcon={<Refresh />}
                            endIcon={getDebugQueryInfo.isFetching ? <CircularProgress size={24} color="info" /> : null}
                        >
                            Refresh
                        </Button>
                    </span>
                </Tooltip>
                <Tooltip title="Runs the request again">
                    <span>
                        <Button
                            onClick={repeatRequestHandler}
                            disabled={!debugEntry || doRequestInfo.isLoading || getDebugQueryInfo.isFetching}
                            startIcon={<ReplayIcon />}
                            endIcon={
                                doRequestInfo.isLoading ? (
                                    <CircularProgress size={24} color="info" />
                                ) : doRequestInfo.isUninitialized ? null : doRequestInfo.isSuccess ? (
                                    <Check color="success" />
                                ) : (
                                    <Error color="error" />
                                )
                            }
                        >
                            Repeat Request
                        </Button>
                    </span>
                </Tooltip>
                {debugEntry && isDebugEntryAboutWeb(debugEntry) && (
                    <Tooltip title="Copies the request cURL interpretation">
                        <span>
                            <Button
                                onClick={copyCurlHandler}
                                disabled={!debugEntry || postCurlBuildQueryInfo.isLoading}
                                endIcon={
                                    postCurlBuildQueryInfo.isLoading ? (
                                        <CircularProgress size={24} color="info" />
                                    ) : postCurlBuildQueryInfo.isUninitialized ? null : postCurlBuildQueryInfo.isSuccess ? (
                                        <Check color="success" />
                                    ) : (
                                        <Error color="error" />
                                    )
                                }
                            >
                                Copy cURL
                            </Button>
                        </span>
                    </Tooltip>
                )}
                {/*TODO add documentation and description how it work and how add it to php-fpm / road-runner*/}
                <Tooltip title="Switches to the latest debug entry automatically (delay 1s). Needs server-sent events suppport.">
                    <span>
                        <FormControlLabel
                            control={<Switch checked={autoLatest} value={autoLatest} onChange={autoLatestHandler} />}
                            label="Latest auto"
                        />
                    </span>
                </Tooltip>
            </Stack>

            {links.length === 0 ? (
                <EmptyCollectorsInfoBox />
            ) : (
                <MenuPanel links={links} open={!selectedCollector} activeLink={selectedCollector}>
                    {selectedCollector ? (
                        <>
                            {collectorQueryInfo.isFetching && <LinearProgress />}
                            {collectorQueryInfo.isError && (
                                <HttpRequestError
                                    error={
                                        (collectorQueryInfo.error as any)?.error || (collectorQueryInfo.error as any)
                                    }
                                />
                            )}
                            {collectorQueryInfo.isSuccess && (
                                <ErrorBoundary
                                    FallbackComponent={ErrorFallback}
                                    resetKeys={[window.location.pathname, window.location.search, debugEntry]}
                                >
                                    <CollectorData
                                        selectedCollector={selectedCollector}
                                        collectorData={collectorData}
                                    />
                                </ErrorBoundary>
                            )}
                        </>
                    ) : (
                        <NoCollectorsInfoBox />
                    )}
                </MenuPanel>
            )}
        </>
    );
};
export {Layout};
