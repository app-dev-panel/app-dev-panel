import {useGetConfigurationQuery, useLazyGetObjectQuery} from '@app-dev-panel/panel/Module/Inspector/API/Inspector';
import {DataContext} from '@app-dev-panel/panel/Module/Inspector/Context/DataContext';
import {CodeHighlight} from '@app-dev-panel/sdk/Component/CodeHighlight';
import {EmptyState} from '@app-dev-panel/sdk/Component/EmptyState';
import {FileLink} from '@app-dev-panel/sdk/Component/FileLink';
import {FilterInput} from '@app-dev-panel/sdk/Component/FilterInput';
import {FullScreenCircularProgress} from '@app-dev-panel/sdk/Component/FullScreenCircularProgress';
import {JsonRenderer} from '@app-dev-panel/sdk/Component/JsonRenderer';
import {QueryErrorState} from '@app-dev-panel/sdk/Component/QueryErrorState';
import {searchVariants} from '@app-dev-panel/sdk/Helper/layoutTranslit';
import {regexpQuote} from '@app-dev-panel/sdk/Helper/regexpQuote';
import {ChevronRight, ContentCopy, DataObject, Download, ErrorOutline} from '@mui/icons-material';
import {Box, CircularProgress, Collapse, IconButton, TablePagination, Tooltip, Typography} from '@mui/material';
import {alpha, styled} from '@mui/material/styles';
import clipboardCopy from 'clipboard-copy';
import {useCallback, useContext, useEffect, useMemo, useState} from 'react';
import {Link as RouterLink, useSearchParams} from 'react-router';

// ---------------------------------------------------------------------------
// Types
// ---------------------------------------------------------------------------

type DefinitionEntry = {id: string; value: unknown};

type DefinitionKind = 'class' | 'factory' | 'object' | 'primitive';

// ---------------------------------------------------------------------------
// Classifiers
// ---------------------------------------------------------------------------

const REGEXP_PHP_FUNCTION = /(static\s+)?(function|fn)\s*\(.*\).*((\{.*})|(=>.*))/s;
const REGEXP_CLASS_NAME = /^[\w\\]+$/i;

type ClosureDescriptor = {
    __closure: true;
    source: string;
    file: string | null;
    startLine: number | null;
    endLine: number | null;
};

const isClosureDescriptor = (v: unknown): v is ClosureDescriptor =>
    typeof v === 'object' &&
    v !== null &&
    (v as Record<string, unknown>).__closure === true &&
    typeof (v as Record<string, unknown>).source === 'string';

type FactoryMeta = {source: string; file: string | null; startLine: number | null; endLine: number | null};

const getFactoryMeta = (value: unknown): FactoryMeta => {
    if (isClosureDescriptor(value)) {
        return {source: value.source, file: value.file, startLine: value.startLine, endLine: value.endLine};
    }
    return {source: typeof value === 'string' ? value : '', file: null, startLine: null, endLine: null};
};

const detectKind = (value: unknown): DefinitionKind => {
    if (isClosureDescriptor(value)) return 'factory';
    if (typeof value === 'string') {
        if (REGEXP_PHP_FUNCTION.test(value)) return 'factory';
        if (REGEXP_CLASS_NAME.test(value)) return 'class';
        return 'primitive';
    }
    if (value && typeof value === 'object') return 'object';
    return 'primitive';
};

