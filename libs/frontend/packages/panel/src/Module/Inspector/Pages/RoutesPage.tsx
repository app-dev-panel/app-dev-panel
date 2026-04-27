import {ClassName} from '@app-dev-panel/panel/Application/Component/ClassName';
import {useGetRoutesQuery, useLazyGetCheckRouteQuery} from '@app-dev-panel/panel/Module/Inspector/API/Inspector';
import {EmptyState} from '@app-dev-panel/sdk/Component/EmptyState';
import {FilterChip} from '@app-dev-panel/sdk/Component/FilterChip';
import {FilterInput} from '@app-dev-panel/sdk/Component/FilterInput';
import {FullScreenCircularProgress} from '@app-dev-panel/sdk/Component/FullScreenCircularProgress';
import {PageHeader} from '@app-dev-panel/sdk/Component/PageHeader';
import {QueryErrorState} from '@app-dev-panel/sdk/Component/QueryErrorState';
import {SectionTitle} from '@app-dev-panel/sdk/Component/SectionTitle';
import {serializeCallable} from '@app-dev-panel/sdk/Helper/callableSerializer';
import {concatClassMethod} from '@app-dev-panel/sdk/Helper/classMethodConcater';
import {ContentCopy, OpenInNew} from '@mui/icons-material';
import {
    Alert,
    AlertTitle,
    Box,
    Chip,
    CircularProgress,
    Collapse,
    Icon,
    IconButton,
    InputBase,
    type Theme,
    Tooltip,
    Typography,
} from '@mui/material';
import {styled, useTheme} from '@mui/material/styles';
import clipboardCopy from 'clipboard-copy';
import React, {useCallback, useDeferredValue, useEffect, useMemo, useState} from 'react';
import {Link as RouterLink} from 'react-router';

// ---------------------------------------------------------------------------
// Types
// ---------------------------------------------------------------------------

type ActionType = {className: string; methodName: string} | null;

type RouteType = {id: string; name: string; pattern: string; method: string; middlewares: any[]; action: ActionType};

// ---------------------------------------------------------------------------
// Helpers
// ---------------------------------------------------------------------------

const methodColor = (method: string, theme: Theme): string => {
    switch (method.toUpperCase()) {
        case 'GET':
            return theme.palette.success.main;
        case 'POST':
            return theme.palette.primary.main;
        case 'PUT':
        case 'PATCH':
            return theme.palette.warning.main;
        case 'DELETE':
            return theme.palette.error.main;
        default:
            return theme.palette.text.disabled;
    }
};

function parseCallable(value: any): ActionType {
    if (Array.isArray(value) && value.length >= 2 && typeof value[0] === 'string' && typeof value[1] === 'string') {
        return {className: value[0], methodName: value[1]};
    }
    if (typeof value === 'string' && value.includes('::')) {
        const [className, methodName] = value.split('::', 2);
        if (className && methodName) {
            return {className, methodName};
        }
    }
    return null;
}

function collectGroupsAndRoutes(data: any): RouteType[] {
    const routes: RouteType[] = [];
    let i = 0;
    for (const route of data) {
        let action: ActionType = null;
        const middlewares: any[] = Array.isArray(route.middlewares) ? [...route.middlewares] : [];

        if (middlewares.length > 0) {
            const last = middlewares[middlewares.length - 1];
            const parsed = parseCallable(last);
            if (parsed) {
                action = parsed;
                middlewares.pop();
            }
        }

        for (const method of route.methods.filter((m: string) => !['OPTIONS', 'HEAD'].includes(m))) {
            routes.push({
                id: String(i++),
                name: route.name,
                pattern: route.pattern,
                method: method,
                middlewares: middlewares,
                action: action,
            });
        }
    }

    return routes.sort((a, b) => a.pattern.localeCompare(b.pattern));
}

// ---------------------------------------------------------------------------
// Styled components
// ---------------------------------------------------------------------------

