import {JsonRenderer} from '@app-dev-panel/panel/Module/Debug/Component/JsonRenderer';
import {useExplainQueryMutation} from '@app-dev-panel/panel/Module/Inspector/API/Inspector';
import {EmptyState} from '@app-dev-panel/sdk/Component/EmptyState';
import {FilterInput} from '@app-dev-panel/sdk/Component/FilterInput';
import {SectionTitle} from '@app-dev-panel/sdk/Component/SectionTitle';
import {primitives} from '@app-dev-panel/sdk/Component/Theme/tokens';
import {formatMillisecondsAsDuration} from '@app-dev-panel/sdk/Helper/formatDate';
import {
    Box,
    Button,
    Chip,
    CircularProgress,
    Collapse,
    Icon,
    IconButton,
    type Theme,
    Tooltip,
    Typography,
} from '@mui/material';
import {styled, useTheme} from '@mui/material/styles';
import {useDeferredValue, useState} from 'react';

type QueryAction = {action: 'query.start' | 'query.end'; time: number};
type Query = {
    sql: string;
    rawSql: string;
    line: string;
    params: Record<string, number | string>;
    status: 'success';
    actions: QueryAction[];
    rowsNumber: number;
};
type DatabasePanelProps = {data: {queries?: Query[]; transactions?: any[]}};

function getQueryTime(actions: QueryAction[]) {
    const start = actions.find((a) => a.action === 'query.start');
    const end = actions.find((a) => a.action === 'query.end');
    return end && start ? end.time - start.time : 0;
}

function durationColor(ms: number, theme: Theme): string {
    if (ms > 100) return theme.palette.error.main;
    if (ms > 30) return theme.palette.warning.main;
    return theme.palette.success.main;
}

const QueryRow = styled(Box, {shouldForwardProp: (p) => p !== 'expanded'})<{expanded?: boolean}>(
    ({theme, expanded}) => ({
        display: 'flex',
        alignItems: 'flex-start',
        gap: theme.spacing(1.5),
        padding: theme.spacing(1, 1.5),
        borderBottom: `1px solid ${theme.palette.divider}`,
        cursor: 'pointer',
        transition: 'background-color 0.1s ease',
        backgroundColor: expanded ? theme.palette.action.hover : 'transparent',
        '&:hover': {backgroundColor: theme.palette.action.hover},
    }),
);

const SqlCell = styled(Typography)({
    fontFamily: primitives.fontFamilyMono,
    fontSize: '12px',
    flex: 1,
    wordBreak: 'break-word',
    lineHeight: 1.6,
});

const DurationCell = styled(Typography)({
    fontFamily: primitives.fontFamilyMono,
    fontSize: '11px',
    flexShrink: 0,
    textAlign: 'right',
    width: 70,
    paddingTop: 2,
});

const DetailBox = styled(Box)(({theme}) => ({
    padding: theme.spacing(1.5, 1.5, 1.5, 6),
    backgroundColor: theme.palette.action.hover,
    borderBottom: `1px solid ${theme.palette.divider}`,
    fontSize: '12px',
}));

const QueriesView = ({queries}: {queries: Query[]}) => {
    const theme = useTheme();
    const [filter, setFilter] = useState('');
    const deferredFilter = useDeferredValue(filter);
    const [expandedIndex, setExpandedIndex] = useState<number | null>(null);

    if (!queries || queries.length === 0) {
        return <EmptyState icon="storage" title="No queries found" />;
    }

    const filtered = deferredFilter
        ? queries.filter((q) => q.sql.toLowerCase().includes(deferredFilter.toLowerCase()))
        : queries;

    const totalTime = queries.reduce((sum, q) => sum + getQueryTime(q.actions), 0);

    return (
        <Box>
            <SectionTitle
                action={<FilterInput value={filter} onChange={setFilter} placeholder="Filter SQL..." />}
            >{`${filtered.length} queries · ${formatMillisecondsAsDuration(totalTime)} total`}</SectionTitle>

            {filtered.map((query, index) => {
                const expanded = expandedIndex === index;
                const ms = getQueryTime(query.actions);
                const color = durationColor(ms, theme);

                return (
                    <QueryRowWithExplain
                        key={index}
                        query={query}
                        expanded={expanded}
                        ms={ms}
                        color={color}
                        onToggle={() => setExpandedIndex(expanded ? null : index)}
                        onExpand={() => setExpandedIndex(index)}
                    />
                );
            })}
        </Box>
    );
};

