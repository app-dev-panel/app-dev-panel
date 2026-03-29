import {Box, Chip, LinearProgress, Tooltip, Typography} from '@mui/material';
import Icon from '@mui/material/Icon';
import {alpha, useTheme} from '@mui/material/styles';
import React, {useCallback, useMemo, useState} from 'react';

// ---------------------------------------------------------------------------
// Types
// ---------------------------------------------------------------------------

/** PostgreSQL JSON EXPLAIN node */
type PgPlanNode = {
    'Node Type': string;
    'Relation Name'?: string;
    Alias?: string;
    'Startup Cost'?: number;
    'Total Cost'?: number;
    'Plan Rows'?: number;
    'Plan Width'?: number;
    'Actual Startup Time'?: number;
    'Actual Total Time'?: number;
    'Actual Rows'?: number;
    'Actual Loops'?: number;
    'Index Name'?: string;
    'Index Cond'?: string;
    Filter?: string;
    'Join Type'?: string;
    'Hash Cond'?: string;
    'Merge Cond'?: string;
    'Sort Key'?: string[];
    'Sort Method'?: string;
    'Rows Removed by Filter'?: number;
    'Shared Hit Blocks'?: number;
    'Shared Read Blocks'?: number;
    Plans?: PgPlanNode[];
    [key: string]: unknown;
};

type PgExplainResult = {Plan: PgPlanNode; 'Planning Time'?: number; 'Execution Time'?: number; [key: string]: unknown};

/** MySQL EXPLAIN row */
type MySqlExplainRow = {
    id: number;
    select_type: string;
    table: string;
    partitions?: string | null;
    type: string;
    possible_keys: string | null;
    key: string | null;
    key_len: string | null;
    ref: string | null;
    rows: number;
    filtered?: number;
    Extra: string;
};

/** SQLite / text-based detail row */
type DetailRow = {id?: number; detail: string};

/** Normalized node for rendering */
type PlanTreeNode = {
    id: string;
    nodeType: string;
    table?: string;
    index?: string;
    condition?: string;
    cost?: number;
    rows?: number;
    actualTime?: number;
    actualRows?: number;
    loops?: number;
    extra?: string;
    width?: number;
    sharedHitBlocks?: number;
    sharedReadBlocks?: number;
    rowsRemovedByFilter?: number;
    sortKey?: string[];
    sortMethod?: string;
    exclusive?: number;
    children: PlanTreeNode[];
    raw: Record<string, unknown>;
};

type ExplainPlanVisualizerProps = {data: unknown[]};

// ---------------------------------------------------------------------------
// Format detection
// ---------------------------------------------------------------------------

function isPgJsonExplain(data: unknown[]): data is PgExplainResult[] {
    return data.length > 0 && typeof data[0] === 'object' && data[0] !== null && 'Plan' in data[0];
}

function isMySqlExplain(data: unknown[]): data is MySqlExplainRow[] {
    return (
        data.length > 0 &&
        typeof data[0] === 'object' &&
        data[0] !== null &&
        'select_type' in data[0] &&
        'table' in data[0]
    );
}

function isDetailExplain(data: unknown[]): data is DetailRow[] {
    return data.length > 0 && typeof data[0] === 'object' && data[0] !== null && 'detail' in data[0];
}

// ---------------------------------------------------------------------------
// Normalization
// ---------------------------------------------------------------------------

type Counter = {value: number};