const RouteRow = styled(Box, {shouldForwardProp: (p) => p !== 'expanded'})<{expanded?: boolean}>(
    ({theme, expanded}) => ({
        display: 'flex',
        alignItems: 'center',
        gap: theme.spacing(1.5),
        padding: theme.spacing(1, 1.5),
        borderBottom: `1px solid ${theme.palette.divider}`,
        transition: 'background-color 0.1s ease',
        backgroundColor: expanded ? theme.palette.action.hover : 'transparent',
        '&:hover': {backgroundColor: theme.palette.action.hover},
        [theme.breakpoints.down('sm')]: {gap: theme.spacing(0.75), padding: theme.spacing(0.75, 1)},
    }),
);

const PatternCell = styled(Typography)(({theme}) => ({
    fontFamily: theme.adp.fontFamilyMono,
    fontSize: '12px',
    flex: 1,
    minWidth: 0,
    overflow: 'hidden',
    textOverflow: 'ellipsis',
    whiteSpace: 'nowrap',
    [theme.breakpoints.down('sm')]: {fontSize: '11px'},
}));

const NameCell = styled(Typography)(({theme}) => ({
    fontSize: '11px',
    color: theme.palette.text.secondary,
    flexShrink: 0,
    maxWidth: 200,
    overflow: 'hidden',
    textOverflow: 'ellipsis',
    whiteSpace: 'nowrap',
    [theme.breakpoints.down('sm')]: {display: 'none'},
}));

const ActionInlineLink = styled('a')(({theme}) => ({
    fontFamily: theme.adp.fontFamilyMono,
    fontSize: '11px',
    color: theme.palette.primary.main,
    textDecoration: 'none',
    flexShrink: 1,
    minWidth: 0,
    maxWidth: 300,
    overflow: 'hidden',
    textOverflow: 'ellipsis',
    whiteSpace: 'nowrap',
    '&:hover': {textDecoration: 'underline'},
    [theme.breakpoints.down('sm')]: {maxWidth: '100%'},
}));

const DetailBox = styled(Box)(({theme}) => ({
    padding: theme.spacing(2, 2, 2, 6),
    backgroundColor: theme.palette.action.hover,
    borderBottom: `1px solid ${theme.palette.divider}`,
    fontSize: '12px',
}));

const CheckerBox = styled('form')(({theme}) => ({
    display: 'flex',
    alignItems: 'center',
    padding: theme.spacing(0.5, 1),
    marginBottom: theme.spacing(2),
    border: `1px solid ${theme.palette.divider}`,
    borderRadius: theme.shape.borderRadius,
    backgroundColor: theme.palette.background.paper,
    boxShadow: 'none',
}));

// ---------------------------------------------------------------------------
// Route detail (expanded)
// ---------------------------------------------------------------------------

function isClassName(value: string): boolean {
    return value.includes('\\') && !value.includes(' ');
}

const MiddlewareItem = ({mw}: {mw: any}) => {
    const parsed = parseCallable(mw);
    if (parsed) {
        return (
            <ClassName value={parsed.className} methodName={parsed.methodName}>
                <Typography
                    component="span"
                    sx={(theme) => ({
                        display: 'block',
                        fontFamily: theme.adp.fontFamilyMono,
                        fontSize: '12px',
                        color: 'primary.main',
                        py: 0.25,
                    })}
                >
                    {concatClassMethod(parsed.className, parsed.methodName)}
                </Typography>
            </ClassName>
        );
    }
    if (typeof mw === 'string' && isClassName(mw)) {
        return (
            <ClassName value={mw}>
                <Typography
                    component="span"
                    sx={(theme) => ({
                        display: 'block',
                        fontFamily: theme.adp.fontFamilyMono,
                        fontSize: '12px',
                        color: 'primary.main',
                        py: 0.25,
                    })}
                >
                    {mw}
                </Typography>
            </ClassName>
        );
    }
    return (
        <Typography
            sx={(theme) => ({
                fontFamily: theme.adp.fontFamilyMono,
                fontSize: '12px',
                color: 'text.secondary',
                py: 0.25,
            })}
        >
            {typeof mw === 'string' ? mw : JSON.stringify(mw)}
        </Typography>
    );
};

