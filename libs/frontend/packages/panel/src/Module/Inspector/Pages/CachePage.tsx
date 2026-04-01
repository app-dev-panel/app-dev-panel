import {
    useClearCacheMutation,
    useDeleteCacheMutation,
    useGetCacheQuery,
} from '@app-dev-panel/panel/Module/Inspector/API/Inspector';
import {FilterInput} from '@app-dev-panel/sdk/Component/Form/FilterInput';
import {JsonRenderer} from '@app-dev-panel/sdk/Component/JsonRenderer';
import {PageHeader} from '@app-dev-panel/sdk/Component/PageHeader';
import {Button, CircularProgress, LinearProgress, Stack} from '@mui/material';
import {useCallback} from 'react';
import {useSearchParams} from 'react-router';

type CacheViewProps = {data: any};

export const CachePage = ({showHeader = true}: {showHeader?: boolean}) => {
    const [searchParams, setSearchParams] = useSearchParams();
    const searchString = searchParams.get('filter') || '';
    const [clearCacheMutation, clearCacheMutationInfo] = useClearCacheMutation();
    const [deleteCacheMutation, deleteCacheMutationInfo] = useDeleteCacheMutation();
    const getCacheQuery = useGetCacheQuery(searchString, {skip: searchString === ''});

    const onChangeHandler = useCallback(async (value: string) => {
        setSearchParams({filter: value});
    }, []);

    const onRefetchHandler = async () => {
        getCacheQuery.refetch();
    };
    const onDeleteHandler = async () => {
        await deleteCacheMutation(searchString);
        await getCacheQuery.refetch();
    };

    const onPurgeHandler = async () => {
        await clearCacheMutation();
        await getCacheQuery.refetch();
    };

    return (
        <>
            {showHeader && <PageHeader title="Cache" icon="cached" description="View and manage application cache" />}
            <Stack direction="row" justifyContent="space-between">
                <FilterInput value={searchString} onChange={onChangeHandler} />
                <Button
                    color="error"
                    onClick={onPurgeHandler}
                    disabled={clearCacheMutationInfo.isLoading}
                    endIcon={clearCacheMutationInfo.isLoading ? <CircularProgress size={24} color="info" /> : null}
                >
                    Purge cache
                </Button>
            </Stack>
            {getCacheQuery.isFetching && <LinearProgress />}
            {searchString !== '' && !getCacheQuery.isFetching && getCacheQuery.data !== undefined && (
                <Stack direction="column">
                    <Stack direction="row">
                        <Button
                            color="primary"
                            onClick={onRefetchHandler}
                            disabled={deleteCacheMutationInfo.isLoading}
                            endIcon={
                                deleteCacheMutationInfo.isLoading ? <CircularProgress size={24} color="info" /> : null
                            }
                        >
                            Refresh
                        </Button>
                        <Button
                            color="error"
                            onClick={onDeleteHandler}
                            disabled={deleteCacheMutationInfo.isLoading}
                            endIcon={
                                deleteCacheMutationInfo.isLoading ? <CircularProgress size={24} color="info" /> : null
                            }
                        >
                            Delete
                        </Button>
                    </Stack>
                    <JsonRenderer value={getCacheQuery.data} />
                </Stack>
            )}
        </>
    );
};