function normalizePgNode(node: PgPlanNode, counter: Counter): PlanTreeNode {
    const totalTime = node['Actual Total Time'] ?? 0;
    const childrenTime = (node.Plans ?? []).reduce((sum, child) => sum + (child['Actual Total Time'] ?? 0), 0);
    const exclusive = Math.max(0, totalTime - childrenTime);

    const children = (node.Plans ?? []).map((child) => normalizePgNode(child, counter));

    return {
        id: String(++counter.value),
        nodeType: node['Node Type'],
        table: node['Relation Name'] ?? node.Alias,
        index: node['Index Name'],
        condition: node['Index Cond'] ?? node.Filter ?? node['Hash Cond'] ?? node['Merge Cond'],
        cost: node['Total Cost'],
        rows: node['Plan Rows'],
        actualTime: totalTime,
        actualRows: node['Actual Rows'],
        loops: node['Actual Loops'],
        width: node['Plan Width'],
        sharedHitBlocks: node['Shared Hit Blocks'],
        sharedReadBlocks: node['Shared Read Blocks'],
        rowsRemovedByFilter: node['Rows Removed by Filter'],
        sortKey: node['Sort Key'],
        sortMethod: node['Sort Method'],
        exclusive,
        children,
        raw: node as unknown as Record<string, unknown>,
    };
}

function normalizeMySqlRows(rows: MySqlExplainRow[], counter: Counter): PlanTreeNode[] {
    return rows.map((row) => ({
        id: String(++counter.value),
        nodeType: row.type ?? 'unknown',
        table: row.table,
        index: row.key ?? undefined,
        condition: row.ref ?? undefined,
        cost: undefined,
        rows: row.rows,
        actualTime: undefined,
        actualRows: undefined,
        loops: undefined,
        width: undefined,
        sharedHitBlocks: undefined,
        sharedReadBlocks: undefined,
        rowsRemovedByFilter: undefined,
        sortKey: undefined,
        sortMethod: undefined,
        exclusive: undefined,
        extra: row.Extra,
        children: [],
        raw: {
            id: row.id,
            select_type: row.select_type,
            possible_keys: row.possible_keys,
            key_len: row.key_len,
            filtered: row.filtered,
            ...row,
        },
    }));
}

function parseDetailRows(rows: DetailRow[], counter: Counter): PlanTreeNode[] {
    return rows.map((row) => ({
        id: String(++counter.value),
        nodeType: row.detail.replace(/^[->\s]+/, '').split(/\s+/)[0] ?? 'Step',
        table: undefined,
        index: undefined,
        condition: undefined,
        cost: undefined,
        rows: undefined,
        actualTime: undefined,
        actualRows: undefined,
        loops: undefined,
        width: undefined,
        sharedHitBlocks: undefined,
        sharedReadBlocks: undefined,
        rowsRemovedByFilter: undefined,
        sortKey: undefined,
        sortMethod: undefined,
        exclusive: undefined,
        extra: row.detail,
        children: [],
        raw: row as unknown as Record<string, unknown>,
    }));
}

// ---------------------------------------------------------------------------
// Severity helpers
// ---------------------------------------------------------------------------

const NODE_TYPE_ICONS: Record<string, string> = {
    'Seq Scan': 'view_list',
    'Index Scan': 'search',
    'Index Only Scan': 'bolt',
    'Bitmap Heap Scan': 'grid_view',
    'Bitmap Index Scan': 'grid_on',
    'Nested Loop': 'loop',
    'Hash Join': 'join',
    'Merge Join': 'merge',
    Sort: 'sort',
    Hash: 'tag',
    Aggregate: 'functions',
    'Group Aggregate': 'group_work',
    HashAggregate: 'functions',
    Limit: 'vertical_align_bottom',
    Unique: 'fingerprint',
    Append: 'add',
    Result: 'output',
    Materialize: 'inventory',
    CTE_Scan: 'account_tree',
    Subquery_Scan: 'manage_search',
    // MySQL access types
    ALL: 'view_list',
    index: 'list',
    range: 'search',
    ref: 'link',
    eq_ref: 'link',
    const: 'bolt',
    system: 'bolt',
};

type Severity = 'good' | 'warning' | 'bad';

