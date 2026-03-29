import {JsonRenderer} from '@app-dev-panel/panel/Module/Debug/Component/JsonRenderer';
import {useExecuteQueryMutation, useExplainQueryMutation} from '@app-dev-panel/panel/Module/Inspector/API/Inspector';
import {EmptyState} from '@app-dev-panel/sdk/Component/EmptyState';
import {ExplainPlanVisualizer} from '@app-dev-panel/sdk/Component/ExplainPlanVisualizer';
import {FilterInput} from '@app-dev-panel/sdk/Component/FilterInput';
import {DataTable} from '@app-dev-panel/sdk/Component/Grid';
import {SectionTitle} from '@app-dev-panel/sdk/Component/SectionTitle';
import {SqlHighlight} from '@app-dev-panel/sdk/Component/SqlHighlight';
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
    ToggleButton,
    ToggleButtonGroup,
    Tooltip,
    Typography,
} from '@mui/material';
import {styled, useTheme} from '@mui/material/styles';
import {type GridColDef, type GridRenderCellParams} from '@mui/x-data-grid';
import {useCallback, useDeferredValue, useEffect, useMemo, useState} from 'react';

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
type DuplicateGroup = {key: string; count: number; indices: number[]};
type DuplicatesData = {groups: DuplicateGroup[]; totalDuplicatedCount: number};
type DatabasePanelProps = {data: {queries?: Query[]; transactions?: any[]; duplicates?: DuplicatesData}};

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

const GroupHeader = styled(Box)(({theme}) => ({
    display: 'flex',
    alignItems: 'flex-start',
    gap: theme.spacing(1.5),
    padding: theme.spacing(1, 1.5),
    borderBottom: `1px solid ${theme.palette.divider}`,
    cursor: 'pointer',
    transition: 'background-color 0.1s ease',
    backgroundColor: theme.palette.action.selected,
    '&:hover': {backgroundColor: theme.palette.action.hover},
}));

