import {useGetClassesQuery, useLazyGetObjectQuery} from '@app-dev-panel/panel/Module/Inspector/API/Inspector';
import {DataContext} from '@app-dev-panel/panel/Module/Inspector/Context/DataContext';
import {groupByNamespace, stripNamespace} from '@app-dev-panel/panel/Module/Inspector/Pages/Config/grouping';
import {EmptyState} from '@app-dev-panel/sdk/Component/EmptyState';
import {FullScreenCircularProgress} from '@app-dev-panel/sdk/Component/FullScreenCircularProgress';
import {GroupCard} from '@app-dev-panel/sdk/Component/GroupCard';
import {JsonRenderer} from '@app-dev-panel/sdk/Component/JsonRenderer';
import {QueryErrorState} from '@app-dev-panel/sdk/Component/QueryErrorState';
import {searchVariants} from '@app-dev-panel/sdk/Helper/layoutTranslit';
import {regexpQuote} from '@app-dev-panel/sdk/Helper/regexpQuote';
import {Code, ContentCopy, DataObject, Download, ErrorOutline} from '@mui/icons-material';
import {Box, CircularProgress, IconButton, Tooltip, Typography} from '@mui/material';
import {styled} from '@mui/material/styles';
import clipboardCopy from 'clipboard-copy';
import {useCallback, useContext, useEffect, useMemo, useState} from 'react';
import {Link as RouterLink, useSearchParams} from 'react-router';

// ---------------------------------------------------------------------------
// Types
// ---------------------------------------------------------------------------

type ContainerEntry = {id: string; value: unknown};

// ---------------------------------------------------------------------------
// Styled components
// ---------------------------------------------------------------------------

const EntryRow = styled(Box)(({theme}) => ({
    display: 'flex',
    alignItems: 'flex-start',
    gap: theme.spacing(2),
    padding: theme.spacing(1, 2),
    borderTop: `1px solid ${theme.palette.divider}`,
    '&:hover': {backgroundColor: theme.palette.action.hover},
    [theme.breakpoints.down('sm')]: {flexDirection: 'column', gap: theme.spacing(0.5), padding: theme.spacing(1, 1.5)},
}));

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
    wordBreak: 'break-all',
    flex: 1,
    paddingTop: 2,
}));

const ValueCell = styled(Box)({flex: 1, minWidth: 0, overflow: 'hidden', paddingTop: 4});

const ActionsCell = styled(Box)({display: 'flex', alignItems: 'center', gap: 2, flexShrink: 0, paddingTop: 2});

// ---------------------------------------------------------------------------
// Sub-components
// ---------------------------------------------------------------------------

const ContainerValue = ({entry, onLoad}: {entry: ContainerEntry; onLoad: (id: string) => Promise<string | null>}) => {
    const [loading, setLoading] = useState(false);
    const [error, setError] = useState<string | null>(null);

    const handleLoad = useCallback(async () => {
        setLoading(true);
        setError(null);
        const errorMessage = await onLoad(entry.id);
        setLoading(false);
        if (errorMessage) {
            setError(errorMessage);
        }
    }, [entry.id, onLoad]);

    if (entry.value) {
        return <JsonRenderer key={entry.id} value={entry.value} depth={2} />;
    }

    return (
        <Box>
            <Tooltip title={error ? 'Retry loading' : 'Load object state'}>
                <IconButton size="small" onClick={handleLoad} disabled={loading}>
                    {loading ? (
                        <CircularProgress size={14} />
                    ) : error ? (
                        <ErrorOutline sx={{fontSize: 14, color: 'error.main'}} />
                    ) : (
                        <Download sx={{fontSize: 14}} />
                    )}
                </IconButton>
            </Tooltip>
            {error && <Typography sx={{fontSize: '11px', color: 'error.main', mt: 0.5}}>{error}</Typography>}
        </Box>
    );
};

// ---------------------------------------------------------------------------
// Main component
// ---------------------------------------------------------------------------

export const ContainerPage = () => {
    const {data, isLoading, isError, error, refetch} = useGetClassesQuery('');
    const [lazyLoadObject] = useLazyGetObjectQuery();
    const [searchParams] = useSearchParams();
    const searchString = searchParams.get('filter') || '';

    const {objects, setObjects, insertObject} = useContext(DataContext);

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
            setObjects(data.map((row) => ({id: row, value: null})));
        }
    }, [isLoading, data]);

    const filteredRows = useMemo(() => {
        if (!searchString.trim()) return objects;
        const patterns = searchVariants(searchString).map((v) => new RegExp(regexpQuote(v), 'i'));
        return objects.filter((object) => patterns.some((re) => re.test(object.id)));
    }, [objects, searchString]);

    const groups = useMemo(() => groupByNamespace(filteredRows), [filteredRows]);

    if (isLoading) {
        return <FullScreenCircularProgress />;
    }

    if (isError) {
        return (
            <QueryErrorState
                error={error}
                title="Failed to load container entries"
                fallback="Failed to load container entries."
                onRetry={refetch}
            />
        );
    }

    return (
        <Box sx={{pb: 2}}>
            {filteredRows.length === 0 && (
                <EmptyState
                    icon="widgets"
                    title="No container entries found"
                    description={searchString ? `No entries match "${searchString}"` : undefined}
                />
            )}
            {groups.map((group) => (
                <GroupCard
                    key={group.name || '__services__'}
                    name={group.displayName}
                    count={group.entries.length}
                    countLabel={group.entries.length === 1 ? 'entry' : 'entries'}
                    defaultExpanded={filteredRows.length <= 10 || groups.length === 1 || !!searchString}
                    preview={
                        <>
                            {group.entries.slice(0, 4).map((entry, i) => (
                                <span key={entry.id}>
                                    {i > 0 && <span style={{opacity: 0.4}}>{' · '}</span>}
                                    {stripNamespace(entry.id, group.name)}
                                </span>
                            ))}
                            {group.entries.length > 4 && <span style={{opacity: 0.4}}> …</span>}
                        </>
                    }
                >
                    {group.entries.map((entry) => (
                        <EntryRow key={entry.id}>
                            <NameCell>
                                <Tooltip title={entry.id} placement="top-start">
                                    <NameText>{stripNamespace(entry.id, group.name)}</NameText>
                                </Tooltip>
                            </NameCell>
                            <ValueCell>
                                <ContainerValue entry={entry} onLoad={handleLoadObject} />
                            </ValueCell>
                            <ActionsCell>
                                <Tooltip title="Open class source">
                                    <IconButton
                                        size="small"
                                        component={RouterLink}
                                        to={`/inspector/files?class=${encodeURIComponent(entry.id)}`}
                                        aria-label="Open class source"
                                    >
                                        <Code sx={{fontSize: 14}} />
                                    </IconButton>
                                </Tooltip>
                                <Tooltip title="Copy class name">
                                    <IconButton
                                        size="small"
                                        onClick={() => clipboardCopy(entry.id)}
                                        aria-label="Copy class name"
                                    >
                                        <ContentCopy sx={{fontSize: 14}} />
                                    </IconButton>
                                </Tooltip>
                                <Tooltip title="Examine as container entry">
                                    <IconButton
                                        size="small"
                                        component={RouterLink}
                                        to={'/inspector/container/view?class=' + entry.id}
                                        aria-label="Examine as container entry"
                                    >
                                        <DataObject sx={{fontSize: 14}} />
                                    </IconButton>
                                </Tooltip>
                            </ActionsCell>
                        </EntryRow>
                    ))}
                </GroupCard>
            ))}
        </Box>
    );
};
