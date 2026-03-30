import {useGetElasticsearchHealthQuery} from '@app-dev-panel/panel/Module/Inspector/API/Inspector';
import {FullScreenCircularProgress} from '@app-dev-panel/sdk/Component/FullScreenCircularProgress';
import {DataTable} from '@app-dev-panel/sdk/Component/Grid';
import {PageHeader} from '@app-dev-panel/sdk/Component/PageHeader';
import {Box, Chip, Typography} from '@mui/material';
import {GridColDef, GridRenderCellParams, GridValidRowModel} from '@mui/x-data-grid';
import {useMemo} from 'react';

const healthColor = (health: string): 'success' | 'warning' | 'error' | 'default' => {
    switch (health) {
        case 'green':
            return 'success';
        case 'yellow':
            return 'warning';
        case 'red':
            return 'error';
        default:
            return 'default';
    }
};

const columns: GridColDef[] = [
    {
        field: 'name',
        headerName: 'Index',
        width: 250,
        renderCell: (params: GridRenderCellParams) => (
            <Typography my={1} sx={{fontWeight: 500, wordBreak: 'break-all'}}>
                {params.value}
            </Typography>
        ),
    },
    {
        field: 'health',
        headerName: 'Health',
        width: 100,
        renderCell: (params: GridRenderCellParams) => (
            <Chip label={params.value} size="small" color={healthColor(params.value)} sx={{fontWeight: 600}} />
        ),
    },
    {
        field: 'status',
        headerName: 'Status',
        width: 100,
        renderCell: (params: GridRenderCellParams) => <Typography my={1}>{params.value}</Typography>,
    },
    {
        field: 'docsCount',
        headerName: 'Documents',
        width: 130,
        renderCell: (params: GridRenderCellParams) => (
            <Typography my={1}>{Number(params.value).toLocaleString()}</Typography>
        ),
    },
    {
        field: 'storeSize',
        headerName: 'Size',
        width: 120,
        renderCell: (params: GridRenderCellParams) => <Typography my={1}>{params.value}</Typography>,
    },
    {
        field: 'primaryShards',
        headerName: 'Shards',
        width: 90,
        renderCell: (params: GridRenderCellParams) => <Typography my={1}>{params.value}</Typography>,
    },
    {
        field: 'replicas',
        headerName: 'Replicas',
        width: 90,
        renderCell: (params: GridRenderCellParams) => <Typography my={1}>{params.value}</Typography>,
    },
];

type HealthData = {
    health: {
        status: string;
        clusterName: string;
        numberOfNodes: number;
        numberOfDataNodes: number;
        activePrimaryShards: number;
        activeShards: number;
        unassignedShards: number;
    };
    indices: GridValidRowModel[];
};

export const ElasticsearchPage = () => {
    const {data, isLoading} = useGetElasticsearchHealthQuery();

    const typedData = data as unknown as HealthData | undefined;
    const health = typedData?.health;
    const indices = useMemo(() => (typedData?.indices ?? []) as GridValidRowModel[], [typedData]);

    if (isLoading) {
        return <FullScreenCircularProgress />;
    }

    return (
        <>
            <PageHeader title="Elasticsearch" icon="search" description="Inspect Elasticsearch cluster and indices" />

            {health && (
                <Box sx={{display: 'flex', gap: 2, mb: 3, flexWrap: 'wrap'}}>
                    <Chip label={`Cluster: ${health.clusterName || 'unknown'}`} size="small" sx={{fontWeight: 600}} />
                    <Chip label={`Status: ${health.status}`} size="small" color={healthColor(health.status)} />
                    <Chip label={`Nodes: ${health.numberOfNodes}`} size="small" variant="outlined" />
                    <Chip label={`Data Nodes: ${health.numberOfDataNodes}`} size="small" variant="outlined" />
                    <Chip label={`Shards: ${health.activeShards}`} size="small" variant="outlined" />
                    {health.unassignedShards > 0 && (
                        <Chip label={`Unassigned: ${health.unassignedShards}`} size="small" color="warning" />
                    )}
                </Box>
            )}

            <DataTable rows={indices} getRowId={(row) => row.name} columns={columns} />
        </>
    );
};
