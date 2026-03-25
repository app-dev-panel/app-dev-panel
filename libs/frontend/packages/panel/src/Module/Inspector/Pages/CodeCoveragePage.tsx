import {CoverageFileList, CoverageSummary} from '@app-dev-panel/panel/Module/Debug/Component/Panel/CodeCoveragePanel';
import {useGetCoverageQuery} from '@app-dev-panel/panel/Module/Inspector/API/Inspector';
import {EmptyState} from '@app-dev-panel/sdk/Component/EmptyState';
import {FullScreenCircularProgress} from '@app-dev-panel/sdk/Component/FullScreenCircularProgress';
import {PageHeader} from '@app-dev-panel/sdk/Component/PageHeader';
import {Box} from '@mui/material';

export const CodeCoveragePage = () => {
    const {data, isLoading} = useGetCoverageQuery();

    if (isLoading) return <FullScreenCircularProgress />;

    if (!data || data.driver === null) {
        return (
            <>
                <PageHeader title="Code Coverage" icon="shield" description="PHP code coverage analysis" />
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
            <PageHeader title="Code Coverage" icon="shield" description="PHP code coverage analysis" />
            <CoverageSummary data={data} />
            <CoverageFileList files={data.files} />
        </Box>
    );
};
