import {useGetParametersQuery} from '@app-dev-panel/panel/Module/Inspector/API/Inspector';
import {EmptyState} from '@app-dev-panel/sdk/Component/EmptyState';
import {FullScreenCircularProgress} from '@app-dev-panel/sdk/Component/FullScreenCircularProgress';
import {JsonRenderer} from '@app-dev-panel/sdk/Component/JsonRenderer';
import {QueryErrorState} from '@app-dev-panel/sdk/Component/QueryErrorState';
import {searchVariants} from '@app-dev-panel/sdk/Helper/layoutTranslit';
import {regexpQuote} from '@app-dev-panel/sdk/Helper/regexpQuote';
import {Box, Chip, Collapse, Icon, IconButton, Tooltip, Typography} from '@mui/material';
import {styled} from '@mui/material/styles';
import clipboardCopy from 'clipboard-copy';
import {useMemo, useState} from 'react';
import {useSearchParams} from 'react-router';

// ---------------------------------------------------------------------------
// Types
// ---------------------------------------------------------------------------

type ParamGroup = {name: string; params: Array<{key: string; value: unknown}>};

// ---------------------------------------------------------------------------
// Styled components
// ---------------------------------------------------------------------------

const Card = styled(Box)(({theme}) => ({
    border: `1px solid ${theme.palette.divider}`,
    borderRadius: theme.shape.borderRadius,
    overflow: 'hidden',
    '&:not(:last-child)': {marginBottom: theme.spacing(1.5)},
}));

const CardHeader = styled(Box, {shouldForwardProp: (p) => p !== 'expanded'})<{expanded?: boolean}>(
    ({theme, expanded}) => ({
        display: 'flex',
        alignItems: 'center',
        gap: theme.spacing(1.5),
        padding: theme.spacing(1.5, 2),
        cursor: 'pointer',
        backgroundColor: expanded ? theme.palette.action.hover : 'transparent',
        '&:hover': {backgroundColor: theme.palette.action.hover},
    }),
);

const ParamRow = styled(Box)(({theme}) => ({
    display: 'flex',
    alignItems: 'flex-start',
    gap: theme.spacing(2),
    padding: theme.spacing(0.75, 2),
    borderTop: `1px solid ${theme.palette.divider}`,
    '&:hover': {backgroundColor: theme.palette.action.hover},
    [theme.breakpoints.down('sm')]: {
        flexDirection: 'column',
        gap: theme.spacing(0.5),
        padding: theme.spacing(0.75, 1.5),
    },
}));

const ParamKey = styled(Typography)(({theme}) => ({
    fontFamily: theme.adp.fontFamilyMono,
    fontSize: '12px',
    fontWeight: 600,
    width: 220,
    flexShrink: 0,
    paddingTop: 4,
    wordBreak: 'break-word',
    [theme.breakpoints.down('sm')]: {width: '100%'},
}));

const ParamValue = styled(Box)({flex: 1, minWidth: 0, overflow: 'hidden'});

const Preview = styled(Typography)(({theme}) => ({
    fontFamily: theme.adp.fontFamilyMono,
    fontSize: '12px',
    color: theme.palette.text.secondary,
    overflow: 'hidden',
    textOverflow: 'ellipsis',
    whiteSpace: 'nowrap',
    flex: 1,
}));

// ---------------------------------------------------------------------------
// Helpers
// ---------------------------------------------------------------------------

function formatPreview(value: unknown): string {
    if (value === null || value === undefined) return 'null';
    if (typeof value === 'boolean') return String(value);
    if (typeof value === 'number') return String(value);
    if (typeof value === 'string') return `"${value}"`;
    if (Array.isArray(value)) return `[${value.length} items]`;
    if (typeof value === 'object') {
        const keys = Object.keys(value);
        return `{${keys.length} keys}`;
    }
    return String(value);
}

function groupParams(data: unknown): ParamGroup[] {
    if (!data || typeof data !== 'object') return [];
    const entries = Object.entries(data);
    const groups: ParamGroup[] = [];

    for (const [key, value] of entries) {
        if (value && typeof value === 'object' && !Array.isArray(value)) {
            const subEntries = Object.entries(value as Record<string, unknown>);
            groups.push({name: key, params: subEntries.map(([k, v]) => ({key: k, value: v}))});
        } else {
            // Top-level non-object params go into a "General" group
            let general = groups.find((g) => g.name === '__general__');
            if (!general) {
                general = {name: '__general__', params: []};
                groups.unshift(general);
            }
            general.params.push({key, value});
        }
    }

    return groups;
}

