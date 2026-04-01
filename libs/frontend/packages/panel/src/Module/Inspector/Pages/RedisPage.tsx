import {
    useDeleteRedisKeyMutation,
    useFlushRedisDbMutation,
    useGetRedisDbSizeQuery,
    useGetRedisInfoQuery,
    useGetRedisKeysQuery,
    useGetRedisPingQuery,
    useLazyGetRedisKeyQuery,
} from '@app-dev-panel/panel/Module/Inspector/API/Inspector';
import {FilterInput} from '@app-dev-panel/sdk/Component/Form/FilterInput';
import {JsonRenderer} from '@app-dev-panel/sdk/Component/JsonRenderer';
import {PageHeader} from '@app-dev-panel/sdk/Component/PageHeader';
import {primitives} from '@app-dev-panel/sdk/Component/Theme/tokens';
import {
    Alert,
    Box,
    Button,
    Chip,
    CircularProgress,
    Collapse,
    Icon,
    LinearProgress,
    Stack,
    Tab,
    Tabs,
    Typography,
} from '@mui/material';
import {styled, useTheme} from '@mui/material/styles';
import {useCallback, useState} from 'react';
import {useSearchParams} from 'react-router-dom';

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
    borderRadius: Number(theme.shape.borderRadius) * 1.5,
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

const KeyRow = styled(Box)(({theme}) => ({
    display: 'flex',
    alignItems: 'center',
    gap: theme.spacing(1.5),
    padding: theme.spacing(1, 1.5),
    borderBottom: `1px solid ${theme.palette.divider}`,
    cursor: 'pointer',
    transition: 'background-color 0.1s ease',
    '&:hover': {backgroundColor: theme.palette.action.hover},
}));

const KeyName = styled(Typography)({
    fontFamily: primitives.fontFamilyMono,
    fontSize: '12px',
    flex: 1,
    wordBreak: 'break-all',
    minWidth: 0,
});

const DetailBox = styled(Box)(({theme}) => ({
    padding: theme.spacing(1.5, 2, 1.5, 5.5),
    borderBottom: `1px solid ${theme.palette.divider}`,
    backgroundColor: theme.palette.action.hover,
}));

// ---------------------------------------------------------------------------
// RedisPage
// ---------------------------------------------------------------------------

