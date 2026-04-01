import {JsonRenderer} from '@app-dev-panel/panel/Module/Debug/Component/JsonRenderer';
import {EmptyState} from '@app-dev-panel/sdk/Component/EmptyState';
import {FileLink} from '@app-dev-panel/sdk/Component/FileLink';
import {FilterInput} from '@app-dev-panel/sdk/Component/FilterInput';
import {SectionTitle} from '@app-dev-panel/sdk/Component/SectionTitle';
import {primitives} from '@app-dev-panel/sdk/Component/Theme/tokens';
import {formatMillisecondsAsDuration} from '@app-dev-panel/sdk/Helper/formatDate';
import {OpenInNew} from '@mui/icons-material';
import {Box, Button, Chip, type Theme, Typography} from '@mui/material';
import {styled, useTheme} from '@mui/material/styles';
import {useDeferredValue, useState} from 'react';

type CurrentRoute = {
    matchTime: number;
    name: string | null;
    pattern: string;
    arguments: Record<string, string> | null;
    host: string | null;
    uri: string;
    action: any;
    middlewares: any[];
};
type Route = {name?: string; pattern?: string; methods?: string[]; host?: string};
type RouterPanelProps = {
    data: {currentRoute: CurrentRoute | null; routesTree?: any; routes?: Route[]; routeTime?: number};
};

function durationColor(ms: number, theme: Theme): string {
    if (ms > 100) return theme.palette.error.main;
    if (ms > 30) return theme.palette.warning.main;
    return theme.palette.success.main;
}

const InfoCard = styled(Box)(({theme}) => ({
    padding: theme.spacing(2.5),
    borderRadius: Number(theme.shape.borderRadius) * 1.5,
    border: `1px solid ${theme.palette.divider}`,
    backgroundColor: theme.palette.background.paper,
    marginBottom: theme.spacing(3),
}));

const FieldRow = styled(Box)(({theme}) => ({
    display: 'flex',
    alignItems: 'flex-start',
    gap: theme.spacing(1),
    marginBottom: theme.spacing(1),
    fontSize: '13px',
}));

const FieldLabel = styled(Typography)(({theme}) => ({
    fontSize: '12px',
    fontWeight: 600,
    color: theme.palette.text.disabled,
    width: 90,
    flexShrink: 0,
}));

const FieldValue = styled(Typography)({fontSize: '12px', wordBreak: 'break-word'});

const RouteRow = styled(Box)(({theme}) => ({
    display: 'flex',
    alignItems: 'center',
    gap: theme.spacing(1.5),
    padding: theme.spacing(1, 1.5),
    borderBottom: `1px solid ${theme.palette.divider}`,
    transition: 'background-color 0.1s ease',
    '&:hover': {backgroundColor: theme.palette.action.hover},
}));

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

