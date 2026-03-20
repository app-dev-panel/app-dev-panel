import {EmptyState} from '@app-dev-panel/sdk/Component/EmptyState';
import {FilterInput} from '@app-dev-panel/sdk/Component/FilterInput';
import {JsonRenderer} from '@app-dev-panel/sdk/Component/JsonRenderer';
import {SectionTitle} from '@app-dev-panel/sdk/Component/SectionTitle';
import {primitives} from '@app-dev-panel/sdk/Component/Theme/tokens';
import {Box, Chip, Collapse, Icon, LinearProgress, Tooltip, Typography} from '@mui/material';
import {styled, useTheme} from '@mui/material/styles';
import {useDeferredValue, useMemo, useState} from 'react';

type CacheOperation = {pool: string; operation: string; key: string; hit: boolean; duration: number; value?: unknown};

type CacheData = {operations: CacheOperation[]; hits: number; misses: number; totalOperations: number};

type CachePanelProps = {data: CacheData};

// ---------------------------------------------------------------------------
// Styled components
// ---------------------------------------------------------------------------

const SummaryGrid = styled(Box)(({theme}) => ({
    display: 'grid',
    gridTemplateColumns: 'repeat(auto-fill, minmax(160px, 1fr))',
    gap: theme.spacing(2),
    marginBottom: theme.spacing(3),
}));

const SummaryCard = styled(Box)(({theme}) => ({
    padding: theme.spacing(2),
    borderRadius: theme.shape.borderRadius * 1.5,
    border: `1px solid ${theme.palette.divider}`,
    backgroundColor: theme.palette.background.paper,
}));

const SummaryLabel = styled(Typography)(({theme}) => ({
    fontSize: '11px',
    fontWeight: 600,
    textTransform: 'uppercase' as const,
    letterSpacing: '0.5px',
    color: theme.palette.text.disabled,
    marginBottom: theme.spacing(0.5),
}));

const SummaryValue = styled(Typography)({fontFamily: primitives.fontFamilyMono, fontWeight: 700, fontSize: '22px'});

const OperationRow = styled(Box, {shouldForwardProp: (p) => p !== 'expanded'})<{expanded?: boolean}>(
    ({theme, expanded}) => ({
        display: 'flex',
        alignItems: 'center',
        gap: theme.spacing(1.5),
        padding: theme.spacing(1, 1.5),
        borderBottom: `1px solid ${theme.palette.divider}`,
        cursor: 'pointer',
        transition: 'background-color 0.1s ease',
        backgroundColor: expanded ? theme.palette.action.hover : 'transparent',
        '&:hover': {backgroundColor: theme.palette.action.hover},
    }),
);

const KeyCell = styled(Typography)({
    fontFamily: primitives.fontFamilyMono,
    fontSize: '12px',
    flex: 1,
    wordBreak: 'break-all',
    minWidth: 0,
});

const DurationCell = styled(Typography)({
    fontFamily: primitives.fontFamilyMono,
    fontSize: '11px',
    flexShrink: 0,
    width: 80,
    textAlign: 'right',
});

const PoolChip = styled(Chip)(({theme}) => ({
    fontWeight: 600,
    fontSize: '10px',
    height: 20,
    borderRadius: theme.shape.borderRadius * 0.5,
}));

const ValueBox = styled(Box)(({theme}) => ({
    padding: theme.spacing(1.5, 2, 1.5, 5.5),
    borderBottom: `1px solid ${theme.palette.divider}`,
    backgroundColor: theme.palette.action.hover,
}));

// ---------------------------------------------------------------------------
// Helpers
// ---------------------------------------------------------------------------

const formatDuration = (seconds: number): string => {
    if (seconds === 0) return '0 ms';
    if (seconds < 0.001) return `${(seconds * 1000000).toFixed(0)} us`;
    if (seconds < 1) return `${(seconds * 1000).toFixed(2)} ms`;
    return `${seconds.toFixed(3)} s`;
};

const operationIcon = (op: string): string => {
    switch (op) {
        case 'get':
            return 'search';
        case 'set':
            return 'edit';
        case 'delete':
            return 'delete';
        case 'has':
            return 'help_outline';
        case 'clear':
            return 'clear_all';
        default:
            return 'cached';
    }
};

// ---------------------------------------------------------------------------
// Pool breakdown sub-component
// ---------------------------------------------------------------------------

