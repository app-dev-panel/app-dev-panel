import {ClassName} from '@app-dev-panel/panel/Application/Component/ClassName';
import {
    AuthorizationGuard,
    AuthorizationVoter,
    useGetAuthorizationQuery,
} from '@app-dev-panel/panel/Module/Inspector/API/Inspector';
import {EmptyState} from '@app-dev-panel/sdk/Component/EmptyState';
import {JsonRenderer} from '@app-dev-panel/sdk/Component/JsonRenderer';
import {PageHeader} from '@app-dev-panel/sdk/Component/PageHeader';
import {QueryErrorState} from '@app-dev-panel/sdk/Component/QueryErrorState';
import {SectionTitle} from '@app-dev-panel/sdk/Component/SectionTitle';
import {Box, Chip, LinearProgress, Typography} from '@mui/material';
import {styled} from '@mui/material/styles';

const TableContainer = styled(Box)(({theme}) => ({
    border: `1px solid ${theme.palette.divider}`,
    borderRadius: Number(theme.shape.borderRadius) * 1.5,
    overflow: 'hidden',
    marginBottom: theme.spacing(3),
}));

const TableRow = styled(Box)(({theme}) => ({
    display: 'flex',
    alignItems: 'center',
    gap: theme.spacing(1.5),
    padding: theme.spacing(1, 1.5),
    borderBottom: `1px solid ${theme.palette.divider}`,
    fontSize: '13px',
    '&:last-child': {borderBottom: 'none'},
}));

const TableHeader = styled(TableRow)(({theme}) => ({
    fontWeight: 600,
    fontSize: '11px',
    color: theme.palette.text.disabled,
    textTransform: 'uppercase',
    backgroundColor: theme.palette.action.hover,
}));

const MonoText = styled(Typography)(({theme}) => ({fontFamily: theme.adp.fontFamilyMono, fontSize: '12px'}));

const ClassNameText = ({value, bold = false, muted = false}: {value: string; bold?: boolean; muted?: boolean}) => (
    <ClassName value={value}>
        <Typography
            component="span"
            sx={(theme) => ({
                fontFamily: theme.adp.fontFamilyMono,
                fontSize: '12px',
                fontWeight: bold ? 600 : 400,
                color: muted ? 'text.secondary' : 'text.primary',
            })}
        >
            {value}
        </Typography>
    </ClassName>
);

const HierarchyRow = styled(Box)(({theme}) => ({
    display: 'flex',
    alignItems: 'center',
    gap: theme.spacing(1),
    padding: theme.spacing(1, 1.5),
    borderBottom: `1px solid ${theme.palette.divider}`,
    '&:last-child': {borderBottom: 'none'},
}));

const GuardsTable = ({guards}: {guards: AuthorizationGuard[]}) => (
    <TableContainer>
        <TableHeader>
            <Box sx={{flex: 1}}>Name</Box>
            <Box sx={{flex: 1}}>Provider</Box>
            <Box sx={{flex: 2}}>Config</Box>
        </TableHeader>
        {guards.map((guard) => (
            <TableRow key={guard.name}>
                <Box sx={{flex: 1}}>
                    <ClassNameText value={guard.name} bold />
                </Box>
                <Box sx={{flex: 1}}>
                    <ClassNameText value={guard.provider} muted />
                </Box>
                <Box sx={{flex: 2}}>
                    {Object.entries(guard.config).map(([key, value]) => (
                        <Chip
                            key={key}
                            label={`${key}: ${String(value)}`}
                            size="small"
                            variant="outlined"
                            sx={(theme) => ({
                                fontFamily: theme.adp.fontFamilyMono,
                                fontSize: '10px',
                                height: 20,
                                borderRadius: 1,
                                mr: 0.5,
                                mb: 0.5,
                            })}
                        />
                    ))}
                </Box>
            </TableRow>
        ))}
    </TableContainer>
);

