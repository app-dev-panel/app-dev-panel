import {EmptyState} from '@app-dev-panel/sdk/Component/EmptyState';
import {FilterInput} from '@app-dev-panel/sdk/Component/FilterInput';
import {SectionTitle} from '@app-dev-panel/sdk/Component/SectionTitle';
import {primitives} from '@app-dev-panel/sdk/Component/Theme/tokens';
import {formatMicrotime} from '@app-dev-panel/sdk/Helper/formatDate';
import {Box, Chip, Collapse, Icon, IconButton, type Theme, Tooltip, Typography} from '@mui/material';
import {styled, useTheme} from '@mui/material/styles';
import {useDeferredValue, useMemo, useState} from 'react';

type SpanEvent = {name: string; timestamp: number; attributes: Record<string, unknown>};
type SpanLink = {traceId: string; spanId: string; attributes: Record<string, unknown>};

type SpanEntry = {
    traceId: string;
    spanId: string;
    parentSpanId: string | null;
    operationName: string;
    serviceName: string;
    startTime: number;
    endTime: number;
    duration: number;
    status: string;
    statusMessage: string;
    kind: string;
    attributes: Record<string, unknown>;
    events: SpanEvent[];
    links: SpanLink[];
    resourceAttributes: Record<string, unknown>;
};

type OpenTelemetryData = {spans: SpanEntry[]; traceCount: number; spanCount: number; errorCount: number};

type OpenTelemetryPanelProps = {data: OpenTelemetryData};

// ---------------------------------------------------------------------------
// Helpers
// ---------------------------------------------------------------------------

const statusColor = (status: string, theme: Theme): string => {
    switch (status) {
        case 'ERROR':
            return theme.palette.error.main;
        case 'OK':
            return theme.palette.success.main;
        default:
            return theme.palette.text.disabled;
    }
};

const kindLabel = (kind: string): string => {
    switch (kind) {
        case 'SERVER':
            return 'SRV';
        case 'CLIENT':
            return 'CLI';
        case 'PRODUCER':
            return 'PRD';
        case 'CONSUMER':
            return 'CNS';
        case 'INTERNAL':
            return 'INT';
        default:
            return kind.slice(0, 3);
    }
};

const formatDuration = (ms: number): string => {
    if (ms >= 1000) return `${(ms / 1000).toFixed(2)} s`;
    if (ms >= 1) return `${ms.toFixed(1)} ms`;
    return `${(ms * 1000).toFixed(0)} µs`;
};

const durationColor = (ms: number, theme: Theme): string => {
    if (ms >= 1000) return theme.palette.error.main;
    if (ms >= 300) return theme.palette.warning.main;
    return theme.palette.text.disabled;
};

// ---------------------------------------------------------------------------
// Styled components
// ---------------------------------------------------------------------------

const SpanRow = styled(Box, {shouldForwardProp: (p) => p !== 'expanded' && p !== 'depth'})<{
    expanded?: boolean;
    depth: number;
}>(({theme, expanded, depth}) => ({
    display: 'flex',
    alignItems: 'center',
    gap: theme.spacing(1),
    padding: theme.spacing(0.75, 1.5),
    paddingLeft: theme.spacing(1.5 + depth * 2.5),
    borderBottom: `1px solid ${theme.palette.divider}`,
    cursor: 'pointer',
    transition: 'background-color 0.1s ease',
    backgroundColor: expanded ? theme.palette.action.hover : 'transparent',
    '&:hover': {backgroundColor: theme.palette.action.hover},
}));

const OperationCell = styled(Typography)({
    fontFamily: primitives.fontFamilyMono,
    fontSize: '12px',
    flex: 1,
    wordBreak: 'break-all',
});

const DurationCell = styled(Typography)({
    fontFamily: primitives.fontFamilyMono,
    fontSize: '11px',
    flexShrink: 0,
    textAlign: 'right',
    width: 80,
});

const TimeCell = styled(Typography)({
    fontFamily: primitives.fontFamilyMono,
    fontSize: '11px',
    flexShrink: 0,
    width: 100,
    color: 'text.secondary',
});