type PoolStat = {pool: string; gets: number; sets: number; deletes: number; hits: number; misses: number};

const PoolBreakdown = ({operations}: {operations: CacheOperation[]}) => {
    const theme = useTheme();
    const pools = useMemo(() => {
        const map = new Map<string, PoolStat>();
        for (const op of operations) {
            let stat = map.get(op.pool);
            if (!stat) {
                stat = {pool: op.pool, gets: 0, sets: 0, deletes: 0, hits: 0, misses: 0};
                map.set(op.pool, stat);
            }
            if (op.operation === 'get') {
                stat.gets++;
                if (op.hit) stat.hits++;
                else stat.misses++;
            } else if (op.operation === 'set') {
                stat.sets++;
            } else if (op.operation === 'delete') {
                stat.deletes++;
            }
        }
        return [...map.values()];
    }, [operations]);

    if (pools.length <= 1) return null;

    return (
        <Box sx={{mb: 3}}>
            <SectionTitle>Pools</SectionTitle>
            <Box sx={{display: 'flex', gap: 2, flexWrap: 'wrap', mt: 1}}>
                {pools.map((p) => {
                    const hitRate = p.gets > 0 ? Math.round((p.hits / p.gets) * 100) : null;
                    return (
                        <Box
                            key={p.pool}
                            sx={{
                                flex: '1 1 200px',
                                p: 1.5,
                                borderRadius: 1.5,
                                border: `1px solid ${theme.palette.divider}`,
                                backgroundColor: theme.palette.background.paper,
                            }}
                        >
                            <Typography sx={{fontWeight: 600, fontSize: '13px', mb: 0.5}}>{p.pool}</Typography>
                            <Box sx={{display: 'flex', gap: 2, fontSize: '12px', color: 'text.secondary'}}>
                                <span>{p.gets} gets</span>
                                <span>{p.sets} sets</span>
                                <span>{p.deletes} del</span>
                                {hitRate !== null && (
                                    <span
                                        style={{
                                            color:
                                                hitRate >= 80 ? theme.palette.success.main : theme.palette.warning.main,
                                        }}
                                    >
                                        {hitRate}% hit
                                    </span>
                                )}
                            </Box>
                        </Box>
                    );
                })}
            </Box>
        </Box>
    );
};

// ---------------------------------------------------------------------------
// CachePanel
// ---------------------------------------------------------------------------