// ---------------------------------------------------------------------------
// Sub-components
// ---------------------------------------------------------------------------

const ParamCard = ({
    group,
    filter: _filter,
    defaultExpanded,
}: {
    group: ParamGroup;
    filter: string;
    defaultExpanded: boolean;
}) => {
    const [expanded, setExpanded] = useState(defaultExpanded);
    const displayName = group.name === '__general__' ? 'General' : group.name;

    const previewParams = group.params.slice(0, 4);

    return (
        <Card>
            <CardHeader expanded={expanded} onClick={() => setExpanded(!expanded)}>
                <Icon sx={{fontSize: 16, color: 'text.disabled'}}>{expanded ? 'expand_less' : 'expand_more'}</Icon>
                <Typography sx={{fontWeight: 600, fontSize: '13px', flex: 1}}>{displayName}</Typography>
                <Chip
                    label={`${group.params.length} params`}
                    size="small"
                    sx={{fontSize: '10px', height: 20, borderRadius: 1, backgroundColor: 'action.selected'}}
                />
            </CardHeader>

            {!expanded && (
                <Box sx={{px: 2, pb: 1.5}}>
                    <Preview>
                        {previewParams.map((p, i) => (
                            <span key={p.key}>
                                {i > 0 && <span style={{opacity: 0.4}}>{' · '}</span>}
                                <span style={{fontWeight: 500}}>{p.key}</span>
                                {' = '}
                                {formatPreview(p.value)}
                            </span>
                        ))}
                        {group.params.length > 4 && <span style={{opacity: 0.4}}> …</span>}
                    </Preview>
                </Box>
            )}

            <Collapse in={expanded}>
                {group.params.map((param) => (
                    <ParamRow key={param.key}>
                        <ParamKey>{param.key}</ParamKey>
                        <ParamValue>
                            <JsonRenderer value={param.value} depth={2} />
                        </ParamValue>
                        <Tooltip title="Copy value">
                            <IconButton
                                size="small"
                                sx={{mt: 0.25, flexShrink: 0}}
                                onClick={(e) => {
                                    e.stopPropagation();
                                    clipboardCopy(
                                        typeof param.value === 'string'
                                            ? param.value
                                            : JSON.stringify(param.value, null, 2),
                                    );
                                }}
                            >
                                <Icon sx={{fontSize: 14}}>content_copy</Icon>
                            </IconButton>
                        </Tooltip>
                    </ParamRow>
                ))}
            </Collapse>
        </Card>
    );
};

// ---------------------------------------------------------------------------
// Main component
// ---------------------------------------------------------------------------

export const ParametersPage = () => {
    const {data, isLoading, isError, error, refetch} = useGetParametersQuery();
    const [searchParams] = useSearchParams();
    const searchString = searchParams.get('filter') || '';

    const groups = useMemo(() => groupParams(data), [data]);

    const filtered = useMemo(() => {
        if (!searchString.trim()) return groups;
        const patterns = searchVariants(searchString).map((v) => new RegExp(regexpQuote(v), 'i'));
        return groups
            .map((group) => {
                const nameMatch = patterns.some((re) => re.test(group.name));
                const matchedParams = group.params.filter((p) =>
                    patterns.some((re) => re.test(p.key) || re.test(JSON.stringify(p.value))),
                );
                if (nameMatch) return group;
                if (matchedParams.length > 0) return {...group, params: matchedParams};
                return null;
            })
            .filter(Boolean) as ParamGroup[];
    }, [groups, searchString]);

    if (isLoading) {
        return <FullScreenCircularProgress />;
    }

    if (isError) {
        return (
            <QueryErrorState
                error={error}
                title="Failed to load parameters"
                fallback="Failed to load parameters."
                onRetry={refetch}
            />
        );
    }

    return (
        <Box sx={{pb: 2}}>
            {filtered.length === 0 && (
                <EmptyState
                    icon="tune"
                    title="No parameters found"
                    description={searchString ? `No parameters match "${searchString}"` : undefined}
                />
            )}
            {filtered.map((group) => (
                <ParamCard
                    key={group.name}
                    group={group}
                    filter={searchString}
                    defaultExpanded={filtered.length === 1 || !!searchString}
                />
            ))}
        </Box>
    );
};