const RouteDetail = ({route}: {route: RouteType}) => {
    const {action} = route;
    const actionFull = action ? concatClassMethod(action.className, action.methodName) : null;

    return (
        <DetailBox>
            {action && actionFull && (
                <Box sx={{mb: route.middlewares.length > 0 ? 2 : 0}}>
                    <Typography
                        variant="caption"
                        sx={{fontWeight: 600, color: 'text.disabled', textTransform: 'uppercase', letterSpacing: 0.5}}
                    >
                        Action
                    </Typography>
                    <Box sx={{mt: 0.5, display: 'flex', alignItems: 'center', gap: 0.5}}>
                        <ClassName value={action.className} methodName={action.methodName}>
                            <Typography
                                component="span"
                                sx={(theme) => ({
                                    fontFamily: theme.adp.fontFamilyMono,
                                    fontSize: '12px',
                                    wordBreak: 'break-all',
                                    color: 'primary.main',
                                })}
                            >
                                {actionFull}
                            </Typography>
                        </ClassName>
                        <Tooltip title="Copy">
                            <IconButton
                                size="small"
                                onClick={(e) => {
                                    e.stopPropagation();
                                    clipboardCopy(actionFull);
                                }}
                            >
                                <ContentCopy sx={{fontSize: 14}} />
                            </IconButton>
                        </Tooltip>
                        <Tooltip title="Examine as a container entry">
                            <IconButton
                                size="small"
                                component={RouterLink}
                                to={'/inspector/container/view?class=' + action.className}
                                onClick={(e: React.MouseEvent) => e.stopPropagation()}
                            >
                                <OpenInNew sx={{fontSize: 14}} />
                            </IconButton>
                        </Tooltip>
                    </Box>
                </Box>
            )}

            {route.middlewares.length > 0 && (
                <Box>
                    <Typography
                        variant="caption"
                        sx={{fontWeight: 600, color: 'text.disabled', textTransform: 'uppercase', letterSpacing: 0.5}}
                    >
                        Middlewares ({route.middlewares.length})
                    </Typography>
                    <Box sx={{mt: 0.5}}>
                        {route.middlewares.map((mw, i) => (
                            <MiddlewareItem key={i} mw={mw} />
                        ))}
                    </Box>
                </Box>
            )}

            {!action && route.middlewares.length === 0 && (
                <Typography sx={{fontSize: '12px', color: 'text.disabled'}}>No additional details</Typography>
            )}
        </DetailBox>
    );
};

// ---------------------------------------------------------------------------
// Route checker (isolated state — typing doesn't re-render the route list)
// ---------------------------------------------------------------------------