function getNodeSeverity(node: PlanTreeNode): Severity {
    const type = node.nodeType;
    // PostgreSQL node types
    if (type === 'Seq Scan' && (node.rows ?? 0) > 1000) return 'bad';
    if (type === 'Seq Scan') return 'warning';
    if (type.includes('Index')) return 'good';

    // MySQL access types
    if (type === 'ALL') return 'bad';
    if (type === 'index') return 'warning';
    if (type === 'const' || type === 'system' || type === 'eq_ref') return 'good';
    if (type === 'ref' || type === 'range') return 'good';

    return 'warning';
}

function severityColor(severity: Severity, palette: 'main' | 'bg', theme: ReturnType<typeof useTheme>): string {
    const map = {
        good: {main: theme.palette.success.main, bg: alpha(theme.palette.success.main, 0.08)},
        warning: {main: theme.palette.warning.main, bg: alpha(theme.palette.warning.main, 0.08)},
        bad: {main: theme.palette.error.main, bg: alpha(theme.palette.error.main, 0.08)},
    };
    return map[severity][palette];
}

// ---------------------------------------------------------------------------
// Tree helpers
// ---------------------------------------------------------------------------

function getMaxTime(nodes: PlanTreeNode[]): number {
    let max = 0;
    for (const node of nodes) {
        if ((node.exclusive ?? 0) > max) max = node.exclusive ?? 0;
        if ((node.actualTime ?? 0) > max) max = node.actualTime ?? 0;
        if (node.children.length > 0) {
            const childMax = getMaxTime(node.children);
            if (childMax > max) max = childMax;
        }
    }
    return max;
}

function getMaxRows(nodes: PlanTreeNode[]): number {
    let max = 0;
    for (const node of nodes) {
        const r = node.actualRows ?? node.rows ?? 0;
        if (r > max) max = r;
        if (node.children.length > 0) {
            const childMax = getMaxRows(node.children);
            if (childMax > max) max = childMax;
        }
    }
    return max;
}

// ---------------------------------------------------------------------------
// Sub-components
// ---------------------------------------------------------------------------