const RoleHierarchyTable = ({hierarchy}: {hierarchy: Record<string, string[]>}) => (
    <TableContainer>
        {Object.entries(hierarchy).map(([role, children]) => (
            <HierarchyRow key={role}>
                <Chip
                    label={role}
                    size="small"
                    sx={(theme) => ({
                        fontFamily: theme.adp.fontFamilyMono,
                        fontSize: '11px',
                        fontWeight: 600,
                        height: 22,
                        borderRadius: 1,
                        flexShrink: 0,
                    })}
                />
                <Typography sx={{color: 'text.disabled', fontSize: '12px', mx: 0.5}}>inherits</Typography>
                <Box sx={{display: 'flex', gap: 0.5, flexWrap: 'wrap'}}>
                    {children.map((child) => (
                        <Chip
                            key={child}
                            label={child}
                            size="small"
                            variant="outlined"
                            sx={(theme) => ({
                                fontFamily: theme.adp.fontFamilyMono,
                                fontSize: '10px',
                                height: 20,
                                borderRadius: 1,
                            })}
                        />
                    ))}
                </Box>
            </HierarchyRow>
        ))}
    </TableContainer>
);

const VotersTable = ({voters}: {voters: AuthorizationVoter[]}) => (
    <TableContainer>
        <TableHeader>
            <Box sx={{flex: 2}}>Name</Box>
            <Box sx={{flex: 1}}>Type</Box>
            <Box sx={{width: 80}}>Priority</Box>
        </TableHeader>
        {voters.map((voter, index) => (
            <TableRow key={index}>
                <Box sx={{flex: 2}}>
                    <ClassNameText value={voter.name} bold />
                </Box>
                <Box sx={{flex: 1}}>
                    <Chip
                        label={voter.type}
                        size="small"
                        variant="outlined"
                        sx={{fontSize: '10px', height: 20, borderRadius: 1}}
                    />
                </Box>
                <MonoText sx={{width: 80, color: 'text.secondary'}}>
                    {voter.priority !== null ? voter.priority : '-'}
                </MonoText>
            </TableRow>
        ))}
    </TableContainer>
);

export const AuthorizationPage = () => {
    const {data, isLoading, isError, error, refetch} = useGetAuthorizationQuery();

    if (isLoading) {
        return <LinearProgress />;
    }

    if (isError) {
        return (
            <Box>
                <PageHeader
                    title="Authorization"
                    icon="shield"
                    description="Security guards, roles, voters, and configuration"
                />
                <QueryErrorState
                    error={error}
                    title="Failed to load authorization data"
                    fallback="Failed to load authorization data."
                    onRetry={refetch}
                />
            </Box>
        );
    }

    if (!data) {
        return (
            <Box>
                <PageHeader
                    title="Authorization"
                    icon="shield"
                    description="Security guards, roles, voters, and configuration"
                />
                <EmptyState icon="shield" title="No authorization data available" />
            </Box>
        );
    }

    const hasGuards = data.guards.length > 0;
    const hasHierarchy = Object.keys(data.roleHierarchy).length > 0;
    const hasVoters = data.voters.length > 0;
    const hasConfig = Object.keys(data.config).length > 0;
    const isEmpty = !hasGuards && !hasHierarchy && !hasVoters && !hasConfig;

    return (
        <Box>
            <PageHeader
                title="Authorization"
                icon="shield"
                description="Security guards, roles, voters, and configuration"
            />

            {isEmpty && (
                <EmptyState
                    icon="shield"
                    title="No authorization configuration found"
                    description="Framework adapter needs to implement AuthorizationConfigProviderInterface"
                />
            )}

            {hasGuards && (
                <>
                    <SectionTitle>{`Guards (${data.guards.length})`}</SectionTitle>
                    <GuardsTable guards={data.guards} />
                </>
            )}

            {hasHierarchy && (
                <>
                    <SectionTitle>Role Hierarchy</SectionTitle>
                    <RoleHierarchyTable hierarchy={data.roleHierarchy} />
                </>
            )}

            {hasVoters && (
                <>
                    <SectionTitle>{`Voters / Policies (${data.voters.length})`}</SectionTitle>
                    <VotersTable voters={data.voters} />
                </>
            )}

            {hasConfig && (
                <>
                    <SectionTitle>Security Configuration</SectionTitle>
                    <JsonRenderer value={data.config} />
                </>
            )}
        </Box>
    );
};