const DetailBox = styled(Box)(({theme}) => ({
    padding: theme.spacing(1.5, 1.5, 1.5, 4),
    backgroundColor: theme.palette.action.hover,
    borderBottom: `1px solid ${theme.palette.divider}`,
    fontSize: '12px',
}));

const AttrTable = styled('table')(({theme}) => ({
    borderCollapse: 'collapse',
    width: '100%',
    '& td, & th': {
        padding: theme.spacing(0.25, 1),
        textAlign: 'left',
        fontFamily: primitives.fontFamilyMono,
        fontSize: '11px',
        borderBottom: `1px solid ${theme.palette.divider}`,
    },
    '& th': {fontWeight: 600, width: '30%', color: theme.palette.text.secondary},
}));

const WaterfallBarContainer = styled(Box)({flex: 1, position: 'relative', height: 14, minWidth: 120});

const WaterfallBar = styled(Box)(({theme}) => ({
    position: 'absolute',
    top: 2,
    height: 10,
    borderRadius: 2,
    backgroundColor: theme.palette.primary.main,
    minWidth: 2,
}));

// ---------------------------------------------------------------------------
// Waterfall: build tree and bar positions
// ---------------------------------------------------------------------------

type SpanNode = {span: SpanEntry; children: SpanNode[]; depth: number};

function buildTree(spans: SpanEntry[]): SpanNode[] {
    const byId = new Map<string, SpanNode>();
    const roots: SpanNode[] = [];

    for (const span of spans) {
        byId.set(span.spanId, {span, children: [], depth: 0});
    }

    for (const node of byId.values()) {
        if (node.span.parentSpanId && byId.has(node.span.parentSpanId)) {
            byId.get(node.span.parentSpanId)?.children.push(node);
        } else {
            roots.push(node);
        }
    }

    const setDepths = (nodes: SpanNode[], depth: number) => {
        for (const n of nodes) {
            n.depth = depth;
            n.children.sort((a, b) => a.span.startTime - b.span.startTime);
            setDepths(n.children, depth + 1);
        }
    };
    roots.sort((a, b) => a.span.startTime - b.span.startTime);
    setDepths(roots, 0);

    return roots;
}

function flattenTree(nodes: SpanNode[]): SpanNode[] {
    const result: SpanNode[] = [];
    const walk = (n: SpanNode) => {
        result.push(n);
        n.children.forEach(walk);
    };
    nodes.forEach(walk);
    return result;
}

// ---------------------------------------------------------------------------
// Trace group component
// ---------------------------------------------------------------------------