const PlanNodeCard = ({
    node,
    depth,
    maxTime,
    maxRows,
    isLast,
}: {
    node: PlanTreeNode;
    depth: number;
    maxTime: number;
    maxRows: number;
    isLast: boolean;
}) => {
    const theme = useTheme();
    const [expanded, setExpanded] = useState(false);
    const severity = getNodeSeverity(node);
    const sColor = severityColor(severity, 'main', theme);
    const bgColor = severityColor(severity, 'bg', theme);
    const icon = NODE_TYPE_ICONS[node.nodeType] ?? 'circle';

    const timePercent = maxTime > 0 ? ((node.exclusive ?? node.actualTime ?? 0) / maxTime) * 100 : 0;
    const rowPercent = maxRows > 0 ? ((node.actualRows ?? node.rows ?? 0) / maxRows) * 100 : 0;

    const hasDetails = Object.keys(node.raw).length > 0;
    const hasActuals = node.actualTime !== undefined;

    const toggleExpanded = useCallback(() => {
        if (hasDetails) setExpanded((prev) => !prev);
    }, [hasDetails]);

    const handleKeyDown = useCallback(
        (e: React.KeyboardEvent) => {
            if (hasDetails && (e.key === 'Enter' || e.key === ' ')) {
                e.preventDefault();
                setExpanded((prev) => !prev);
            }
        },
        [hasDetails],
    );

    return (
        <Box sx={{ml: depth > 0 ? 3 : 0, position: 'relative'}}>
            {depth > 0 && (
                <Box
                    sx={{
                        position: 'absolute',
                        left: -16,
                        top: 0,
                        bottom: isLast ? '50%' : 0,
                        width: '1px',
                        backgroundColor: theme.palette.divider,
                    }}
                />
            )}
            {depth > 0 && (
                <Box
                    sx={{
                        position: 'absolute',
                        left: -16,
                        top: 20,
                        width: 16,
                        height: '1px',
                        backgroundColor: theme.palette.divider,
                    }}
                />
            )}

            <Box
                role={hasDetails ? 'button' : undefined}
                tabIndex={hasDetails ? 0 : undefined}
                aria-expanded={hasDetails ? expanded : undefined}
                aria-label={`${node.nodeType}${node.table ? ` on ${node.table}` : ''}`}
                onClick={toggleExpanded}
                onKeyDown={handleKeyDown}
                sx={{
                    border: `1px solid ${alpha(sColor, 0.25)}`,
                    borderLeft: `3px solid ${sColor}`,
                    borderRadius: 1,
                    backgroundColor: bgColor,
                    p: 1,
                    mb: 1,
                    cursor: hasDetails ? 'pointer' : 'default',
                    transition: 'box-shadow 0.15s',
                    '&:hover': hasDetails ? {boxShadow: `0 0 0 1px ${alpha(sColor, 0.25)}`} : {},
                    '&:focus-visible': {outline: `2px solid ${theme.palette.primary.main}`, outlineOffset: 2},
                }}
            >
                <Box sx={{display: 'flex', alignItems: 'center', gap: 1, flexWrap: 'wrap'}}>
                    <Icon sx={{fontSize: 16, color: sColor}}>{icon}</Icon>
                    <Typography sx={{fontWeight: 700, fontSize: '12px'}}>{node.nodeType}</Typography>
                    {node.table && (
                        <Chip
                            label={node.table}
                            size="small"
                            sx={{fontSize: '10px', height: 18, borderRadius: 1, fontWeight: 600}}
                        />
                    )}
                    {node.index && (
                        <Chip
                            label={node.index}
                            size="small"
                            variant="outlined"
                            sx={{fontSize: '10px', height: 18, borderRadius: 1}}
                        />
                    )}
                    {node.extra && !node.table && (
                        <Typography
                            sx={{
                                fontSize: '11px',
                                color: 'text.secondary',
                                fontFamily: "'JetBrains Mono', monospace",
                                wordBreak: 'break-word',
                            }}
                        >
                            {node.extra}
                        </Typography>
                    )}
                    {hasDetails && (
                        <Icon sx={{fontSize: 14, color: 'text.disabled', ml: 'auto'}}>
                            {expanded ? 'expand_less' : 'expand_more'}
                        </Icon>
                    )}
                </Box>

                {/* Metrics row */}
                <Box sx={{display: 'flex', gap: 2, mt: 0.5, flexWrap: 'wrap', alignItems: 'center'}}>
                    {hasActuals && (
                        <Tooltip title="Exclusive time (this node only)" placement="top">
                            <Box sx={{display: 'flex', alignItems: 'center', gap: 0.5}}>
                                <Icon sx={{fontSize: 12, color: 'text.disabled'}}>timer</Icon>
                                <Typography sx={{fontSize: '11px', fontWeight: 600, color: sColor}}>
                                    {(node.exclusive ?? 0).toFixed(3)} ms
                                </Typography>
                            </Box>
                        </Tooltip>
                    )}
                    {node.cost !== undefined && (
                        <Tooltip title="Estimated total cost" placement="top">
                            <Box sx={{display: 'flex', alignItems: 'center', gap: 0.5}}>
                                <Icon sx={{fontSize: 12, color: 'text.disabled'}}>paid</Icon>
                                <Typography sx={{fontSize: '11px', color: 'text.secondary'}}>
                                    {node.cost.toFixed(2)}
                                </Typography>
                            </Box>
                        </Tooltip>
                    )}
                    {(node.actualRows ?? node.rows) !== undefined && (
                        <Tooltip
                            title={node.actualRows !== undefined ? 'Actual rows' : 'Estimated rows'}
                            placement="top"
                        >
                            <Box sx={{display: 'flex', alignItems: 'center', gap: 0.5}}>
                                <Icon sx={{fontSize: 12, color: 'text.disabled'}}>table_rows</Icon>
                                <Typography sx={{fontSize: '11px', color: 'text.secondary'}}>
                                    {node.actualRows ?? node.rows} rows
                                </Typography>
                            </Box>
                        </Tooltip>
                    )}
                    {node.loops !== undefined && node.loops > 1 && (
                        <Tooltip title="Loop count" placement="top">
                            <Box sx={{display: 'flex', alignItems: 'center', gap: 0.5}}>
                                <Icon sx={{fontSize: 12, color: 'text.disabled'}}>replay</Icon>
                                <Typography sx={{fontSize: '11px', color: 'text.secondary'}}>{node.loops}x</Typography>
                            </Box>
                        </Tooltip>
                    )}
                    {node.condition && (
                        <Typography
                            sx={{fontSize: '10px', color: 'text.disabled', fontFamily: "'JetBrains Mono', monospace"}}
                        >
                            {node.condition}
                        </Typography>
                    )}
                </Box>

                {/* Time bar */}
                {(timePercent > 0 || rowPercent > 0) && (
                    <Box sx={{display: 'flex', gap: 1, mt: 0.75, alignItems: 'center'}}>
                        {timePercent > 0 && (
                            <Box sx={{flex: 1, maxWidth: 200}}>
                                <LinearProgress
                                    variant="determinate"
                                    value={Math.min(timePercent, 100)}
                                    sx={{
                                        height: 4,
                                        borderRadius: 2,
                                        backgroundColor: theme.palette.divider,
                                        '& .MuiLinearProgress-bar': {backgroundColor: sColor},
                                    }}
                                />
                            </Box>
                        )}
                        {rowPercent > 0 && (
                            <Tooltip title="Rows proportion" placement="top">
                                <Box sx={{flex: 1, maxWidth: 120}}>
                                    <LinearProgress
                                        variant="determinate"
                                        value={Math.min(rowPercent, 100)}
                                        sx={{
                                            height: 4,
                                            borderRadius: 2,
                                            backgroundColor: theme.palette.divider,
                                            '& .MuiLinearProgress-bar': {backgroundColor: theme.palette.info.main},
                                        }}
                                    />
                                </Box>
                            </Tooltip>
                        )}
                    </Box>
                )}

                {/* Expanded raw details */}
                {expanded && (
                    <Box
                        sx={{
                            mt: 1,
                            pt: 1,
                            borderTop: `1px solid ${theme.palette.divider}`,
                            fontSize: '11px',
                            fontFamily: "'JetBrains Mono', monospace",
                            color: 'text.secondary',
                        }}
                    >
                        {Object.entries(node.raw)
                            .filter(([key]) => key !== 'Plans')
                            .map(([key, value]) => (
                                <Box key={key} sx={{display: 'flex', gap: 1, py: 0.25}}>
                                    <Typography
                                        sx={{
                                            fontSize: '11px',
                                            color: 'text.disabled',
                                            minWidth: 160,
                                            flexShrink: 0,
                                            fontFamily: 'inherit',
                                        }}
                                    >
                                        {key}:
                                    </Typography>
                                    <Typography sx={{fontSize: '11px', wordBreak: 'break-word', fontFamily: 'inherit'}}>
                                        {Array.isArray(value) ? value.join(', ') : String(value ?? 'null')}
                                    </Typography>
                                </Box>
                            ))}
                    </Box>
                )}
            </Box>

            {/* Children */}
            {node.children.map((child, i) => (
                <PlanNodeCard
                    key={child.id}
                    node={child}
                    depth={depth + 1}
                    maxTime={maxTime}
                    maxRows={maxRows}
                    isLast={i === node.children.length - 1}
                />
            ))}
        </Box>
    );
};