export const CachePanel = ({data}: CachePanelProps) => {
    const theme = useTheme();
    const [filter, setFilter] = useState('');
    const deferredFilter = useDeferredValue(filter);
    const [expandedIndex, setExpandedIndex] = useState<number | null>(null);

    if (!data || data.totalOperations === 0) {
        return (
            <EmptyState
                icon="cached"
                title="No cache operations"
                description="No cache operations were recorded during this request."
            />
        );
    }

    const {operations, hits, misses, totalOperations} = data;
    const hitRate = hits + misses > 0 ? Math.round((hits / (hits + misses)) * 100) : 0;
    const totalDuration = operations.reduce((sum, op) => sum + op.duration, 0);

    const filtered = deferredFilter
        ? operations.filter((op) => {
              const lower = deferredFilter.toLowerCase();
              return (
                  op.key.toLowerCase().includes(lower) ||
                  op.operation.toLowerCase().includes(lower) ||
                  op.pool.toLowerCase().includes(lower)
              );
          })
        : operations;

    return (
        <Box>
            {/* Summary cards */}
            <SummaryGrid>
                <SummaryCard>
                    <SummaryLabel>Total Operations</SummaryLabel>
                    <SummaryValue sx={{color: 'primary.main'}}>{totalOperations}</SummaryValue>
                </SummaryCard>
                <SummaryCard>
                    <SummaryLabel>Hit Rate</SummaryLabel>
                    <SummaryValue
                        sx={{color: hitRate >= 80 ? 'success.main' : hitRate >= 50 ? 'warning.main' : 'error.main'}}
                    >
                        {hitRate}%
                    </SummaryValue>
                    <LinearProgress
                        variant="determinate"
                        value={hitRate}
                        sx={{
                            mt: 1,
                            height: 4,
                            borderRadius: 2,
                            backgroundColor: theme.palette.action.hover,
                            '& .MuiLinearProgress-bar': {
                                backgroundColor:
                                    hitRate >= 80
                                        ? theme.palette.success.main
                                        : hitRate >= 50
                                          ? theme.palette.warning.main
                                          : theme.palette.error.main,
                                borderRadius: 2,
                            },
                        }}
                    />
                </SummaryCard>
                <SummaryCard>
                    <SummaryLabel>Hits</SummaryLabel>
                    <SummaryValue sx={{color: 'success.main'}}>{hits}</SummaryValue>
                </SummaryCard>
                <SummaryCard>
                    <SummaryLabel>Misses</SummaryLabel>
                    <SummaryValue sx={{color: misses > 0 ? 'warning.main' : 'text.disabled'}}>{misses}</SummaryValue>
                </SummaryCard>
                <SummaryCard>
                    <SummaryLabel>Total Time</SummaryLabel>
                    <SummaryValue sx={{color: 'text.primary', fontSize: '18px'}}>
                        {formatDuration(totalDuration)}
                    </SummaryValue>
                </SummaryCard>
            </SummaryGrid>

            {/* Pool breakdown */}
            <PoolBreakdown operations={operations} />

            {/* Operations table */}
            <SectionTitle
                action={<FilterInput value={filter} onChange={setFilter} placeholder="Filter operations..." />}
            >{`${filtered.length} operations`}</SectionTitle>

            {filtered.map((op, index) => {
                const isHit = op.operation === 'get' && op.hit;
                const isMiss = op.operation === 'get' && !op.hit;
                const hasValue = op.value !== null && op.value !== undefined;
                const expanded = expandedIndex === index;
                return (
                    <Box key={index}>
                        <OperationRow
                            expanded={expanded}
                            onClick={() => hasValue && setExpandedIndex(expanded ? null : index)}
                            sx={{cursor: hasValue ? 'pointer' : 'default'}}
                        >
                            <Tooltip title={op.operation} placement="top">
                                <Icon sx={{fontSize: 16, color: 'text.disabled', flexShrink: 0}}>
                                    {operationIcon(op.operation)}
                                </Icon>
                            </Tooltip>
                            <Chip
                                label={op.operation.toUpperCase()}
                                size="small"
                                sx={{
                                    fontWeight: 600,
                                    fontSize: '10px',
                                    height: 20,
                                    minWidth: 50,
                                    borderRadius: 0.5,
                                    backgroundColor: isHit
                                        ? theme.palette.success.light
                                        : isMiss
                                          ? theme.palette.warning.light
                                          : theme.palette.action.hover,
                                    color: isHit
                                        ? theme.palette.success.main
                                        : isMiss
                                          ? theme.palette.warning.main
                                          : theme.palette.text.secondary,
                                }}
                            />
                            {op.operation === 'get' && (
                                <Chip
                                    label={op.hit ? 'HIT' : 'MISS'}
                                    size="small"
                                    sx={{
                                        fontWeight: 700,
                                        fontSize: '9px',
                                        height: 18,
                                        minWidth: 40,
                                        borderRadius: 0.5,
                                        backgroundColor: op.hit
                                            ? theme.palette.success.main
                                            : theme.palette.warning.main,
                                        color: 'common.white',
                                    }}
                                />
                            )}
                            <PoolChip label={op.pool} size="small" variant="outlined" />
                            <KeyCell sx={{color: 'text.primary'}}>{op.key}</KeyCell>
                            <DurationCell sx={{color: 'text.disabled'}}>{formatDuration(op.duration)}</DurationCell>
                            {hasValue && (
                                <Icon sx={{fontSize: 16, color: 'text.disabled', flexShrink: 0}}>
                                    {expanded ? 'expand_less' : 'expand_more'}
                                </Icon>
                            )}
                        </OperationRow>
                        {hasValue && (
                            <Collapse in={expanded}>
                                <ValueBox>
                                    <Typography
                                        sx={{
                                            fontSize: '11px',
                                            fontWeight: 600,
                                            textTransform: 'uppercase',
                                            letterSpacing: '0.5px',
                                            color: 'text.disabled',
                                            mb: 0.5,
                                        }}
                                    >
                                        Value
                                    </Typography>
                                    <JsonRenderer value={op.value} depth={3} />
                                </ValueBox>
                            </Collapse>
                        )}
                    </Box>
                );
            })}
        </Box>
    );
};