export const RouterPanel = ({data}: RouterPanelProps) => {
    const theme = useTheme();
    const [filter, setFilter] = useState('');
    const deferredFilter = useDeferredValue(filter);

    if (!data) {
        return <EmptyState icon="route" title="No router data found" />;
    }

    const {currentRoute, routes, routeTime} = data;

    if (!currentRoute && (!routes || routes.length === 0)) {
        return <EmptyState icon="route" title="No route matched" />;
    }

    const filteredRoutes = routes
        ? deferredFilter
            ? routes.filter((r) => {
                  const lower = deferredFilter.toLowerCase();
                  return (
                      (r.name?.toLowerCase().includes(lower) ?? false) ||
                      (r.pattern?.toLowerCase().includes(lower) ?? false)
                  );
              })
            : routes
        : [];

    return (
        <Box>
            {currentRoute && (
                <InfoCard>
                    <Typography sx={{fontSize: '14px', fontWeight: 600, mb: 1.5}}>Current Route</Typography>
                    {currentRoute.name && (
                        <FieldRow>
                            <FieldLabel>Name</FieldLabel>
                            <FieldValue sx={{fontWeight: 500}}>{currentRoute.name}</FieldValue>
                        </FieldRow>
                    )}
                    <FieldRow>
                        <FieldLabel>Pattern</FieldLabel>
                        <FieldValue sx={{fontFamily: primitives.fontFamilyMono}}>{currentRoute.pattern}</FieldValue>
                    </FieldRow>
                    <FieldRow>
                        <FieldLabel>URI</FieldLabel>
                        <FieldValue sx={{fontFamily: primitives.fontFamilyMono}}>{currentRoute.uri}</FieldValue>
                    </FieldRow>
                    {currentRoute.host && (
                        <FieldRow>
                            <FieldLabel>Host</FieldLabel>
                            <FieldValue sx={{fontFamily: primitives.fontFamilyMono}}>{currentRoute.host}</FieldValue>
                        </FieldRow>
                    )}
                    <FieldRow>
                        <FieldLabel>Match Time</FieldLabel>
                        <FieldValue sx={{color: durationColor(currentRoute.matchTime, theme)}}>
                            {formatMillisecondsAsDuration(currentRoute.matchTime)}
                        </FieldValue>
                    </FieldRow>
                    {routeTime != null && (
                        <FieldRow>
                            <FieldLabel>Route Time</FieldLabel>
                            <FieldValue sx={{color: durationColor(routeTime, theme)}}>
                                {formatMillisecondsAsDuration(routeTime)}
                            </FieldValue>
                        </FieldRow>
                    )}

                    {currentRoute.arguments &&
                        typeof currentRoute.arguments === 'object' &&
                        !Array.isArray(currentRoute.arguments) &&
                        Object.keys(currentRoute.arguments).length > 0 && (
                            <Box sx={{mt: 1.5}}>
                                <Typography sx={{fontSize: '11px', fontWeight: 600, color: 'text.disabled', mb: 0.5}}>
                                    Arguments
                                </Typography>
                                {Object.entries(currentRoute.arguments).map(([key, val]) => (
                                    <Box key={key} sx={{display: 'flex', gap: 1, ml: 1, mb: 0.25}}>
                                        <Typography
                                            sx={{
                                                fontFamily: primitives.fontFamilyMono,
                                                fontSize: '12px',
                                                color: 'primary.main',
                                            }}
                                        >
                                            {key}:
                                        </Typography>
                                        <Typography
                                            sx={{
                                                fontFamily: primitives.fontFamilyMono,
                                                fontSize: '12px',
                                                color: 'text.secondary',
                                            }}
                                        >
                                            {val}
                                        </Typography>
                                    </Box>
                                ))}
                            </Box>
                        )}

                    {currentRoute.action && (
                        <FieldRow>
                            <FieldLabel>Action</FieldLabel>
                            <FieldValue sx={{fontFamily: primitives.fontFamilyMono}}>
                                {typeof currentRoute.action === 'string' ? (
                                    <FileLink className={currentRoute.action}>
                                        <Typography
                                            component="span"
                                            sx={{
                                                fontFamily: primitives.fontFamilyMono,
                                                fontSize: '12px',
                                                color: 'primary.main',
                                                '&:hover': {textDecoration: 'underline'},
                                            }}
                                        >
                                            {currentRoute.action}
                                        </Typography>
                                    </FileLink>
                                ) : (
                                    <JsonRenderer value={currentRoute.action} />
                                )}
                            </FieldValue>
                        </FieldRow>
                    )}

                    {currentRoute.middlewares && currentRoute.middlewares.length > 0 && (
                        <Box sx={{mt: 1.5}}>
                            <Typography sx={{fontSize: '11px', fontWeight: 600, color: 'text.disabled', mb: 0.5}}>
                                Middlewares
                            </Typography>
                            <JsonRenderer value={currentRoute.middlewares} />
                        </Box>
                    )}
                </InfoCard>
            )}

            {routes && routes.length > 0 && (
                <Box>
                    <SectionTitle
                        action={
                            <Box sx={{display: 'flex', alignItems: 'center', gap: 2}}>
                                <Button
                                    href="/inspector/routes"
                                    variant="outlined"
                                    size="small"
                                    startIcon={<OpenInNew sx={{fontSize: '14px !important'}} />}
                                    sx={{fontSize: '11px', textTransform: 'none', height: 28, borderRadius: 1, px: 1.5}}
                                >
                                    Inspector
                                </Button>
                                <FilterInput value={filter} onChange={setFilter} placeholder="Filter routes..." />
                            </Box>
                        }
                    >{`${filteredRoutes.length} routes`}</SectionTitle>

                    {filteredRoutes.map((route, index) => (
                        <RouteRow key={index}>
                            <PatternCell>{route.pattern ?? '-'}</PatternCell>
                            {route.name && <NameCell>{route.name}</NameCell>}
                            {route.methods && route.methods.length > 0 && (
                                <Box sx={{display: 'flex', gap: 0.5, flexShrink: 0}}>
                                    {route.methods.map((method) => (
                                        <Chip
                                            key={method}
                                            label={method}
                                            size="small"
                                            sx={{
                                                fontWeight: 700,
                                                fontSize: '9px',
                                                height: 18,
                                                minWidth: 40,
                                                borderRadius: 1,
                                                backgroundColor: 'primary.main',
                                                color: 'common.white',
                                            }}
                                        />
                                    ))}
                                </Box>
                            )}
                        </RouteRow>
                    ))}
                </Box>
            )}
        </Box>
    );
};