const PlanSummary = ({
    planningTime,
    executionTime,
    rootNode,
}: {
    planningTime?: number;
    executionTime?: number;
    rootNode?: PlanTreeNode;
}) => {
    const theme = useTheme();

    return (
        <Box
            sx={{
                display: 'flex',
                gap: 2,
                mb: 1.5,
                p: 1,
                borderRadius: 1,
                backgroundColor: theme.palette.action.hover,
                flexWrap: 'wrap',
            }}
        >
            {executionTime !== undefined && (
                <Box sx={{display: 'flex', alignItems: 'center', gap: 0.5}}>
                    <Icon sx={{fontSize: 14, color: 'text.disabled'}}>timer</Icon>
                    <Typography sx={{fontSize: '11px', color: 'text.secondary'}}>Execution:</Typography>
                    <Typography sx={{fontSize: '11px', fontWeight: 700}}>{executionTime.toFixed(3)} ms</Typography>
                </Box>
            )}
            {planningTime !== undefined && (
                <Box sx={{display: 'flex', alignItems: 'center', gap: 0.5}}>
                    <Icon sx={{fontSize: 14, color: 'text.disabled'}}>schedule</Icon>
                    <Typography sx={{fontSize: '11px', color: 'text.secondary'}}>Planning:</Typography>
                    <Typography sx={{fontSize: '11px', fontWeight: 700}}>{planningTime.toFixed(3)} ms</Typography>
                </Box>
            )}
            {rootNode?.cost !== undefined && (
                <Box sx={{display: 'flex', alignItems: 'center', gap: 0.5}}>
                    <Icon sx={{fontSize: 14, color: 'text.disabled'}}>paid</Icon>
                    <Typography sx={{fontSize: '11px', color: 'text.secondary'}}>Total cost:</Typography>
                    <Typography sx={{fontSize: '11px', fontWeight: 700}}>{rootNode.cost.toFixed(2)}</Typography>
                </Box>
            )}
            {rootNode?.actualRows !== undefined && (
                <Box sx={{display: 'flex', alignItems: 'center', gap: 0.5}}>
                    <Icon sx={{fontSize: 14, color: 'text.disabled'}}>table_rows</Icon>
                    <Typography sx={{fontSize: '11px', color: 'text.secondary'}}>Rows:</Typography>
                    <Typography sx={{fontSize: '11px', fontWeight: 700}}>{rootNode.actualRows}</Typography>
                </Box>
            )}
        </Box>
    );
};

