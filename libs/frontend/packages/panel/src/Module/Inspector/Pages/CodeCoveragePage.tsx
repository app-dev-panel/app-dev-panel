import {CoverageFileList, CoverageSummary} from '@app-dev-panel/panel/Module/Debug/Component/Panel/CodeCoveragePanel';
import {useGetCoverageQuery} from '@app-dev-panel/panel/Module/Inspector/API/Inspector';
import {EmptyState} from '@app-dev-panel/sdk/Component/EmptyState';
import {FullScreenCircularProgress} from '@app-dev-panel/sdk/Component/FullScreenCircularProgress';
import {PageHeader} from '@app-dev-panel/sdk/Component/PageHeader';
import {QueryErrorState} from '@app-dev-panel/sdk/Component/QueryErrorState';
import {Box} from '@mui/material';

export const CodeCoveragePage = ({showHeader = true}: {showHeader?: boolean}) => {
    const {data, isLoading, isError, error, refetch} = useGetCoverageQuery();

    if (isLoading) return <FullScreenCircularProgress />;

    if (isError) {
        return (
            <>
                {showHeader && (
                    <PageHeader title="Code Coverage" icon="shield" description="PHP code coverage analysis" />
                )}
                <QueryErrorState
                    error={error}
                    title="Failed to load coverage data"
                    fallback="Failed to load coverage data."
                    onRetry={refetch}
                />
            </>
        );
    }

    if (!data || data.driver === null) {
        return (
            <>
                {showHeader && (
                    <PageHeader title="Code Coverage" icon="shield" description="PHP code coverage analysis" />
                )}
                <EmptyState
                    icon="shield"
                    title="No coverage driver"
                    description={data?.error ?? 'No code coverage driver available (install pcov or xdebug).'}
                />
            </>
        );
    }

    return (
        <Box>
            {showHeader && <PageHeader title="Code Coverage" icon="shield" description="PHP code coverage analysis" />}
            <CoverageSummary data={data} />
            <CoverageFileList files={data.files} />
        </Box>
    );
};
