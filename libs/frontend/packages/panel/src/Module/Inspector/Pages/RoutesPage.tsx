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
import CheckIcon from '@mui/icons-material/Check';
import {
    Alert,
    AlertTitle,
    Box,
    Chip,
    Collapse,
    FormHelperText,
    Icon,
    IconButton,
    InputBase,
    Paper,
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

function extractAction(middlewares: any[]): {action: any[] | undefined; actualMiddlewares: any[]} {
    if (!Array.isArray(middlewares) || middlewares.length === 0) {
        return {action: undefined, actualMiddlewares: []};
    }

    if (middlewares.length === 1) {
        const single = middlewares[0];
        if (Array.isArray(single) && single.length >= 2) {
            return {action: [single[0], single[1]], actualMiddlewares: []};
        }
        if (typeof single === 'string') {
            return {action: undefined, actualMiddlewares: []};
        }
        return {action: undefined, actualMiddlewares: middlewares};
    }

    const lastMiddleware = middlewares.at(-1);
    if (Array.isArray(lastMiddleware) && lastMiddleware.length >= 2) {
        return {action: [lastMiddleware[0], lastMiddleware[1]], actualMiddlewares: middlewares.slice(0, -1)};
    }
    return {action: undefined, actualMiddlewares: middlewares};
}

function collectGroupsAndRoutes(data: any): RouteType[] {
    const routes: RouteType[] = [];
    let i = 0;
    for (const route of data) {
        const {action, actualMiddlewares} = extractAction(route.middlewares);
        for (const method of route.methods.filter((method: string) => !['OPTIONS', 'HEAD'].includes(method))) {
            routes.push({
                id: String(i++),
                name: route.name,
                pattern: route.pattern,
                method: method,
                middlewares: actualMiddlewares,
                action: action,
            });
        }
    }

    return routes.sort((one, two) => one.pattern.localeCompare(two.pattern));
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

const ActionCell = styled(Typography)({
    fontFamily: primitives.fontFamilyMono,
    fontSize: '11px',
    flexShrink: 0,
    maxWidth: 300,
    overflow: 'hidden',
    textOverflow: 'ellipsis',
    whiteSpace: 'nowrap',
});

const DetailBox = styled(Box)(({theme}) => ({
    padding: theme.spacing(2, 2, 2, 6),
    backgroundColor: theme.palette.action.hover,
    borderBottom: `1px solid ${theme.palette.divider}`,
    fontSize: '12px',
}));

// ---------------------------------------------------------------------------
// Route detail (expanded)
// ---------------------------------------------------------------------------

const RouteDetail = ({route}: {route: RouteType}) => {
    const actionStr = route.action ? concatClassMethod(route.action[0] as string, route.action[1] as string) : null;

    return (
        <DetailBox>
            {actionStr && (
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
                            {actionStr}
                        </Typography>
                        <Tooltip title="Copy">
                            <IconButton size="small" onClick={() => clipboardCopy(actionStr)}>
                                <ContentCopy sx={{fontSize: 14}} />
                            </IconButton>
                        </Tooltip>
                        <Tooltip title="Examine as a container entry">
                            <IconButton
                                size="small"
                                href={'/inspector/container/view?class=' + (route.action![0] as string)}
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
                                {Array.isArray(mw)
                                    ? serializeCallable(mw)
                                    : typeof mw === 'string'
                                      ? mw
                                      : JSON.stringify(mw)}
                            </Typography>
                        ))}
                    </Box>
                </Box>
            )}

            {!actionStr && route.middlewares.length === 0 && (
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
    const [url, setUrl] = useState<string>('');
    const [filter, setFilter] = useState('');
    const deferredFilter = useDeferredValue(filter);
    const [activeFilters, setActiveFilters] = useState<Set<string>>(new Set());
    const [expandedIndex, setExpandedIndex] = useState<number | null>(null);

    useEffect(() => {
        if (!isSuccess) {
            return;
        }
        const routes = collectGroupsAndRoutes(data);
        setRoutes(routes);
    }, [isSuccess, data]);

    const onSubmitHandler = async (event: {preventDefault: () => void}) => {
        event.preventDefault();
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

            {/* Route checker */}
            <Paper
                component="form"
                onSubmit={onSubmitHandler}
                sx={{p: [0.5, 1], my: 2, display: 'flex', alignItems: 'center'}}
            >
                <InputBase
                    sx={{ml: 1, flex: 1}}
                    placeholder={'/site/index, POST /auth/login, DELETE /user/1'}
                    value={url}
                    onChange={(event) => setUrl(event.target.value)}
                />
                <IconButton type="submit" sx={{p: 2}}>
                    <CheckIcon />
                </IconButton>
            </Paper>
            <FormHelperText variant="outlined">
                Add an HTTP verb in the beginning of the path such as GET, POST, PUT, PATCH and etc. to check different
                methods. <br />
                Default method is GET and it can be omitted.
            </FormHelperText>

            {checkRouteQueryInfo.data && (
                <Alert severity={checkRouteQueryInfo.data.result ? 'success' : 'error'} sx={{mt: 1}}>
                    {checkRouteQueryInfo.data.result ? (
                        <AlertTitle>{serializeCallable(checkRouteQueryInfo.data.action)}</AlertTitle>
                    ) : (
                        <AlertTitle>{'Route is invalid'}</AlertTitle>
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
                    {filtered.map((route, index) => {
                        const expanded = expandedIndex === index;
                        const hasDetails =
                            route.action !== undefined || (route.middlewares && route.middlewares.length > 0);
                        const actionShort = route.action
                            ? concatClassMethod(
                                  (route.action[0] as string).split('\\').pop() as string,
                                  route.action[1] as string,
                              )
                            : null;

                        return (
                            <Box key={route.id}>
                                <RouteRow
                                    expanded={expanded}
                                    onClick={hasDetails ? () => setExpandedIndex(expanded ? null : index) : undefined}
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
                                    {actionShort && (
                                        <ActionCell sx={{color: 'text.secondary'}}>{actionShort}</ActionCell>
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
        </>
    );
};
