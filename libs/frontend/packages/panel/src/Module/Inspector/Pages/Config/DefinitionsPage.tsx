import {useGetConfigurationQuery, useLazyGetObjectQuery} from '@app-dev-panel/panel/Module/Inspector/API/Inspector';
import {DataContext} from '@app-dev-panel/panel/Module/Inspector/Context/DataContext';
import {EmptyState} from '@app-dev-panel/sdk/Component/EmptyState';
import {FilterInput} from '@app-dev-panel/sdk/Component/FilterInput';
import {FullScreenCircularProgress} from '@app-dev-panel/sdk/Component/FullScreenCircularProgress';
import {JsonRenderer} from '@app-dev-panel/sdk/Component/JsonRenderer';
import {searchVariants} from '@app-dev-panel/sdk/Helper/layoutTranslit';
import {regexpQuote} from '@app-dev-panel/sdk/Helper/regexpQuote';
import {ContentCopy, DataObject, Download, ErrorOutline} from '@mui/icons-material';
import {Box, CircularProgress, IconButton, TablePagination, Tooltip, Typography} from '@mui/material';
import {styled} from '@mui/material/styles';
import clipboardCopy from 'clipboard-copy';
import {useCallback, useContext, useEffect, useMemo, useState} from 'react';
import {useSearchParams} from 'react-router';

// ---------------------------------------------------------------------------
// Types
// ---------------------------------------------------------------------------

type DefinitionEntry = {id: string; value: unknown};

// ---------------------------------------------------------------------------
// Styled components
// ---------------------------------------------------------------------------

const SearchRow = styled(Box)(({theme}) => ({
    display: 'flex',
    alignItems: 'center',
    gap: theme.spacing(1.5),
    padding: theme.spacing(2),
}));

const DefinitionRow = styled(Box)(({theme}) => ({
    display: 'flex',
    alignItems: 'flex-start',
    gap: theme.spacing(2),
    padding: theme.spacing(1, 2),
    borderBottom: `1px solid ${theme.palette.divider}`,
    '&:last-child': {borderBottom: 'none'},
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
    wordBreak: 'break-word',
    flex: 1,
    paddingTop: 2,
}));

const ValueCell = styled(Box)({flex: 1, minWidth: 0, overflow: 'hidden'});

const ClassValue = styled(Typography)(({theme}) => ({
    fontFamily: theme.adp.fontFamilyMono,
    fontSize: '12px',
    color: theme.palette.text.secondary,
    wordBreak: 'break-word',
    paddingTop: 4,
}));

const ActionsCell = styled(Box)({display: 'flex', alignItems: 'center', gap: 2, flexShrink: 0, paddingTop: 2});

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

// ---------------------------------------------------------------------------
// Sub-components
// ---------------------------------------------------------------------------

const DefinitionValue = ({entry, onLoad}: {entry: DefinitionEntry; onLoad: (id: string) => Promise<string | null>}) => {
    const [loading, setLoading] = useState(false);
    const [error, setError] = useState<string | null>(null);

    const isClassName = typeof entry.value === 'string' && /^[\w\\]+$/i.test(entry.value);

    const handleLoad = useCallback(async () => {
        setLoading(true);
        setError(null);
        const errorMessage = await onLoad(entry.id);
        setLoading(false);
        if (errorMessage) {
            setError(errorMessage);
        }
    }, [entry.id, onLoad]);

    if (typeof entry.value === 'string' && isClassName) {
        return (
            <Box>
                <Box sx={{display: 'flex', alignItems: 'center', gap: 1}}>
                    <ClassValue>{entry.value}</ClassValue>
                    <Tooltip title={error ? 'Retry loading' : 'Load object state'}>
                        <IconButton size="small" onClick={handleLoad} disabled={loading} sx={{flexShrink: 0}}>
                            {loading ? (
                                <CircularProgress size={14} />
                            ) : error ? (
                                <ErrorOutline sx={{fontSize: 14, color: 'error.main'}} />
                            ) : (
                                <Download sx={{fontSize: 14}} />
                            )}
                        </IconButton>
                    </Tooltip>
                </Box>
                {error && <Typography sx={{fontSize: '11px', color: 'error.main', mt: 0.5}}>{error}</Typography>}
            </Box>
        );
    }

    if (typeof entry.value !== 'string') {
        return <JsonRenderer value={entry.value} depth={2} />;
    }

    return <JsonRenderer value={entry.value} />;
};

// ---------------------------------------------------------------------------
// Main component
// ---------------------------------------------------------------------------

export const DefinitionsPage = () => {
    const {data, isLoading} = useGetConfigurationQuery('di');
    const [lazyLoadObject] = useLazyGetObjectQuery();
    const [searchParams, setSearchParams] = useSearchParams();
    const searchString = searchParams.get('filter') || '';

    const {objects, setObjects, insertObject} = useContext(DataContext);

    // Pagination state
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
                            <HeaderLabel sx={{width: 68, flexShrink: 0, textAlign: 'right'}}>Actions</HeaderLabel>
                        </ListHeader>
                        {paginatedRows.map((entry) => (
                            <DefinitionRow key={entry.id}>
                                <NameCell>
                                    <NameText>{entry.id}</NameText>
                                </NameCell>
                                <ValueCell>
                                    <DefinitionValue entry={entry} onLoad={handleLoadObject} />
                                </ValueCell>
                                <ActionsCell>
                                    <Tooltip title="Copy name">
                                        <IconButton size="small" onClick={() => clipboardCopy(entry.id)}>
                                            <ContentCopy sx={{fontSize: 14}} />
                                        </IconButton>
                                    </Tooltip>
                                    <Tooltip title="Examine in container">
                                        <IconButton size="small" href={'/inspector/container/view?class=' + entry.id}>
                                            <DataObject sx={{fontSize: 14}} />
                                        </IconButton>
                                    </Tooltip>
                                </ActionsCell>
                            </DefinitionRow>
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