const summarizeFactory = (source: string): string => {
    const returnNew = source.match(/return\s+new\s+(\\?[\w\\]+)/);
    if (returnNew) return `new ${returnNew[1]}`;
    const arrowNew = source.match(/=>\s*new\s+(\\?[\w\\]+)/);
    if (arrowNew) return `new ${arrowNew[1]}`;
    const staticCall = source.match(/return\s+(\\?[\w\\]+)::(\w+)\(/);
    if (staticCall) return `${staticCall[1]}::${staticCall[2]}()`;
    return 'closure';
};

type ObjectConfig = {explicitClass: string | null; calls: string[]; properties: string[]; other: string[]};

const parseObjectConfig = (value: unknown): ObjectConfig => {
    const config: ObjectConfig = {explicitClass: null, calls: [], properties: [], other: []};
    if (!value || typeof value !== 'object' || Array.isArray(value)) return config;
    for (const [key, val] of Object.entries(value as Record<string, unknown>)) {
        if (key === 'class' && typeof val === 'string') {
            config.explicitClass = val;
        } else if (key.endsWith('()')) {
            config.calls.push(key);
        } else if (key.startsWith('$')) {
            config.properties.push(key);
        } else {
            config.other.push(key);
        }
    }
    return config;
};

const countLines = (source: string): number => source.split('\n').length;

// ---------------------------------------------------------------------------
// Styled components
// ---------------------------------------------------------------------------

const SearchRow = styled(Box)(({theme}) => ({
    display: 'flex',
    alignItems: 'center',
    gap: theme.spacing(1.5),
    padding: theme.spacing(2),
}));

const ListContainer = styled(Box)(({theme}) => ({
    border: `1px solid ${theme.palette.divider}`,
    borderRadius: theme.shape.borderRadius,
    overflow: 'hidden',
}));

const ListHeader = styled(Box)(({theme}) => ({
    display: 'flex',
    alignItems: 'center',
    gap: theme.spacing(2),
    padding: theme.spacing(1, 2),
    backgroundColor: theme.palette.action.hover,
    borderBottom: `1px solid ${theme.palette.divider}`,
}));

const HeaderLabel = styled(Typography)(({theme}) => ({
    fontSize: '11px',
    fontWeight: 600,
    textTransform: 'uppercase',
    letterSpacing: '0.05em',
    color: theme.palette.text.disabled,
}));

const Row = styled(Box, {shouldForwardProp: (p) => p !== 'expanded'})<{expanded?: boolean}>(({theme, expanded}) => ({
    borderBottom: `1px solid ${theme.palette.divider}`,
    '&:last-child': {borderBottom: 'none'},
    backgroundColor: expanded ? theme.palette.action.hover : 'transparent',
    transition: 'background-color 120ms',
}));

const RowHead = styled(Box, {shouldForwardProp: (p) => p !== 'clickable'})<{clickable?: boolean}>(
    ({theme, clickable}) => ({
        display: 'flex',
        alignItems: 'flex-start',
        gap: theme.spacing(2),
        padding: theme.spacing(1, 2),
        cursor: clickable ? 'pointer' : 'default',
        '&:hover': clickable ? {backgroundColor: theme.palette.action.hover} : undefined,
        [theme.breakpoints.down('sm')]: {
            flexDirection: 'column',
            gap: theme.spacing(0.5),
            padding: theme.spacing(1, 1.5),
        },
    }),
);

const NameCell = styled(Box)(({theme}) => ({
    width: 240,
    flexShrink: 0,
    display: 'flex',
    alignItems: 'flex-start',
    gap: 4,
    paddingTop: 4,
    [theme.breakpoints.down('sm')]: {width: '100%'},
}));

const NameText = styled(Typography)(({theme}) => ({
    fontFamily: theme.adp.fontFamilyMono,
    fontSize: '12px',
    fontWeight: 600,
    wordBreak: 'break-word',
    flex: 1,
    paddingTop: 2,
}));

const ValueCell = styled(Box)(({theme}) => ({
    flex: 1,
    minWidth: 0,
    overflow: 'hidden',
    display: 'flex',
    alignItems: 'center',
    gap: theme.spacing(1),
    paddingTop: 2,
    [theme.breakpoints.down('sm')]: {width: '100%'},
}));

const ClassValueText = styled('span')(({theme}) => ({
    fontFamily: theme.adp.fontFamilyMono,
    fontSize: '12px',
    color: theme.palette.text.secondary,
    wordBreak: 'break-word',
    flex: 1,
    minWidth: 0,
}));

const Summary = styled(Box)(({theme}) => ({
    fontFamily: theme.adp.fontFamilyMono,
    fontSize: '12px',
    color: theme.palette.text.secondary,
    display: 'flex',
    alignItems: 'center',
    flexWrap: 'wrap',
    columnGap: theme.spacing(0.75),
    rowGap: theme.spacing(0.25),
    flex: 1,
    minWidth: 0,
    wordBreak: 'break-word',
}));

const SummaryTarget = styled('span')(({theme}) => ({
    color: theme.palette.text.primary,
    fontWeight: 500,
    wordBreak: 'break-all',
    userSelect: 'text',
}));

const SummaryMeta = styled('span')(({theme}) => ({
    color: theme.palette.text.disabled,
    fontSize: '11px',
    flexShrink: 0,
}));

const ConfigPill = styled('span')(({theme}) => ({
    display: 'inline-flex',
    alignItems: 'center',
    height: 18,
    padding: theme.spacing(0, 0.625),
    borderRadius: 4,
    fontSize: '10px',
    fontWeight: 600,
    letterSpacing: '0.04em',
    textTransform: 'uppercase',
    color: theme.palette.text.secondary,
    backgroundColor: theme.palette.action.hover,
    border: `1px solid ${theme.palette.divider}`,
    flexShrink: 0,
}));

const CallList = styled('span')(({theme}) => ({
    color: theme.palette.text.secondary,
    userSelect: 'text',
    fontSize: '11px',
    '& code': {fontFamily: theme.adp.fontFamilyMono},
}));

const KindBadge = styled(Box, {shouldForwardProp: (p) => p !== 'kind'})<{kind: DefinitionKind}>(({theme, kind}) => {
    const palette = {
        factory: theme.palette.primary.main,
        class: theme.palette.success.main,
        object: theme.palette.warning.main,
        primitive: theme.palette.text.secondary,
    }[kind];
    const isMuted = kind === 'primitive';
    return {
        display: 'inline-flex',
        alignItems: 'center',
        height: 20,
        padding: theme.spacing(0, 0.75),
        borderRadius: 4,
        fontSize: '10px',
        fontWeight: 600,
        letterSpacing: '0.04em',
        textTransform: 'uppercase',
        flexShrink: 0,
        color: isMuted ? theme.palette.text.secondary : palette,
        backgroundColor: isMuted ? theme.palette.action.hover : alpha(palette, 0.12),
    };
});

const ActionsCell = styled(Box)({display: 'flex', alignItems: 'center', gap: 2, flexShrink: 0, paddingTop: 2});

const ChevronButton = styled(IconButton, {shouldForwardProp: (p) => p !== 'open'})<{open?: boolean}>(({open}) => ({
    transition: 'transform 150ms',
    transform: open ? 'rotate(90deg)' : 'rotate(0deg)',
}));

const ExpandedPanel = styled(Box)(({theme}) => ({
    padding: theme.spacing(0, 2, 2, 2),
    [theme.breakpoints.down('sm')]: {padding: theme.spacing(0, 1.5, 1.5, 1.5)},
}));

const CodeContainer = styled(Box)(({theme}) => ({
    border: `1px solid ${theme.palette.divider}`,
    borderRadius: theme.shape.borderRadius,
    overflow: 'hidden',
    backgroundColor: theme.palette.mode === 'dark' ? alpha('#000', 0.25) : theme.palette.background.default,
}));

const CodeToolbar = styled(Box)(({theme}) => ({
    display: 'flex',
    alignItems: 'center',
    justifyContent: 'space-between',
    gap: theme.spacing(1),
    padding: theme.spacing(0.5, 1),
    borderBottom: `1px solid ${theme.palette.divider}`,
    backgroundColor: theme.palette.action.hover,
}));

const CodeToolbarLabel = styled(Typography)(({theme}) => ({
    fontSize: '10px',
    fontWeight: 600,
    textTransform: 'uppercase',
    letterSpacing: '0.05em',
    color: theme.palette.text.disabled,
}));

const CodeBody = styled(Box)({
    overflow: 'auto',
    maxHeight: 520,
    '& pre': {margin: '0 !important', background: 'transparent !important'},
});

// ---------------------------------------------------------------------------
// Kind metadata
// ---------------------------------------------------------------------------

const KIND_LABEL: Record<DefinitionKind, string> = {
    factory: 'Factory',
    class: 'Class',
    object: 'Object',
    primitive: 'Value',
};

// ---------------------------------------------------------------------------
// Sub-components
// ---------------------------------------------------------------------------

const ClassNameLink = ({className}: {className: string}) => (
    <Box
        component="span"
        sx={{
            display: 'inline-flex',
            alignItems: 'center',
            minWidth: 0,
            '& a': {color: 'primary.main', wordBreak: 'break-all'},
            '& a:hover': {textDecoration: 'underline'},
        }}
        onClick={(e) => e.stopPropagation()}
    >
        <FileLink className={className}>
            <ClassValueText>{className}</ClassValueText>
        </FileLink>
    </Box>
);

const InlineClassValue = ({
    id,
    value,
    onLoad,
}: {
    id: string;
    value: string;
    onLoad: (id: string) => Promise<string | null>;
}) => {
    const [loading, setLoading] = useState(false);
    const [error, setError] = useState<string | null>(null);

    const handleLoad = useCallback(
        async (e: React.MouseEvent) => {
            e.stopPropagation();
            setLoading(true);
            setError(null);
            const errorMessage = await onLoad(id);
            setLoading(false);
            if (errorMessage) setError(errorMessage);
        },
        [id, onLoad],
    );

    return (
        <Box sx={{display: 'flex', alignItems: 'center', gap: 1, flex: 1, minWidth: 0, flexWrap: 'wrap'}}>
            <ClassNameLink className={value} />
            <Tooltip title={error ? 'Retry loading' : 'Load object state'}>
                <IconButton
                    size="small"
                    onClick={handleLoad}
                    disabled={loading}
                    aria-label={error ? 'Retry loading' : 'Load object state'}
                    sx={{flexShrink: 0}}
                >
                    {loading ? (
                        <CircularProgress size={14} />
                    ) : error ? (
                        <ErrorOutline sx={{fontSize: 14, color: 'error.main'}} />
                    ) : (
                        <Download sx={{fontSize: 14}} />
                    )}
                </IconButton>
            </Tooltip>
            {error && <Typography sx={{fontSize: '11px', color: 'error.main', flexShrink: 0}}>{error}</Typography>}
        </Box>
    );
};

const FactorySummary = ({source}: {source: string}) => {
    const target = useMemo(() => summarizeFactory(source), [source]);
    // Extract a bare class name from the target when it's in `new X\Y` shape
    const classMatch = target.match(/^new\s+\\?([\w\\]+)$/);
    return (
        <Summary>
            <Box component="span" sx={{color: 'text.disabled'}}>
                returns
            </Box>
            {classMatch ? (
                <>
                    <Box component="span" sx={{color: 'text.disabled'}}>
                        new
                    </Box>
                    <SummaryTarget onClick={(e) => e.stopPropagation()}>
                        <FileLink className={classMatch[1]}>{classMatch[1]}</FileLink>
                    </SummaryTarget>
                </>
            ) : (
                <SummaryTarget>{target}</SummaryTarget>
            )}
            <SummaryMeta>· {countLines(source)} lines</SummaryMeta>
        </Summary>
    );
};

const ObjectSummary = ({id, value}: {id: string; value: unknown}) => {
    const cfg = useMemo(() => parseObjectConfig(value), [value]);
    const resolvedClass = cfg.explicitClass ?? id;
    const hooks = [...cfg.calls, ...cfg.properties];
    const shownHooks = hooks.slice(0, 3);
    const extraHooks = hooks.length - shownHooks.length;

    const isSelf = !cfg.explicitClass;
    const hookLabel = hooks.length === 1 ? '1 call' : `${hooks.length} calls`;

    return (
        <Summary>
            <SummaryTarget onClick={(e) => e.stopPropagation()}>
                <FileLink className={resolvedClass}>{resolvedClass}</FileLink>
            </SummaryTarget>
            {isSelf && <ConfigPill>self</ConfigPill>}
            {hooks.length > 0 ? (
                <>
                    <SummaryMeta>·</SummaryMeta>
                    <CallList>
                        {shownHooks.map((h, i) => (
                            <Box component="span" key={h}>
                                {i > 0 && ', '}
                                <code>{h}</code>
                            </Box>
                        ))}
                        {extraHooks > 0 && <SummaryMeta> +{extraHooks}</SummaryMeta>}
                    </CallList>
                </>
            ) : (
                <SummaryMeta>· {hookLabel}</SummaryMeta>
            )}
            {cfg.other.length > 0 && <SummaryMeta>· +{cfg.other.length} raw</SummaryMeta>}
        </Summary>
    );
};

const DefinitionRow = ({entry, onLoad}: {entry: DefinitionEntry; onLoad: (id: string) => Promise<string | null>}) => {
    const kind = useMemo(() => detectKind(entry.value), [entry.value]);
    const [expanded, setExpanded] = useState(false);
    const expandable = kind === 'factory' || kind === 'object';

    const toggle = useCallback(() => {
        if (!expandable) return;
        // Don't toggle while user is selecting text
        const selection = typeof window !== 'undefined' ? window.getSelection()?.toString() : '';
        if (selection && selection.length > 0) return;
        setExpanded((v) => !v);
    }, [expandable]);

    const stopProp = useCallback((e: React.MouseEvent) => e.stopPropagation(), []);

    const factoryMeta = useMemo(() => (kind === 'factory' ? getFactoryMeta(entry.value) : null), [kind, entry.value]);

    return (
        <Row expanded={expanded}>
            <RowHead clickable={expandable} onClick={toggle}>
                <NameCell>
                    <NameText>{entry.id}</NameText>
                </NameCell>
                <ValueCell>
                    <KindBadge kind={kind}>{KIND_LABEL[kind]}</KindBadge>

                    {kind === 'class' && (
                        <InlineClassValue id={entry.id} value={entry.value as string} onLoad={onLoad} />
                    )}

                    {kind === 'factory' && factoryMeta && <FactorySummary source={factoryMeta.source} />}

                    {kind === 'object' && <ObjectSummary id={entry.id} value={entry.value} />}

                    {kind === 'primitive' && (
                        <Box sx={{flex: 1, minWidth: 0, overflow: 'hidden'}}>
                            <JsonRenderer value={entry.value} />
                        </Box>
                    )}
                </ValueCell>
                <ActionsCell onClick={stopProp}>
                    {expandable && (
                        <ChevronButton
                            size="small"
                            open={expanded}
                            onClick={toggle}
                            aria-label={expanded ? 'Collapse' : 'Expand'}
                        >
                            <ChevronRight sx={{fontSize: 16}} />
                        </ChevronButton>
                    )}
                    <Tooltip title="Copy name">
                        <IconButton size="small" onClick={() => clipboardCopy(entry.id)} aria-label="Copy name">
                            <ContentCopy sx={{fontSize: 14}} />
                        </IconButton>
                    </Tooltip>
                    <Tooltip title="Examine in container">
                        <IconButton
                            size="small"
                            component={RouterLink}
                            to={'/inspector/container/view?class=' + entry.id}
                            aria-label="Examine in container"
                        >
                            <DataObject sx={{fontSize: 14}} />
                        </IconButton>
                    </Tooltip>
                </ActionsCell>
            </RowHead>

            {expandable && (
                <Collapse in={expanded} unmountOnExit>
                    <ExpandedPanel>
                        {kind === 'factory' && factoryMeta && (
                            <CodeContainer>
                                <CodeToolbar>
                                    <Box sx={{display: 'flex', alignItems: 'center', gap: 1, minWidth: 0}}>
                                        <CodeToolbarLabel>
                                            PHP · {countLines(factoryMeta.source)} lines
                                        </CodeToolbarLabel>
                                        {factoryMeta.file && (
                                            <Box
                                                sx={{
                                                    fontSize: '10px',
                                                    color: 'text.secondary',
                                                    fontFamily: 'monospace',
                                                    overflow: 'hidden',
                                                    textOverflow: 'ellipsis',
                                                    whiteSpace: 'nowrap',
                                                    minWidth: 0,
                                                }}
                                                onClick={(e) => e.stopPropagation()}
                                            >
                                                <FileLink
                                                    path={factoryMeta.file}
                                                    line={factoryMeta.startLine ?? undefined}
                                                >
                                                    {factoryMeta.file}
                                                    {factoryMeta.startLine !== null && `:${factoryMeta.startLine}`}
                                                </FileLink>
                                            </Box>
                                        )}
                                    </Box>
                                    <Tooltip title="Copy source">
                                        <IconButton
                                            size="small"
                                            onClick={() => clipboardCopy(factoryMeta.source)}
                                            aria-label="Copy source"
                                        >
                                            <ContentCopy sx={{fontSize: 14}} />
                                        </IconButton>
                                    </Tooltip>
                                </CodeToolbar>
                                <CodeBody>
                                    <CodeHighlight
                                        language="php"
                                        code={factoryMeta.source}
                                        fontSize={10}
                                        showLineNumbers
                                    />
                                </CodeBody>
                            </CodeContainer>
                        )}
                        {kind === 'object' && (
                            <Box sx={{pt: 0.5}}>
                                <JsonRenderer value={entry.value} depth={2} />
                            </Box>
                        )}
                    </ExpandedPanel>
                </Collapse>
            )}
        </Row>
    );
};

// ---------------------------------------------------------------------------
// Main component
// ---------------------------------------------------------------------------

export const DefinitionsPage = () => {
    const {data, isLoading, isError, error, refetch} = useGetConfigurationQuery('di');
    const [lazyLoadObject] = useLazyGetObjectQuery();
    const [searchParams, setSearchParams] = useSearchParams();
    const searchString = searchParams.get('filter') || '';

    const {objects, setObjects, insertObject} = useContext(DataContext);

    const [page, setPage] = useState(0);
    const [rowsPerPage, setRowsPerPage] = useState(50);

    const handleLoadObject = useCallback(
        async (id: string): Promise<string | null> => {
            const result = await lazyLoadObject(id);
            if (result.data) {
                insertObject(id, result.data.object);
                return null;
            }
            const errorData = (result.error as any)?.data;
            return errorData?.error || errorData?.data?.message || 'Failed to load object';
        },
        [lazyLoadObject],
    );

    useEffect(() => {
        if (!isLoading && data) {
            const rows = Object.entries(data || ([] as any));
            const items = rows.map((el) => ({id: el[0], value: el[1]}));
            setObjects(items);
        }
    }, [isLoading, data]);

    const filteredRows = useMemo(() => {
        if (!searchString.trim()) return objects;
        const patterns = searchVariants(searchString).map((v) => new RegExp(regexpQuote(v), 'i'));
        return objects.filter((object) => patterns.some((re) => re.test(object.id)));
    }, [objects, searchString]);

    const paginatedRows = useMemo(() => {
        const start = page * rowsPerPage;
        return filteredRows.slice(start, start + rowsPerPage);
    }, [filteredRows, page, rowsPerPage]);

    const onChangeHandler = useCallback(
        (value: string) => {
            setSearchParams({filter: value});
            setPage(0);
        },
        [setSearchParams],
    );

    if (isLoading) {
        return <FullScreenCircularProgress />;
    }

    if (isError) {
        return (
            <QueryErrorState
                error={error}
                title="Failed to load definitions"
                fallback="Failed to load DI definitions."
                onRetry={refetch}
            />
        );
    }

    return (
        <Box>
            <SearchRow>
                <FilterInput value={searchString} onChange={onChangeHandler} placeholder="Search definitions..." />
                <Typography sx={{fontSize: '12px', color: 'text.disabled', whiteSpace: 'nowrap'}}>
                    {searchString
                        ? `${filteredRows.length} of ${objects.length} definitions`
                        : `${objects.length} definitions`}
                </Typography>
            </SearchRow>

            <Box sx={{px: 2, pb: 2}}>
                {filteredRows.length === 0 ? (
                    <EmptyState
                        icon="account_tree"
                        title="No definitions found"
                        description={searchString ? `No definitions match "${searchString}"` : undefined}
                    />
                ) : (
                    <ListContainer>
                        <ListHeader>
                            <HeaderLabel sx={{width: 240, flexShrink: 0}}>Name</HeaderLabel>
                            <HeaderLabel sx={{flex: 1}}>Value</HeaderLabel>
                            <HeaderLabel sx={{width: 92, flexShrink: 0, textAlign: 'right'}}>Actions</HeaderLabel>
                        </ListHeader>
                        {paginatedRows.map((entry) => (
                            <DefinitionRow key={entry.id} entry={entry} onLoad={handleLoadObject} />
                        ))}
                        {filteredRows.length > 20 && (
                            <TablePagination
                                component="div"
                                count={filteredRows.length}
                                page={page}
                                onPageChange={(_, p) => setPage(p)}
                                rowsPerPage={rowsPerPage}
                                onRowsPerPageChange={(e) => {
                                    setRowsPerPage(parseInt(e.target.value, 10));
                                    setPage(0);
                                }}
                                rowsPerPageOptions={[20, 50, 100]}
                                sx={{borderTop: 1, borderColor: 'divider'}}
                            />
                        )}
                    </ListContainer>
                )}
            </Box>
        </Box>
    );
};