const RouteChecker = () => {
    const [checkRouteQuery, checkRouteQueryInfo] = useLazyGetCheckRouteQuery();
    const [url, setUrl] = useState('');

    const onSubmitHandler = async (event: {preventDefault: () => void}) => {
        event.preventDefault();
        if (!url.trim()) return;
        await checkRouteQuery(url);
    };

    return (
        <>
            <CheckerBox onSubmit={onSubmitHandler}>
                <Tooltip title="Enter a path to check. Prefix with HTTP method (e.g. POST /login). Default is GET.">
                    <Icon sx={{fontSize: 18, color: 'text.disabled', mr: 1}}>travel_explore</Icon>
                </Tooltip>
                <InputBase
                    sx={{flex: 1, fontSize: '13px'}}
                    placeholder="Check route: /site/index, POST /auth/login, DELETE /user/1"
                    value={url}
                    onChange={(e) => setUrl(e.target.value)}
                />
                {checkRouteQueryInfo.isFetching ? (
                    <CircularProgress size={18} sx={{mx: 0.5}} />
                ) : (
                    <IconButton type="submit" size="small" disabled={!url.trim()}>
                        <Icon sx={{fontSize: 18}}>check</Icon>
                    </IconButton>
                )}
            </CheckerBox>

            {checkRouteQueryInfo.isError && (
                <Alert severity="error" sx={{mb: 2}}>
                    <AlertTitle>Failed to check route</AlertTitle>
                    {'status' in (checkRouteQueryInfo.error ?? {}) &&
                    typeof (checkRouteQueryInfo.error as any)?.data === 'object' &&
                    (checkRouteQueryInfo.error as any)?.data?.data?.error
                        ? (checkRouteQueryInfo.error as any).data.data.error
                        : 'An error occurred while checking the route.'}
                </Alert>
            )}

            {checkRouteQueryInfo.data && !checkRouteQueryInfo.isError && (
                <Alert severity={checkRouteQueryInfo.data.result ? 'success' : 'error'} sx={{mb: 2}} onClose={() => {}}>
                    {checkRouteQueryInfo.data.result ? (
                        <AlertTitle>
                            {(() => {
                                const action = checkRouteQueryInfo.data.action;
                                const parsed = parseCallable(action);
                                if (parsed) {
                                    return (
                                        <ClassName value={parsed.className} methodName={parsed.methodName}>
                                            <Typography
                                                component="span"
                                                sx={(theme) => ({
                                                    fontFamily: theme.adp.fontFamilyMono,
                                                    fontSize: '13px',
                                                    color: 'primary.main',
                                                })}
                                            >
                                                {concatClassMethod(parsed.className, parsed.methodName)}
                                            </Typography>
                                        </ClassName>
                                    );
                                }
                                if (typeof action === 'string' && isClassName(action)) {
                                    return (
                                        <ClassName value={action}>
                                            <Typography
                                                component="span"
                                                sx={(theme) => ({
                                                    fontFamily: theme.adp.fontFamilyMono,
                                                    fontSize: '13px',
                                                    color: 'primary.main',
                                                })}
                                            >
                                                {action}
                                            </Typography>
                                        </ClassName>
                                    );
                                }
                                return serializeCallable(action);
                            })()}
                        </AlertTitle>
                    ) : (
                        <AlertTitle>Route is invalid</AlertTitle>
                    )}
                </Alert>
            )}
        </>
    );
};

// ---------------------------------------------------------------------------
// Main component
// ---------------------------------------------------------------------------