function TraceGroup({spans, traceId}: {spans: SpanEntry[]; traceId: string}) {
    const theme = useTheme();
    const [expandedSpanId, setExpandedSpanId] = useState<string | null>(null);

    const tree = useMemo(() => buildTree(spans), [spans]);
    const flat = useMemo(() => flattenTree(tree), [tree]);

    const traceStart = useMemo(() => Math.min(...spans.map((s) => s.startTime)), [spans]);
    const traceEnd = useMemo(() => Math.max(...spans.map((s) => s.endTime)), [spans]);
    const traceDuration = traceEnd - traceStart;

    const rootOp = tree[0]?.span.operationName ?? traceId;
    const errorCount = spans.filter((s) => s.status === 'ERROR').length;

    const titleAction =
        errorCount > 0 ? (
            <Chip label={`${errorCount} error${errorCount !== 1 ? 's' : ''}`} size="small" color="error" />
        ) : undefined;

    return (
        <Box>
            <SectionTitle action={titleAction}>
                {`${rootOp} — ${spans.length} span${spans.length !== 1 ? 's' : ''} — ${formatDuration(traceDuration * 1000)}`}
            </SectionTitle>

            {flat.map((node) => {
                const {span} = node;
                const expanded = expandedSpanId === span.spanId;
                const barLeft = traceDuration > 0 ? ((span.startTime - traceStart) / traceDuration) * 100 : 0;
                const barWidth = traceDuration > 0 ? Math.max((span.duration / 1000 / traceDuration) * 100, 0.5) : 100;

                return (
                    <Box key={span.spanId}>
                        <SpanRow
                            expanded={expanded}
                            depth={node.depth}
                            onClick={() => setExpandedSpanId(expanded ? null : span.spanId)}
                        >
                            <Chip
                                label={kindLabel(span.kind)}
                                size="small"
                                variant="outlined"
                                sx={{fontFamily: primitives.fontFamilyMono, fontSize: '10px', height: 20, minWidth: 36}}
                            />
                            <Chip
                                label={span.status}
                                size="small"
                                sx={{
                                    backgroundColor: statusColor(span.status, theme),
                                    color: theme.palette.common.white,
                                    fontFamily: primitives.fontFamilyMono,
                                    fontSize: '10px',
                                    height: 20,
                                    minWidth: 48,
                                }}
                            />
                            <OperationCell>{span.operationName}</OperationCell>
                            <Tooltip title={`${span.serviceName}`}>
                                <Typography
                                    sx={{
                                        fontSize: '10px',
                                        color: theme.palette.text.secondary,
                                        flexShrink: 0,
                                        maxWidth: 80,
                                        overflow: 'hidden',
                                        textOverflow: 'ellipsis',
                                    }}
                                >
                                    {span.serviceName}
                                </Typography>
                            </Tooltip>
                            <WaterfallBarContainer>
                                <WaterfallBar sx={{left: `${barLeft}%`, width: `${barWidth}%`}} />
                            </WaterfallBarContainer>
                            <DurationCell sx={{color: durationColor(span.duration, theme)}}>
                                {formatDuration(span.duration)}
                            </DurationCell>
                            <TimeCell>{formatMicrotime(span.startTime)}</TimeCell>
                            <IconButton size="small">
                                <Icon sx={{fontSize: 18}}>{expanded ? 'expand_less' : 'expand_more'}</Icon>
                            </IconButton>
                        </SpanRow>

                        <Collapse in={expanded}>
                            <DetailBox>
                                <SpanDetail span={span} />
                            </DetailBox>
                        </Collapse>
                    </Box>
                );
            })}
        </Box>
    );
}

// ---------------------------------------------------------------------------
// Span detail
// ---------------------------------------------------------------------------

