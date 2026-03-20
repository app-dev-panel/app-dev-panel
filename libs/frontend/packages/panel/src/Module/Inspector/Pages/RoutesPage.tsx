import {useBreadcrumbs} from '@app-dev-panel/panel/Application/Context/BreadcrumbsContext';
import {useGetRoutesQuery, useLazyGetCheckRouteQuery} from '@app-dev-panel/panel/Module/Inspector/API/Inspector';
import {EmptyState} from '@app-dev-panel/sdk/Component/EmptyState';
import {FilterInput} from '@app-dev-panel/sdk/Component/FilterInput';
import {FullScreenCircularProgress} from '@app-dev-panel/sdk/Component/FullScreenCircularProgress';
import {PageHeader} from '@app-dev-panel/sdk/Component/PageHeader';
import {SectionTitle} from '@app-dev-panel/sdk/Component/SectionTitle';
import {primitives} from '@app-dev-panel/sdk/Component/Theme/tokens';
import {serializeCallable} from '@app-dev-panel/sdk/Helper/callableSerializer';
import {concatClassMethod} from '@app-dev-panel/sdk/Helper/classMethodConcater';
import {ContentCopy, OpenInNew} from '@mui/icons-material';
import {
    Alert,
    AlertTitle,
    Box,
    Chip,
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
import {useCallback, useDeferredValue, useEffect, useMemo, useState} from 'react';

// ---------------------------------------------------------------------------
// Types
// ---------------------------------------------------------------------------

type RouteType = {
    id: string;
    name: string;
    pattern: string;
    method: string;
    middlewares: any[];
    action: any[] | undefined;
};

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

function isClassCallable(value: any): value is [string, string] {
    return Array.isArray(value) && value.length >= 2 && typeof value[0] === 'string' && typeof value[1] === 'string';
}

function collectGroupsAndRoutes(data: any): RouteType[] {
    const routes: RouteType[] = [];
    let i = 0;
    for (const route of data) {
        let action: any[] | undefined = undefined;
        const middlewares: any[] = Array.isArray(route.middlewares) ? [...route.middlewares] : [];

        if (middlewares.length > 0) {
            const last = middlewares[middlewares.length - 1];
            if (isClassCallable(last)) {
                action = [last[0], last[1]];
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
    }),
);

const PatternCell = styled(Typography)({
    fontFamily: primitives.fontFamilyMono,
    fontSize: '12px',
    flex: 1,
    wordBreak: 'break-all',
    minWidth: 0,
});

const NameCell = styled(Typography)(({theme}) => ({
    fontSize: '11px',
    color: theme.palette.text.secondary,
    flexShrink: 0,
    maxWidth: 200,
    overflow: 'hidden',
    textOverflow: 'ellipsis',
    whiteSpace: 'nowrap',
}));

const ActionInlineCell = styled(Typography)(({theme}) => ({
    fontFamily: primitives.fontFamilyMono,
    fontSize: '11px',
    color: theme.palette.text.secondary,
    flexShrink: 0,
    maxWidth: 300,
    overflow: 'hidden',
    textOverflow: 'ellipsis',
    whiteSpace: 'nowrap',
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

const RouteDetail = ({route}: {route: RouteType}) => {
    const actionFull = route.action ? concatClassMethod(route.action[0] as string, route.action[1] as string) : null;

    return (
        <DetailBox>
            {actionFull && (
                <Box sx={{mb: route.middlewares.length > 0 ? 2 : 0}}>
                    <Typography
                        variant="caption"
                        sx={{fontWeight: 600, color: 'text.disabled', textTransform: 'uppercase', letterSpacing: 0.5}}
                    >
                        Action
                    </Typography>
                    <Box sx={{mt: 0.5, display: 'flex', alignItems: 'center', gap: 0.5}}>
                        <Typography
                            sx={{fontFamily: primitives.fontFamilyMono, fontSize: '12px', wordBreak: 'break-all'}}
                        >
                            {actionFull}
                        </Typography>
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
                                href={'/inspector/container/view?class=' + (route.action![0] as string)}
                                onClick={(e) => e.stopPropagation()}
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
                            <Typography
                                key={i}
                                sx={{
                                    fontFamily: primitives.fontFamilyMono,
                                    fontSize: '12px',
                                    color: 'text.secondary',
                                    py: 0.25,
                                }}
                            >
                                {isClassCallable(mw)
                                    ? serializeCallable(mw)
                                    : typeof mw === 'string'
                                      ? mw
                                      : JSON.stringify(mw)}
                            </Typography>
                        ))}
                    </Box>
                </Box>
            )}

            {!actionFull && route.middlewares.length === 0 && (
                <Typography sx={{fontSize: '12px', color: 'text.disabled'}}>No additional details</Typography>
            )}
        </DetailBox>
    );
};

// ---------------------------------------------------------------------------
// Main component
// ---------------------------------------------------------------------------

export const RoutesPage = () => {
    const theme = useTheme();
    const {data, isLoading, isSuccess} = useGetRoutesQuery();
    const [checkRouteQuery, checkRouteQueryInfo] = useLazyGetCheckRouteQuery();
    const [routes, setRoutes] = useState<RouteType[]>([]);
    const [url, setUrl] = useState('');
    const [filter, setFilter] = useState('');
    const deferredFilter = useDeferredValue(filter);
    const [activeFilters, setActiveFilters] = useState<Set<string>>(new Set());
    const [expandedId, setExpandedId] = useState<string | null>(null);

    useEffect(() => {
        if (!isSuccess) return;
        setRoutes(collectGroupsAndRoutes(data));
    }, [isSuccess, data]);

    const onSubmitHandler = async (event: {preventDefault: () => void}) => {
        event.preventDefault();
        if (!url.trim()) return;
        await checkRouteQuery(url);
    };

    useBreadcrumbs(() => ['Inspector', 'Routes']);

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

    return (
        <>
            <PageHeader title="Routes" icon="alt_route" description="View and check application routes" />

            {/* Route checker — compact inline form */}
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
                <IconButton type="submit" size="small" disabled={!url.trim()}>
                    <Icon sx={{fontSize: 18}}>check</Icon>
                </IconButton>
            </CheckerBox>

            {checkRouteQueryInfo.data && (
                <Alert severity={checkRouteQueryInfo.data.result ? 'success' : 'error'} sx={{mb: 2}} onClose={() => {}}>
                    {checkRouteQueryInfo.data.result ? (
                        <AlertTitle>{serializeCallable(checkRouteQueryInfo.data.action)}</AlertTitle>
                    ) : (
                        <AlertTitle>Route is invalid</AlertTitle>
                    )}
                </Alert>
            )}

            {/* Routes list */}
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
                            {badgeCounts.map(([method, count]) => {
                                const isActive = activeFilters.has(method);
                                const color = methodColor(method, theme);
                                return (
                                    <Chip
                                        key={method}
                                        label={`${method} (${count})`}
                                        size="small"
                                        onClick={() => toggleFilter(method)}
                                        sx={{
                                            fontSize: '11px',
                                            height: 24,
                                            borderRadius: 1,
                                            fontWeight: 600,
                                            cursor: 'pointer',
                                            backgroundColor: isActive ? color : 'transparent',
                                            color: isActive ? 'common.white' : color,
                                            border: `1px solid ${color}`,
                                        }}
                                    />
                                );
                            })}
                            {activeFilters.size > 0 && (
                                <Chip
                                    label="Clear"
                                    size="small"
                                    onClick={() => setActiveFilters(new Set())}
                                    variant="outlined"
                                    sx={{fontSize: '11px', height: 24, borderRadius: 1}}
                                />
                            )}
                        </Box>
                    )}

                    {/* Route rows */}
                    {filtered.map((route) => {
                        const expanded = expandedId === route.id;
                        const hasDetails =
                            route.action !== undefined || (route.middlewares && route.middlewares.length > 0);
                        const actionShort =
                            route.action && isClassCallable(route.action)
                                ? concatClassMethod(
                                      (route.action[0] as string).split('\\').pop() as string,
                                      route.action[1] as string,
                                  )
                                : null;

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
                                    {actionShort && <ActionInlineCell>{actionShort}</ActionInlineCell>}
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
        </>
    );
};
