import {useGetCoverageQuery} from '@app-dev-panel/panel/Module/Inspector/API/Inspector';
import {EmptyState} from '@app-dev-panel/sdk/Component/EmptyState';
import {FilterInput} from '@app-dev-panel/sdk/Component/FilterInput';
import {FullScreenCircularProgress} from '@app-dev-panel/sdk/Component/FullScreenCircularProgress';
import {PageHeader} from '@app-dev-panel/sdk/Component/PageHeader';
import {SectionTitle} from '@app-dev-panel/sdk/Component/SectionTitle';
import {primitives} from '@app-dev-panel/sdk/Component/Theme/tokens';
import {Box, Chip, LinearProgress, Typography} from '@mui/material';
import {styled, useTheme} from '@mui/material/styles';
import {useDeferredValue, useMemo, useState} from 'react';

// ---------------------------------------------------------------------------
// Types
// ---------------------------------------------------------------------------

type FileInfo = {coveredLines: number; executableLines: number; percentage: number};

type CoverageResponse = {
    driver: string | null;
    error?: string;
    files: Record<string, FileInfo>;
    summary: {totalFiles: number; coveredLines: number; executableLines: number; percentage: number};
};

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

const FileRow = styled(Box)(({theme}) => ({
    display: 'flex',
    alignItems: 'center',
    gap: theme.spacing(1.5),
    padding: theme.spacing(1, 1.5),
    borderBottom: `1px solid ${theme.palette.divider}`,
    '&:hover': {backgroundColor: theme.palette.action.hover},
}));

const FilePathCell = styled(Typography)({
    fontFamily: primitives.fontFamilyMono,
    fontSize: '12px',
    flex: 1,
    wordBreak: 'break-all',
    minWidth: 0,
});

const StatsCell = styled(Typography)({
    fontFamily: primitives.fontFamilyMono,
    fontSize: '11px',
    flexShrink: 0,
    width: 100,
    textAlign: 'right',
});

// ---------------------------------------------------------------------------
// Helpers
// ---------------------------------------------------------------------------

const coverageColor = (percentage: number): 'success' | 'warning' | 'error' => {
    if (percentage >= 80) return 'success';
    if (percentage >= 50) return 'warning';
    return 'error';
};

type FileSortEntry = {path: string; info: FileInfo};

// ---------------------------------------------------------------------------
// CodeCoveragePage
// ---------------------------------------------------------------------------

export const CodeCoveragePage = () => {
    const theme = useTheme();
    const {data, isLoading} = useGetCoverageQuery();
    const [filter, setFilter] = useState('');
    const deferredFilter = useDeferredValue(filter);

    const fileEntries = useMemo((): FileSortEntry[] => {
        if (!data?.files) return [];
        const entries = Object.entries(data.files).map(([path, info]) => ({path, info}));
        entries.sort((a, b) => a.info.percentage - b.info.percentage);
        return entries;
    }, [data?.files]);

    const filtered = useMemo(() => {
        if (!deferredFilter) return fileEntries;
        const lower = deferredFilter.toLowerCase();
        return fileEntries.filter((entry) => entry.path.toLowerCase().includes(lower));
    }, [fileEntries, deferredFilter]);

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

    const {summary} = data;
    const color = coverageColor(summary.percentage);

    return (
        <Box>
            <PageHeader title="Code Coverage" icon="shield" description="PHP code coverage analysis" />

            {/* Summary cards */}
            <SummaryGrid>
                <SummaryCard>
                    <SummaryLabel>Coverage</SummaryLabel>
                    <SummaryValue sx={{color: `${color}.main`}}>{summary.percentage}%</SummaryValue>
                    <LinearProgress
                        variant="determinate"
                        value={summary.percentage}
                        sx={{
                            mt: 1,
                            height: 4,
                            borderRadius: 2,
                            backgroundColor: theme.palette.action.hover,
                            '& .MuiLinearProgress-bar': {backgroundColor: theme.palette[color].main, borderRadius: 2},
                        }}
                    />
                </SummaryCard>
                <SummaryCard>
                    <SummaryLabel>Files</SummaryLabel>
                    <SummaryValue sx={{color: 'primary.main'}}>{summary.totalFiles}</SummaryValue>
                </SummaryCard>
                <SummaryCard>
                    <SummaryLabel>Covered Lines</SummaryLabel>
                    <SummaryValue sx={{color: 'success.main'}}>{summary.coveredLines}</SummaryValue>
                </SummaryCard>
                <SummaryCard>
                    <SummaryLabel>Executable Lines</SummaryLabel>
                    <SummaryValue sx={{color: 'text.primary'}}>{summary.executableLines}</SummaryValue>
                </SummaryCard>
                <SummaryCard>
                    <SummaryLabel>Driver</SummaryLabel>
                    <SummaryValue sx={{color: 'text.secondary', fontSize: '16px'}}>{data.driver}</SummaryValue>
                </SummaryCard>
            </SummaryGrid>

            {/* Files table */}
            <SectionTitle action={<FilterInput value={filter} onChange={setFilter} placeholder="Filter files..." />}>
                {`${filtered.length} files`}
            </SectionTitle>

            {filtered.map(({path, info}) => {
                const fileColor = coverageColor(info.percentage);
                return (
                    <FileRow key={path}>
                        <Chip
                            label={`${info.percentage}%`}
                            size="small"
                            sx={{
                                fontWeight: 700,
                                fontSize: '10px',
                                height: 20,
                                minWidth: 55,
                                borderRadius: 0.5,
                                backgroundColor: theme.palette[fileColor].light,
                                color: theme.palette[fileColor].main,
                            }}
                        />
                        <FilePathCell sx={{color: 'text.primary'}}>{path}</FilePathCell>
                        <StatsCell sx={{color: 'success.main'}}>
                            {info.coveredLines}/{info.executableLines}
                        </StatsCell>
                        <Box sx={{width: 80, flexShrink: 0}}>
                            <LinearProgress
                                variant="determinate"
                                value={info.percentage}
                                sx={{
                                    height: 4,
                                    borderRadius: 2,
                                    backgroundColor: theme.palette.action.hover,
                                    '& .MuiLinearProgress-bar': {
                                        backgroundColor: theme.palette[fileColor].main,
                                        borderRadius: 2,
                                    },
                                }}
                            />
                        </Box>
                    </FileRow>
                );
            })}
        </Box>
    );
};