// ---------------------------------------------------------------------------
// MySQL table view
// ---------------------------------------------------------------------------

const MYSQL_ACCESS_SEVERITY: Record<string, Severity> = {
    ALL: 'bad',
    index: 'warning',
    range: 'good',
    ref: 'good',
    eq_ref: 'good',
    const: 'good',
    system: 'good',
    NULL: 'good',
};

const MySqlExplainTable = ({nodes}: {nodes: PlanTreeNode[]}) => {
    const theme = useTheme();
    const maxRows = Math.max(...nodes.map((n) => n.rows ?? 0), 1);

    return (
        <Box sx={{overflowX: 'auto'}}>
            <Box
                component="table"
                sx={{
                    width: '100%',
                    borderCollapse: 'collapse',
                    fontSize: '12px',
                    fontFamily: "'JetBrains Mono', monospace",
                    '& th': {
                        textAlign: 'left',
                        p: 0.75,
                        fontSize: '10px',
                        fontWeight: 700,
                        color: 'text.disabled',
                        textTransform: 'uppercase',
                        borderBottom: `2px solid ${theme.palette.divider}`,
                    },
                    '& td': {p: 0.75, borderBottom: `1px solid ${theme.palette.divider}`, verticalAlign: 'top'},
                }}
            >
                <thead>
                    <tr>
                        <th scope="col">ID</th>
                        <th scope="col">Type</th>
                        <th scope="col">Table</th>
                        <th scope="col">Access</th>
                        <th scope="col">Key</th>
                        <th scope="col">Rows</th>
                        <th scope="col" style={{minWidth: 80}} aria-label="Rows proportion" />
                        <th scope="col">Filtered</th>
                        <th scope="col">Extra</th>
                    </tr>
                </thead>
                <tbody>
                    {nodes.map((node) => {
                        const severity = MYSQL_ACCESS_SEVERITY[node.nodeType] ?? 'warning';
                        const sColor = severityColor(severity, 'main', theme);
                        const bgColor = severityColor(severity, 'bg', theme);
                        const rowPercent = maxRows > 0 ? ((node.rows ?? 0) / maxRows) * 100 : 0;

                        return (
                            <tr key={node.id} style={{backgroundColor: bgColor}}>
                                <td>{(node.raw as Record<string, unknown>).id as number}</td>
                                <td>{(node.raw as Record<string, unknown>).select_type as string}</td>
                                <td style={{fontWeight: 600}}>{node.table}</td>
                                <td>
                                    <Chip
                                        label={node.nodeType}
                                        size="small"
                                        sx={{
                                            fontSize: '10px',
                                            height: 18,
                                            borderRadius: 1,
                                            fontWeight: 700,
                                            backgroundColor: `${sColor}20`,
                                            color: sColor,
                                            borderColor: sColor,
                                            border: '1px solid',
                                        }}
                                    />
                                </td>
                                <td>{node.index ?? '-'}</td>
                                <td>{node.rows ?? '-'}</td>
                                <td>
                                    <LinearProgress
                                        variant="determinate"
                                        value={Math.min(rowPercent, 100)}
                                        sx={{
                                            height: 4,
                                            borderRadius: 2,
                                            backgroundColor: theme.palette.divider,
                                            '& .MuiLinearProgress-bar': {backgroundColor: sColor},
                                        }}
                                    />
                                </td>
                                <td>
                                    {(node.raw as Record<string, unknown>).filtered !== undefined
                                        ? `${(node.raw as Record<string, unknown>).filtered}%`
                                        : '-'}
                                </td>
                                <td style={{color: theme.palette.text.secondary, fontSize: '11px'}}>
                                    {node.extra ?? '-'}
                                </td>
                            </tr>
                        );
                    })}
                </tbody>
            </Box>
        </Box>
    );
};

