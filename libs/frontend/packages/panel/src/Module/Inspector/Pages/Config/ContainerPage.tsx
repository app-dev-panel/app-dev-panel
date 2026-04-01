import {useGetClassesQuery, useLazyGetObjectQuery} from '@app-dev-panel/panel/Module/Inspector/API/Inspector';
import {DataContext} from '@app-dev-panel/panel/Module/Inspector/Context/DataContext';
import {EmptyState} from '@app-dev-panel/sdk/Component/EmptyState';
import {FilterInput} from '@app-dev-panel/sdk/Component/FilterInput';
import {FullScreenCircularProgress} from '@app-dev-panel/sdk/Component/FullScreenCircularProgress';
import {JsonRenderer} from '@app-dev-panel/sdk/Component/JsonRenderer';
import {primitives} from '@app-dev-panel/sdk/Component/Theme/tokens';
import {searchVariants} from '@app-dev-panel/sdk/Helper/layoutTranslit';
import {regexpQuote} from '@app-dev-panel/sdk/Helper/regexpQuote';
import {ContentCopy, Download, OpenInNew} from '@mui/icons-material';
import {Box, CircularProgress, IconButton, TablePagination, Tooltip, Typography} from '@mui/material';
import {styled} from '@mui/material/styles';
import clipboardCopy from 'clipboard-copy';
import {useCallback, useContext, useEffect, useMemo, useState} from 'react';
import {useSearchParams} from 'react-router-dom';

// ---------------------------------------------------------------------------
// Types
// ---------------------------------------------------------------------------

type ContainerEntry = {id: string; value: unknown};

// ---------------------------------------------------------------------------
// Styled components
// ---------------------------------------------------------------------------

const SearchRow = styled(Box)(({theme}) => ({
    display: 'flex',
    alignItems: 'center',
    gap: theme.spacing(1.5),
    padding: theme.spacing(2),
}));

const EntryRow = styled(Box)(({theme}) => ({
    display: 'flex',
    alignItems: 'flex-start',
    gap: theme.spacing(2),
    padding: theme.spacing(1, 2),
    borderBottom: `1px solid ${theme.palette.divider}`,
    '&:last-child': {borderBottom: 'none'},
    '&:hover': {backgroundColor: theme.palette.action.hover},
}));

const NameCell = styled(Box)({
    width: 280,
    flexShrink: 0,
    display: 'flex',
    alignItems: 'flex-start',
    gap: 4,
    paddingTop: 4,
});

const NameText = styled(Typography)({
    fontFamily: primitives.fontFamilyMono,
    fontSize: '12px',
    fontWeight: 600,
    wordBreak: 'break-all',
    flex: 1,
    paddingTop: 2,
});

const ValueCell = styled(Box)({flex: 1, minWidth: 0, overflow: 'hidden', paddingTop: 4});

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

const ContainerValue = ({entry, onLoad}: {entry: ContainerEntry; onLoad: (id: string) => void}) => {
    const [loading, setLoading] = useState(false);

    const handleLoad = useCallback(async () => {
        setLoading(true);
        await onLoad(entry.id);
        setLoading(false);
    }, [entry.id, onLoad]);

    if (entry.value) {
        return <JsonRenderer key={entry.id} value={entry.value} depth={2} />;
    }

    return (
        <Tooltip title="Load object state">
            <IconButton size="small" onClick={handleLoad} disabled={loading}>
                {loading ? <CircularProgress size={14} /> : <Download sx={{fontSize: 14}} />}
            </IconButton>
        </Tooltip>
    );
};

// ---------------------------------------------------------------------------
// Main component
// ---------------------------------------------------------------------------

export const ContainerPage = () => {
    const {data, isLoading} = useGetClassesQuery('');
    const [lazyLoadObject] = useLazyGetObjectQuery();
    const [searchParams, setSearchParams] = useSearchParams();
    const searchString = searchParams.get('filter') || '';

    const {objects, setObjects, insertObject} = useContext(DataContext);

    const [page, setPage] = useState(0);
    const [rowsPerPage, setRowsPerPage] = useState(50);

    const handleLoadObject = useCallback(
        async (id: string) => {
            const result = await lazyLoadObject(id);
            if (result.data) {
                insertObject(id, result.data.object);
            }
        },
        [lazyLoadObject, insertObject],
    );

    useEffect(() => {
        if (!isLoading && data) {
            setObjects(data.map((row) => ({id: row, value: null})));
        }
    }, [isLoading, data, setObjects]);

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
                <FilterInput
                    value={searchString}
                    onChange={onChangeHandler}
                    placeholder="Search container entries..."
                />
                <Typography sx={{fontSize: '12px', color: 'text.disabled', whiteSpace: 'nowrap'}}>
                    {searchString ? `${filteredRows.length} of ${objects.length} entries` : `${objects.length} entries`}
                </Typography>
            </SearchRow>

            <Box sx={{px: 2, pb: 2}}>
                {filteredRows.length === 0 ? (
                    <EmptyState
                        icon="widgets"
                        title="No container entries found"
                        description={searchString ? `No entries match "${searchString}"` : undefined}
                    />
                ) : (
                    <ListContainer>
                        <ListHeader>
                            <HeaderLabel sx={{width: 280, flexShrink: 0}}>Class</HeaderLabel>
                            <HeaderLabel sx={{flex: 1}}>Value</HeaderLabel>
                            <HeaderLabel sx={{width: 68, flexShrink: 0, textAlign: 'right'}}>Actions</HeaderLabel>
                        </ListHeader>
                        {paginatedRows.map((entry) => (
                            <EntryRow key={entry.id}>
                                <NameCell>
                                    <NameText>{entry.id}</NameText>
                                </NameCell>
                                <ValueCell>
                                    <ContainerValue entry={entry} onLoad={handleLoadObject} />
                                </ValueCell>
                                <ActionsCell>
                                    <Tooltip title="Copy class name">
                                        <IconButton size="small" onClick={() => clipboardCopy(entry.id)}>
                                            <ContentCopy sx={{fontSize: 14}} />
                                        </IconButton>
                                    </Tooltip>
                                    <Tooltip title="Examine as container entry">
                                        <IconButton size="small" href={'/inspector/container/view?class=' + entry.id}>
                                            <OpenInNew sx={{fontSize: 14}} />
                                        </IconButton>
                                    </Tooltip>
                                </ActionsCell>
                            </EntryRow>
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