const QueriesView = ({queries, duplicates}: {queries: Query[]; duplicates: DuplicatesData}) => {
    const theme = useTheme();
    const [filter, setFilter] = useState('');
    const deferredFilter = useDeferredValue(filter);
    const [expandedIndex, setExpandedIndex] = useState<number | null>(null);
    const [viewMode, setViewMode] = useState<'flat' | 'grouped'>('flat');

    const hasDuplicates = duplicates.groups.length > 0;

    if (!queries || queries.length === 0) {
        return <EmptyState icon="storage" title="No queries found" />;
    }

    const filtered = deferredFilter
        ? queries.filter((q) => q.sql.toLowerCase().includes(deferredFilter.toLowerCase()))
        : queries;

    const totalTime = queries.reduce((sum, q) => sum + getQueryTime(q.actions), 0);

    const groupedView = useMemo(() => {
        if (!hasDuplicates || viewMode !== 'grouped') return null;
        const filterLower = deferredFilter.toLowerCase();
        return duplicates.groups
            .filter((group) => !deferredFilter || group.key.toLowerCase().includes(filterLower))
            .map((group) => ({
                ...group,
                items: group.indices.map((i) => queries[i]).filter(Boolean),
                totalTime: group.indices.reduce((sum, i) => {
                    const q = queries[i];
                    return sum + (q ? getQueryTime(q.actions) : 0);
                }, 0),
            }));
    }, [hasDuplicates, viewMode, duplicates.groups, queries, deferredFilter]);

    return (
        <Box>
            <SectionTitle
                action={
                    <Box sx={{display: 'flex', alignItems: 'center', gap: 1}}>
                        {hasDuplicates && (
                            <ToggleButtonGroup
                                value={viewMode}
                                exclusive
                                onChange={(_e, value) => value && setViewMode(value)}
                                size="small"
                                sx={{height: 28}}
                            >
                                <ToggleButton value="flat" sx={{fontSize: '11px', px: 1.5, textTransform: 'none'}}>
                                    All
                                </ToggleButton>
                                <ToggleButton value="grouped" sx={{fontSize: '11px', px: 1.5, textTransform: 'none'}}>
                                    <Tooltip title="Show duplicate queries (N+1)" placement="top">
                                        <Box sx={{display: 'flex', alignItems: 'center', gap: 0.5}}>
                                            Duplicates
                                            <Chip
                                                label={duplicates.groups.length}
                                                size="small"
                                                color="warning"
                                                sx={{fontSize: '10px', height: 18, minWidth: 20, borderRadius: 1}}
                                            />
                                        </Box>
                                    </Tooltip>
                                </ToggleButton>
                            </ToggleButtonGroup>
                        )}
                        <FilterInput value={filter} onChange={setFilter} placeholder="Filter SQL..." />
                    </Box>
                }
            >
                {`${filtered.length} queries · ${formatMillisecondsAsDuration(totalTime)} total`}
                {hasDuplicates && (
                    <Chip
                        label={`N+1`}
                        size="small"
                        color="warning"
                        sx={{fontSize: '10px', height: 18, borderRadius: 1, ml: 1}}
                    />
                )}
            </SectionTitle>

            {viewMode === 'grouped' && groupedView
                ? groupedView.map((group) => (
                      <DuplicateQueryGroup
                          key={group.key}
                          group={group}
                          queries={queries}
                          expandedIndex={expandedIndex}
                          onToggleExpand={setExpandedIndex}
                      />
                  ))
                : filtered.map((query, index) => {
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

const DuplicateQueryGroup = ({
    group,
    queries,
    expandedIndex,
    onToggleExpand,
}: {
    group: DuplicateGroup & {items: Query[]; totalTime: number};
    queries: Query[];
    expandedIndex: number | null;
    onToggleExpand: (index: number | null) => void;
}) => {
    const theme = useTheme();
    const [expanded, setExpanded] = useState(false);
    const [wasExpanded, setWasExpanded] = useState(false);
    useEffect(() => {
        if (expanded) setWasExpanded(true);
    }, [expanded]);

    const sqlPreview = group.key;
    const verb = sqlPreview.trim().split(/\s/)[0]?.toUpperCase();

    return (
        <Box>
            <GroupHeader onClick={() => setExpanded(!expanded)}>
                <Chip
                    label={verb}
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
                <Box sx={{flex: 1, wordBreak: 'break-word', lineHeight: 1.6, color: 'text.primary'}}>
                    <SqlHighlight sql={sqlPreview} inline />
                </Box>
                <Chip
                    label={`${group.count}x`}
                    size="small"
                    color="warning"
                    sx={{fontWeight: 700, fontSize: '11px', height: 22, borderRadius: 1, flexShrink: 0}}
                />
                <DurationCell sx={{color: durationColor(group.totalTime, theme)}}>
                    {formatMillisecondsAsDuration(group.totalTime)}
                </DurationCell>
                <IconButton size="small" sx={{flexShrink: 0}}>
                    <Icon sx={{fontSize: 16}}>{expanded ? 'expand_less' : 'expand_more'}</Icon>
                </IconButton>
            </GroupHeader>
            <Collapse in={expanded}>
                {wasExpanded && (
                    <Box sx={{pl: 2}}>
                        {group.indices.map((originalIndex) => {
                            const query = queries[originalIndex];
                            if (!query) return null;
                            const isExpanded = expandedIndex === originalIndex;
                            const ms = getQueryTime(query.actions);
                            const color = durationColor(ms, theme);
                            return (
                                <QueryRowWithExplain
                                    key={originalIndex}
                                    query={query}
                                    expanded={isExpanded}
                                    ms={ms}
                                    color={color}
                                    onToggle={() => onToggleExpand(isExpanded ? null : originalIndex)}
                                    onExpand={() => onToggleExpand(originalIndex)}
                                />
                            );
                        })}
                    </Box>
                )}
            </Collapse>
        </Box>
    );
};

const QueryResultTable = ({
    data,
    error,
    isLoading,
}: {
    data: Record<string, unknown>[] | undefined;
    error: any;
    isLoading: boolean;
}) => {
    const columns = useMemo<GridColDef[]>(() => {
        if (!data || data.length === 0) return [];
        return Object.keys(data[0]).map((key) => ({
            field: key,
            headerName: key,
            flex: 1,
            minWidth: 100,
            renderCell: (params: GridRenderCellParams) => (
                <span style={{wordBreak: 'break-all', maxHeight: 100, overflowY: 'hidden'}}>
                    {params.value == null ? <em style={{color: '#999'}}>NULL</em> : String(params.value)}
                </span>
            ),
        }));
    }, [data]);

    const getRowId = useCallback((_row: Record<string, unknown>, index?: number) => index ?? 0, []);

    if (isLoading) {
        return (
            <Box sx={{display: 'flex', alignItems: 'center', gap: 1, py: 1}}>
                <CircularProgress size={14} />
                <Typography sx={{fontSize: '12px', color: 'text.disabled'}}>Executing query...</Typography>
            </Box>
        );
    }

    if (error) {
        return (
            <Typography sx={{fontSize: '12px', color: 'error.main', fontFamily: primitives.fontFamilyMono}}>
                {'data' in error && (error.data as any)?.data?.error
                    ? (error.data as any).data.error
                    : 'Failed to execute query'}
            </Typography>
        );
    }

    if (data && Array.isArray(data) && data.length === 0) {
        return (
            <Typography sx={{fontSize: '12px', color: 'text.disabled', fontFamily: primitives.fontFamilyMono}}>
                Query returned no rows
            </Typography>
        );
    }

    if (!data) return null;

    return (
        <Box sx={{mt: 0.5}}>
            <Typography sx={{fontSize: '11px', color: 'text.disabled', mb: 0.5}}>
                {data.length} row{data.length !== 1 ? 's' : ''} returned
            </Typography>
            <DataTable rows={data as any[]} columns={columns} getRowId={getRowId} rowHeight="auto" />
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
    const [wasExpanded, setWasExpanded] = useState(false);
    useEffect(() => {
        if (expanded) setWasExpanded(true);
    }, [expanded]);
    const [explainQuery, {data, isLoading, error}] = useExplainQueryMutation({fixedCacheKey: undefined});
    const [analyzeQuery, {data: analyzeData, isLoading: analyzeLoading, error: analyzeError}] = useExplainQueryMutation(
        {fixedCacheKey: undefined},
    );
    const [executeQuery, {data: queryData, isLoading: queryLoading, error: queryError}] = useExecuteQueryMutation({
        fixedCacheKey: undefined,
    });
    const sql = typeof query.rawSql === 'string' ? query.rawSql : query.sql;

    const handleExplain = (e: React.MouseEvent) => {
        e.stopPropagation();
        explainQuery({sql, params: query.params});
        if (!expanded) {
            onExpand();
        }
    };

    const handleAnalyze = (e: React.MouseEvent) => {
        e.stopPropagation();
        analyzeQuery({sql, params: query.params, analyze: true});
        if (!expanded) {
            onExpand();
        }
    };

    const handleQuery = (e: React.MouseEvent) => {
        e.stopPropagation();
        executeQuery({sql, params: query.params});
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
                <Box sx={{flex: 1, wordBreak: 'break-word', lineHeight: 1.6}}>
                    <SqlHighlight sql={query.sql} inline />
                </Box>
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
                {wasExpanded && (
                    <DetailBox>
                        <Box sx={{mb: 1.5}}>
                            <Box sx={{display: 'flex', alignItems: 'center', gap: 0.5, mb: 0.5}}>
                                <Typography sx={{fontSize: '11px', fontWeight: 600, color: 'text.disabled'}}>
                                    Raw SQL
                                </Typography>
                                <CopyButton
                                    text={
                                        typeof query.rawSql === 'string' ? query.rawSql : JSON.stringify(query.rawSql)
                                    }
                                />
                            </Box>
                            <SqlHighlight
                                sql={typeof query.rawSql === 'string' ? query.rawSql : JSON.stringify(query.rawSql)}
                                formatted
                                showLineNumbers
                            />
                        </Box>
                        {Object.keys(query.params).length > 0 && (
                            <Box sx={{mb: 1.5}}>
                                <Typography sx={{fontSize: '11px', fontWeight: 600, color: 'text.disabled', mb: 0.5}}>
                                    Parameters
                                </Typography>
                                <JsonRenderer value={query.params} />
                            </Box>
                        )}
                        <ExplainResult
                            data={data}
                            error={error}
                            isLoading={isLoading}
                            analyzeData={analyzeData}
                            analyzeError={analyzeError}
                            analyzeLoading={analyzeLoading}
                            queryData={queryData}
                            queryError={queryError}
                            queryLoading={queryLoading}
                            onExplain={handleExplain}
                            onAnalyze={handleAnalyze}
                            onQuery={handleQuery}
                        />
                    </DetailBox>
                )}
            </Collapse>
        </Box>
    );
};

const ExplainDataView = ({data, error, label}: {data: any[] | undefined; error: any; label: string}) => {
    if (error) {
        return (
            <Typography sx={{fontSize: '12px', color: 'error.main', fontFamily: primitives.fontFamilyMono}}>
                {'data' in error && (error.data as any)?.data?.error
                    ? (error.data as any).data.error
                    : `Failed to run ${label}`}
            </Typography>
        );
    }
    if (data && Array.isArray(data) && data.length > 0) {
        return <ExplainPlanVisualizer data={data} />;
    }
    if (data && Array.isArray(data) && data.length === 0) {
        return (
            <Typography sx={{fontSize: '12px', color: 'text.disabled', fontFamily: primitives.fontFamilyMono}}>
                No {label} data returned
            </Typography>
        );
    }
    return null;
};

const actionButtonSx = {fontSize: '11px', textTransform: 'none', padding: '2px 10px', minHeight: 26, borderRadius: 1};

const ExplainResult = ({
    data,
    error,
    isLoading,
    analyzeData,
    analyzeError,
    analyzeLoading,
    queryData,
    queryError,
    queryLoading,
    onExplain,
    onAnalyze,
    onQuery,
}: {
    data: any[] | undefined;
    error: any;
    isLoading: boolean;
    analyzeData: any[] | undefined;
    analyzeError: any;
    analyzeLoading: boolean;
    queryData: Record<string, unknown>[] | undefined;
    queryError: any;
    queryLoading: boolean;
    onExplain: (e: React.MouseEvent) => void;
    onAnalyze: (e: React.MouseEvent) => void;
    onQuery: (e: React.MouseEvent) => void;
}) => {
    const hasExplainResult = data !== undefined || error;
    const hasAnalyzeResult = analyzeData !== undefined || analyzeError;
    const hasQueryResult = queryData !== undefined || queryError;
    const hasAnyResult = hasExplainResult || hasAnalyzeResult || hasQueryResult;

    return (
        <Box sx={{mt: 1.5}}>
            <Box sx={{display: 'flex', gap: 1, mb: hasAnyResult ? 1 : 0}}>
                <Button
                    size="small"
                    variant={hasQueryResult ? 'text' : 'outlined'}
                    disabled={queryLoading}
                    onClick={onQuery}
                    startIcon={
                        queryLoading ? <CircularProgress size={14} /> : <Icon sx={{fontSize: 14}}>play_arrow</Icon>
                    }
                    sx={actionButtonSx}
                >
                    {hasQueryResult ? 'Re-run' : 'QUERY'}
                </Button>
                <Button
                    size="small"
                    variant={hasExplainResult ? 'text' : 'outlined'}
                    disabled={isLoading}
                    onClick={onExplain}
                    startIcon={
                        isLoading ? <CircularProgress size={14} /> : <Icon sx={{fontSize: 14}}>query_stats</Icon>
                    }
                    sx={actionButtonSx}
                >
                    {hasExplainResult ? 'Repeat' : 'EXPLAIN'}
                </Button>
                <Button
                    size="small"
                    variant={hasAnalyzeResult ? 'text' : 'outlined'}
                    disabled={analyzeLoading}
                    onClick={onAnalyze}
                    startIcon={analyzeLoading ? <CircularProgress size={14} /> : <Icon sx={{fontSize: 14}}>speed</Icon>}
                    sx={actionButtonSx}
                >
                    {hasAnalyzeResult ? 'Repeat Analyze' : 'EXPLAIN ANALYZE'}
                </Button>
            </Box>
            {hasQueryResult && (
                <Box sx={{mb: hasExplainResult || hasAnalyzeResult ? 1.5 : 0}}>
                    <Typography sx={{fontSize: '11px', fontWeight: 600, color: 'text.disabled', mb: 0.5}}>
                        QUERY RESULT
                    </Typography>
                    <QueryResultTable data={queryData} error={queryError} isLoading={queryLoading} />
                </Box>
            )}
            {hasExplainResult && (
                <Box sx={{mb: hasAnalyzeResult ? 1.5 : 0}}>
                    <Typography sx={{fontSize: '11px', fontWeight: 600, color: 'text.disabled', mb: 0.5}}>
                        EXPLAIN
                    </Typography>
                    <ExplainDataView data={data} error={error} label="EXPLAIN" />
                </Box>
            )}
            {hasAnalyzeResult && (
                <Box>
                    <Typography sx={{fontSize: '11px', fontWeight: 600, color: 'text.disabled', mb: 0.5}}>
                        EXPLAIN ANALYZE
                    </Typography>
                    <ExplainDataView data={analyzeData} error={analyzeError} label="EXPLAIN ANALYZE" />
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

    const duplicates: DuplicatesData = data.duplicates ?? {groups: [], totalDuplicatedCount: 0};

    return <QueriesView queries={data.queries} duplicates={duplicates} />;
};