// ---------------------------------------------------------------------------
// Main component
// ---------------------------------------------------------------------------

export const ExplainPlanVisualizer = React.memo(({data}: ExplainPlanVisualizerProps) => {
    const plan = useMemo(() => {
        const counter: Counter = {value: 0};

        if (isPgJsonExplain(data)) {
            const result = data[0];
            const rootNode = normalizePgNode(result.Plan, counter);
            return {
                type: 'pg' as const,
                rootNode,
                planningTime: result['Planning Time'] as number | undefined,
                executionTime: result['Execution Time'] as number | undefined,
                maxTime: getMaxTime([rootNode]),
                maxRows: getMaxRows([rootNode]),
            };
        }

        if (isMySqlExplain(data)) {
            const nodes = normalizeMySqlRows(data, counter);
            return {type: 'mysql' as const, nodes};
        }

        if (isDetailExplain(data)) {
            const nodes = parseDetailRows(data, counter);
            return {type: 'detail' as const, nodes};
        }

        return {type: 'unknown' as const};
    }, [data]);

    if (plan.type === 'pg') {
        return (
            <Box>
                <PlanSummary
                    planningTime={plan.planningTime}
                    executionTime={plan.executionTime}
                    rootNode={plan.rootNode}
                />
                <PlanNodeCard node={plan.rootNode} depth={0} maxTime={plan.maxTime} maxRows={plan.maxRows} isLast />
            </Box>
        );
    }

    if (plan.type === 'mysql') {
        return <MySqlExplainTable nodes={plan.nodes} />;
    }

    if (plan.type === 'detail') {
        return (
            <Box>
                {plan.nodes.map((node) => (
                    <PlanNodeCard key={node.id} node={node} depth={0} maxTime={0} maxRows={0} isLast />
                ))}
            </Box>
        );
    }

    return null;
});