export const RoutesPage = () => {
    const theme = useTheme();
    const {data, isLoading, isSuccess, isError, error, refetch} = useGetRoutesQuery();
    const [routes, setRoutes] = useState<RouteType[]>([]);
    const [filter, setFilter] = useState('');
    const deferredFilter = useDeferredValue(filter);
    const [activeFilters, setActiveFilters] = useState<Set<string>>(new Set());
    const [expandedId, setExpandedId] = useState<string | null>(null);

    useEffect(() => {
        if (!isSuccess) return;
        setRoutes(collectGroupsAndRoutes(data));
    }, [isSuccess, data]);

    const toggleFilter = useCallback((name: string) => {
        setActiveFilters((prev) => {
            const next = new Set(prev);
            if (next.has(name)) {
                next.delete(name);
            } else {
                next.add(name);
            }
            return next;
        });
    }, []);

    const badgeCounts = useMemo(() => {
        const counts = new Map<string, number>();
        for (const route of routes) {
            const method = route.method.toUpperCase();
            counts.set(method, (counts.get(method) ?? 0) + 1);
        }
        const order = ['GET', 'POST', 'PUT', 'PATCH', 'DELETE'];
        return [...counts.entries()].sort((a, b) => {
            const ai = order.indexOf(a[0]);
            const bi = order.indexOf(b[0]);
            return (ai === -1 ? 99 : ai) - (bi === -1 ? 99 : bi);
        });
    }, [routes]);

    const filtered = useMemo(() => {
        let result = routes;
        if (activeFilters.size > 0) {
            result = result.filter((r) => activeFilters.has(r.method.toUpperCase()));
        }
        if (deferredFilter) {
            const lower = deferredFilter.toLowerCase();
            result = result.filter(
                (r) =>
                    r.pattern.toLowerCase().includes(lower) ||
                    (r.name?.toLowerCase().includes(lower) ?? false) ||
                    r.method.toLowerCase().includes(lower),
            );
        }
        return result;
    }, [routes, deferredFilter, activeFilters]);

    if (isLoading) {
        return <FullScreenCircularProgress />;
    }

    if (isError) {
        return (
            <Box sx={{p: {xs: 1.5, sm: 3.5}}}>
                <PageHeader title="Routes" icon="alt_route" description="View and check application routes" />
                <QueryErrorState
                    error={error}
                    title="Failed to load routes"
                    fallback="Failed to load routes."
                    onRetry={refetch}
                />
            </Box>
        );
    }

    return (
        <>
            <Box sx={{px: {xs: 1.5, sm: 3.5}, pt: {xs: 1.5, sm: 3.5}, '& > div': {mb: 0}}}>
                <PageHeader title="Routes" icon="alt_route" description="View and check application routes" />
            </Box>

            <RouteChecker />

            {/* Routes list */}
            <Box sx={{p: {xs: 1.5, sm: 3.5}}}>
                {routes.length === 0 ? (
                    <EmptyState icon="alt_route" title="No routes found" />
                ) : (
                    <Box>
                    <SectionTitle
                        action={<FilterInput value={filter} onChange={setFilter} placeholder="Filter routes..." />}
                    >{`${filtered.length} routes`}</SectionTitle>

                    {/* Method filter badges */}
                    {badgeCounts.length > 1 && (
                        <Box sx={{display: 'flex', flexWrap: 'wrap', gap: 0.75, mb: 2}}>
                            {badgeCounts.map(([method, count]) => (
                                <FilterChip
                                    key={method}
                                    label={method}
                                    count={count}
                                    color={methodColor(method, theme)}
                                    active={activeFilters.has(method)}
                                    onClick={() => toggleFilter(method)}
                                />
                            ))}
                            {activeFilters.size > 0 && (
                                <FilterChip label="Clear" onClick={() => setActiveFilters(new Set())} />
                            )}
                        </Box>
                    )}

                    {/* Route rows */}
                    {filtered.map((route) => {
                        const expanded = expandedId === route.id;
                        const hasDetails = route.action !== null || (route.middlewares && route.middlewares.length > 0);
                        const actionShort = route.action
                            ? (route.action.className.split('\\').pop() as string) + '::' + route.action.methodName
                            : null;
                        const firstClassMw =
                            !route.action && route.middlewares?.length > 0
                                ? route.middlewares.find(
                                      (mw: any) => typeof mw === 'string' && mw.includes('\\') && !mw.includes(' '),
                                  )
                                : null;
                        const firstClassMwShort =
                            typeof firstClassMw === 'string' ? firstClassMw.split('\\').pop() : null;

                        return (
                            <Box key={route.id}>
                                <RouteRow
                                    expanded={expanded}
                                    onClick={hasDetails ? () => setExpandedId(expanded ? null : route.id) : undefined}
                                    sx={{cursor: hasDetails ? 'pointer' : 'default'}}
                                >
                                    <Chip
                                        label={route.method}
                                        size="small"
                                        sx={{
                                            fontWeight: 700,
                                            fontSize: '10px',
                                            height: 22,
                                            minWidth: 52,
                                            backgroundColor: methodColor(route.method, theme),
                                            color: 'common.white',
                                            borderRadius: 1,
                                        }}
                                    />
                                    <PatternCell>{route.pattern}</PatternCell>
                                    {actionShort && route.action && (
                                        <ClassName value={route.action.className} methodName={route.action.methodName}>
                                            <ActionInlineLink as="span">{actionShort}</ActionInlineLink>
                                        </ClassName>
                                    )}
                                    {firstClassMwShort && typeof firstClassMw === 'string' && (
                                        <ClassName value={firstClassMw}>
                                            <ActionInlineLink as="span">{firstClassMwShort}</ActionInlineLink>
                                        </ClassName>
                                    )}
                                    {route.name && <NameCell>{route.name}</NameCell>}
                                    {hasDetails && (
                                        <IconButton size="small" sx={{flexShrink: 0}}>
                                            <Icon sx={{fontSize: 16}}>{expanded ? 'expand_less' : 'expand_more'}</Icon>
                                        </IconButton>
                                    )}
                                </RouteRow>
                                {hasDetails && (
                                    <Collapse in={expanded}>
                                        <RouteDetail route={route} />
                                    </Collapse>
                                )}
                            </Box>
                        );
                    })}
                </Box>
                )}
            </Box>
        </>
    );
};