export const RedisPage = ({showHeader = true}: {showHeader?: boolean}) => {
    const theme = useTheme();
    const [searchParams, setSearchParams] = useSearchParams();
    const [tab, setTab] = useState(0);
    const pattern = searchParams.get('pattern') || '*';
    const [expandedKey, setExpandedKey] = useState<string | null>(null);
    const [keyData, setKeyData] = useState<{key: string; type: string; ttl: number; value: unknown} | null>(null);

    const pingQuery = useGetRedisPingQuery();
    const dbSizeQuery = useGetRedisDbSizeQuery();
    const infoQuery = useGetRedisInfoQuery(undefined, {skip: tab !== 1});
    const keysQuery = useGetRedisKeysQuery({pattern, limit: 100});
    const [getRedisKey] = useLazyGetRedisKeyQuery();
    const [deleteKeyMutation, deleteKeyInfo] = useDeleteRedisKeyMutation();
    const [flushDbMutation, flushDbInfo] = useFlushRedisDbMutation();

    const onPatternChange = useCallback(
        (value: string) => {
            setSearchParams({pattern: value || '*'});
        },
        [setSearchParams],
    );

    const onKeyClick = useCallback(
        async (key: string) => {
            if (expandedKey === key) {
                setExpandedKey(null);
                setKeyData(null);
                return;
            }
            setExpandedKey(key);
            const result = await getRedisKey(key);
            if (result.data) {
                setKeyData(result.data);
            }
        },
        [expandedKey, getRedisKey],
    );

    const onDeleteKey = useCallback(
        async (key: string) => {
            await deleteKeyMutation(key);
            setExpandedKey(null);
            setKeyData(null);
            keysQuery.refetch();
            dbSizeQuery.refetch();
        },
        [deleteKeyMutation, keysQuery, dbSizeQuery],
    );

    const onFlushDb = useCallback(async () => {
        await flushDbMutation();
        keysQuery.refetch();
        dbSizeQuery.refetch();
    }, [flushDbMutation, keysQuery, dbSizeQuery]);

    const isConnected = pingQuery.isSuccess && !(pingQuery.data as any)?.error;
    const isLoading = pingQuery.isLoading || dbSizeQuery.isLoading;

    return (
        <>
            {showHeader && <PageHeader title="Redis" icon="memory" description="Inspect and manage Redis data store" />}

            {isLoading && <LinearProgress />}

            {pingQuery.isError && (
                <Alert severity="error" sx={{mb: 2}}>
                    Failed to connect to Redis. Make sure Redis is available in the DI container.
                </Alert>
            )}

            {/* Summary cards */}
            <SummaryGrid>
                <SummaryCard>
                    <SummaryLabel>Status</SummaryLabel>
                    <SummaryValue sx={{color: isConnected ? 'success.main' : 'error.main', fontSize: '18px'}}>
                        {isConnected ? 'Connected' : 'Disconnected'}
                    </SummaryValue>
                </SummaryCard>
                {dbSizeQuery.data && (
                    <SummaryCard>
                        <SummaryLabel>DB Size</SummaryLabel>
                        <SummaryValue sx={{color: 'primary.main'}}>{dbSizeQuery.data.size}</SummaryValue>
                    </SummaryCard>
                )}
            </SummaryGrid>

            {/* Tabs */}
            <Box sx={{borderBottom: 1, borderColor: 'divider', mb: 2}}>
                <Tabs value={tab} onChange={(_, v) => setTab(v)}>
                    <Tab label="Keys" />
                    <Tab label="Server Info" />
                </Tabs>
            </Box>

            {/* Keys tab */}
            {tab === 0 && (
                <>
                    <Stack direction="row" justifyContent="space-between" sx={{mb: 2}}>
                        <FilterInput
                            value={pattern === '*' ? '' : pattern}
                            onChange={onPatternChange}
                            placeholder="Key pattern (e.g. user:*)"
                        />
                        <Button
                            color="error"
                            onClick={onFlushDb}
                            disabled={flushDbInfo.isLoading}
                            endIcon={flushDbInfo.isLoading ? <CircularProgress size={24} color="info" /> : null}
                        >
                            Flush DB
                        </Button>
                    </Stack>
                    {keysQuery.isFetching && <LinearProgress />}
                    {keysQuery.data?.keys.map((key) => (
                        <Box key={key}>
                            <KeyRow onClick={() => onKeyClick(key)}>
                                <Icon sx={{fontSize: 16, color: 'text.disabled'}}>vpn_key</Icon>
                                <KeyName sx={{color: 'text.primary'}}>{key}</KeyName>
                                {expandedKey === key && keyData && (
                                    <Chip
                                        label={keyData.type}
                                        size="small"
                                        sx={{
                                            fontWeight: 600,
                                            fontSize: '10px',
                                            height: 20,
                                            borderRadius: 0.5,
                                            backgroundColor: theme.palette.info.light,
                                            color: theme.palette.info.main,
                                        }}
                                    />
                                )}
                                <Icon sx={{fontSize: 16, color: 'text.disabled'}}>
                                    {expandedKey === key ? 'expand_less' : 'expand_more'}
                                </Icon>
                            </KeyRow>
                            <Collapse in={expandedKey === key && keyData !== null}>
                                {keyData && expandedKey === key && (
                                    <DetailBox>
                                        <Stack direction="row" spacing={2} sx={{mb: 1, alignItems: 'center'}}>
                                            <Chip label={`Type: ${keyData.type}`} size="small" variant="outlined" />
                                            <Chip
                                                label={keyData.ttl === -1 ? 'No expiry' : `TTL: ${keyData.ttl}s`}
                                                size="small"
                                                variant="outlined"
                                            />
                                            <Button
                                                color="error"
                                                size="small"
                                                onClick={(e) => {
                                                    e.stopPropagation();
                                                    onDeleteKey(key);
                                                }}
                                                disabled={deleteKeyInfo.isLoading}
                                            >
                                                Delete
                                            </Button>
                                        </Stack>
                                        <JsonRenderer value={keyData.value} depth={5} />
                                    </DetailBox>
                                )}
                            </Collapse>
                        </Box>
                    ))}
                    {keysQuery.data?.keys.length === 0 && !keysQuery.isFetching && (
                        <Typography sx={{p: 2, color: 'text.secondary', textAlign: 'center'}}>
                            No keys found matching pattern &quot;{pattern}&quot;
                        </Typography>
                    )}
                </>
            )}

            {/* Server Info tab */}
            {tab === 1 && (
                <Box>
                    {infoQuery.isFetching && <LinearProgress />}
                    {infoQuery.data && <JsonRenderer value={infoQuery.data} depth={3} />}
                    {infoQuery.isError && <Alert severity="error">Failed to fetch Redis server info.</Alert>}
                </Box>
            )}
        </>
    );
};