function SpanDetail({span}: {span: SpanEntry}) {
    const theme = useTheme();

    return (
        <Box sx={{display: 'flex', flexDirection: 'column', gap: 1.5}}>
            <Box>
                <Typography variant="subtitle2" sx={{mb: 0.5}}>
                    Info
                </Typography>
                <AttrTable>
                    <tbody>
                        <tr>
                            <th>Trace ID</th>
                            <td>{span.traceId}</td>
                        </tr>
                        <tr>
                            <th>Span ID</th>
                            <td>{span.spanId}</td>
                        </tr>
                        {span.parentSpanId && (
                            <tr>
                                <th>Parent Span ID</th>
                                <td>{span.parentSpanId}</td>
                            </tr>
                        )}
                        <tr>
                            <th>Service</th>
                            <td>{span.serviceName}</td>
                        </tr>
                        <tr>
                            <th>Kind</th>
                            <td>{span.kind}</td>
                        </tr>
                        <tr>
                            <th>Duration</th>
                            <td>{formatDuration(span.duration)}</td>
                        </tr>
                        {span.statusMessage && (
                            <tr>
                                <th>Status Message</th>
                                <td style={{color: theme.palette.error.main}}>{span.statusMessage}</td>
                            </tr>
                        )}
                    </tbody>
                </AttrTable>
            </Box>

            {Object.keys(span.attributes).length > 0 && (
                <Box>
                    <Typography variant="subtitle2" sx={{mb: 0.5}}>
                        Attributes
                    </Typography>
                    <AttrTable>
                        <tbody>
                            {Object.entries(span.attributes).map(([key, value]) => (
                                <tr key={key}>
                                    <th>{key}</th>
                                    <td>{typeof value === 'object' ? JSON.stringify(value) : String(value)}</td>
                                </tr>
                            ))}
                        </tbody>
                    </AttrTable>
                </Box>
            )}

            {span.events.length > 0 && (
                <Box>
                    <Typography variant="subtitle2" sx={{mb: 0.5}}>
                        Events ({span.events.length})
                    </Typography>
                    <AttrTable>
                        <thead>
                            <tr>
                                <th>Name</th>
                                <th>Timestamp</th>
                            </tr>
                        </thead>
                        <tbody>
                            {span.events.map((evt, i) => (
                                <tr key={i}>
                                    <td>{evt.name}</td>
                                    <td>{formatMicrotime(evt.timestamp)}</td>
                                </tr>
                            ))}
                        </tbody>
                    </AttrTable>
                </Box>
            )}

            {span.links.length > 0 && (
                <Box>
                    <Typography variant="subtitle2" sx={{mb: 0.5}}>
                        Links ({span.links.length})
                    </Typography>
                    <AttrTable>
                        <thead>
                            <tr>
                                <th>Trace ID</th>
                                <th>Span ID</th>
                            </tr>
                        </thead>
                        <tbody>
                            {span.links.map((link, i) => (
                                <tr key={i}>
                                    <td>{link.traceId}</td>
                                    <td>{link.spanId}</td>
                                </tr>
                            ))}
                        </tbody>
                    </AttrTable>
                </Box>
            )}

            {Object.keys(span.resourceAttributes).length > 0 && (
                <Box>
                    <Typography variant="subtitle2" sx={{mb: 0.5}}>
                        Resource Attributes
                    </Typography>
                    <AttrTable>
                        <tbody>
                            {Object.entries(span.resourceAttributes).map(([key, value]) => (
                                <tr key={key}>
                                    <th>{key}</th>
                                    <td>{typeof value === 'object' ? JSON.stringify(value) : String(value)}</td>
                                </tr>
                            ))}
                        </tbody>
                    </AttrTable>
                </Box>
            )}
        </Box>
    );
}

// ---------------------------------------------------------------------------
// Main Panel
// ---------------------------------------------------------------------------

export const OpenTelemetryPanel = ({data}: OpenTelemetryPanelProps) => {
    const [filter, setFilter] = useState('');
    const deferredFilter = useDeferredValue(filter);

    const spans = data?.spans ?? [];

    const traceGroups = useMemo(() => {
        const groups = new Map<string, SpanEntry[]>();
        for (const span of spans) {
            const group = groups.get(span.traceId) ?? [];
            group.push(span);
            groups.set(span.traceId, group);
        }
        return groups;
    }, [spans]);

    const filteredTraces = useMemo(() => {
        if (!deferredFilter) return [...traceGroups.entries()];
        const q = deferredFilter.toLowerCase();
        return [...traceGroups.entries()].filter(([traceId, traceSpans]) =>
            traceSpans.some(
                (s) =>
                    s.operationName.toLowerCase().includes(q) ||
                    s.serviceName.toLowerCase().includes(q) ||
                    s.traceId.toLowerCase().includes(q) ||
                    s.status.toLowerCase().includes(q),
            ),
        );
    }, [traceGroups, deferredFilter]);

    if (!spans.length) {
        return <EmptyState icon="account_tree" title="No traces collected" />;
    }

    const summaryAction = (
        <Box sx={{display: 'flex', alignItems: 'center', gap: 1}}>
            {(data?.errorCount ?? 0) > 0 && <Chip label={`${data.errorCount} errors`} size="small" color="error" />}
            <FilterInput value={filter} onChange={setFilter} placeholder="Filter by operation, service, ID..." />
        </Box>
    );

    return (
        <Box>
            <SectionTitle action={summaryAction}>
                {`${filteredTraces.length} trace${filteredTraces.length !== 1 ? 's' : ''} — ${spans.length} span${spans.length !== 1 ? 's' : ''}`}
            </SectionTitle>

            {filteredTraces.map(([traceId, traceSpans]) => (
                <TraceGroup key={traceId} traceId={traceId} spans={traceSpans} />
            ))}
        </Box>
    );
};