const QueryRowWithExplain = ({
    query,
    expanded,
    ms,
    color,
    onToggle,
    onExpand,
}: {
    query: Query;
    expanded: boolean;
    ms: number;
    color: string;
    onToggle: () => void;
    onExpand: () => void;
}) => {
    const [explainQuery, {data, isLoading, error}] = useExplainQueryMutation();
    const sql = typeof query.rawSql === 'string' ? query.rawSql : query.sql;

    const handleExplain = (e: React.MouseEvent) => {
        e.stopPropagation();
        explainQuery({sql, params: query.params});
        if (!expanded) {
            onExpand();
        }
    };

    return (
        <Box>
            <QueryRow expanded={expanded} onClick={onToggle}>
                <Chip
                    label={query.sql.trim().split(/\s/)[0]?.toUpperCase()}
                    size="small"
                    sx={{
                        fontWeight: 700,
                        fontSize: '9px',
                        height: 18,
                        minWidth: 50,
                        backgroundColor: 'primary.main',
                        color: 'common.white',
                        borderRadius: 1,
                        flexShrink: 0,
                        mt: '2px',
                    }}
                />
                <SqlCell>{query.sql}</SqlCell>
                {query.rowsNumber != null && (
                    <Typography sx={{fontSize: '11px', color: 'text.disabled', flexShrink: 0, whiteSpace: 'nowrap'}}>
                        {query.rowsNumber} row{query.rowsNumber !== 1 ? 's' : ''}
                    </Typography>
                )}
                <DurationCell sx={{color}}>{formatMillisecondsAsDuration(ms)}</DurationCell>
                <Tooltip title="EXPLAIN" placement="top">
                    <IconButton
                        size="small"
                        onClick={handleExplain}
                        disabled={isLoading}
                        sx={{flexShrink: 0}}
                        aria-label="Explain query"
                    >
                        {isLoading ? <CircularProgress size={14} /> : <Icon sx={{fontSize: 16}}>query_stats</Icon>}
                    </IconButton>
                </Tooltip>
                <IconButton size="small" sx={{flexShrink: 0}}>
                    <Icon sx={{fontSize: 16}}>{expanded ? 'expand_less' : 'expand_more'}</Icon>
                </IconButton>
            </QueryRow>
            <Collapse in={expanded}>
                <DetailBox>
                    <Box sx={{mb: 1.5}}>
                        <Box sx={{display: 'flex', alignItems: 'center', gap: 0.5, mb: 0.5}}>
                            <Typography sx={{fontSize: '11px', fontWeight: 600, color: 'text.disabled'}}>
                                Raw SQL
                            </Typography>
                            <CopyButton
                                text={typeof query.rawSql === 'string' ? query.rawSql : JSON.stringify(query.rawSql)}
                            />
                        </Box>
                        <Typography
                            sx={{
                                fontFamily: primitives.fontFamilyMono,
                                fontSize: '12px',
                                color: 'text.secondary',
                                whiteSpace: 'pre-wrap',
                                wordBreak: 'break-word',
                            }}
                        >
                            {typeof query.rawSql === 'string' ? query.rawSql : JSON.stringify(query.rawSql)}
                        </Typography>
                    </Box>
                    {Object.keys(query.params).length > 0 && (
                        <Box sx={{mb: 1.5}}>
                            <Typography sx={{fontSize: '11px', fontWeight: 600, color: 'text.disabled', mb: 0.5}}>
                                Parameters
                            </Typography>
                            <JsonRenderer value={query.params} />
                        </Box>
                    )}
                    <ExplainResult data={data} error={error} isLoading={isLoading} onExplain={handleExplain} />
                </DetailBox>
            </Collapse>
        </Box>
    );
};

const ExplainResult = ({
    data,
    error,
    isLoading,
    onExplain,
}: {
    data: any[] | undefined;
    error: any;
    isLoading: boolean;
    onExplain: (e: React.MouseEvent) => void;
}) => {
    const hasResult = data !== undefined || error;

    return (
        <Box sx={{mt: 1.5}}>
            {!hasResult && (
                <Button
                    size="small"
                    variant="outlined"
                    disabled={isLoading}
                    onClick={onExplain}
                    startIcon={
                        isLoading ? <CircularProgress size={14} /> : <Icon sx={{fontSize: 14}}>query_stats</Icon>
                    }
                    sx={{fontSize: '11px', textTransform: 'none', padding: '2px 10px', minHeight: 26, borderRadius: 1}}
                >
                    EXPLAIN
                </Button>
            )}
            {hasResult && (
                <Box>
                    <Typography sx={{fontSize: '11px', fontWeight: 600, color: 'text.disabled', mb: 0.5}}>
                        EXPLAIN
                    </Typography>
                    {error && (
                        <Typography sx={{fontSize: '12px', color: 'error.main', fontFamily: primitives.fontFamilyMono}}>
                            {'data' in error && (error.data as any)?.data?.error
                                ? (error.data as any).data.error
                                : 'Failed to run EXPLAIN'}
                        </Typography>
                    )}
                    {data && Array.isArray(data) && data.length > 0 && <JsonRenderer value={data} />}
                    {data && Array.isArray(data) && data.length === 0 && (
                        <Typography
                            sx={{fontSize: '12px', color: 'text.disabled', fontFamily: primitives.fontFamilyMono}}
                        >
                            No EXPLAIN data returned
                        </Typography>
                    )}
                </Box>
            )}
        </Box>
    );
};

const CopyButton = ({text}: {text: string}) => {
    const [copied, setCopied] = useState(false);

    const handleCopy = (e: React.MouseEvent) => {
        e.stopPropagation();
        navigator.clipboard.writeText(text).then(() => {
            setCopied(true);
            setTimeout(() => setCopied(false), 1500);
        });
    };

    return (
        <Tooltip title={copied ? 'Copied!' : 'Copy'} placement="top">
            <IconButton size="small" onClick={handleCopy} sx={{padding: '2px'}}>
                <Icon sx={{fontSize: 14, color: 'text.disabled'}}>{copied ? 'check' : 'content_copy'}</Icon>
            </IconButton>
        </Tooltip>
    );
};

export const DatabasePanel = ({data}: DatabasePanelProps) => {
    if (!data || !data.queries || data.queries.length === 0) {
        return <EmptyState icon="storage" title="No queries found" />;
    }

    return <QueriesView queries={data.queries} />;
};
