import {JsonRenderer} from '@app-dev-panel/panel/Module/Debug/Component/JsonRenderer';
import {EmptyState} from '@app-dev-panel/sdk/Component/EmptyState';
import {FileLink} from '@app-dev-panel/sdk/Component/FileLink';
import {FilterInput} from '@app-dev-panel/sdk/Component/FilterInput';
import {SectionTitle} from '@app-dev-panel/sdk/Component/SectionTitle';
import {TabContext, TabPanel} from '@mui/lab';
import TabList from '@mui/lab/TabList';
import {Box, Chip, Icon, IconButton, Tab, Tooltip, Typography} from '@mui/material';
import {styled, useTheme} from '@mui/material/styles';
import {SyntheticEvent, useDeferredValue, useMemo, useState} from 'react';

type Operation = string;
type Information = {path: string; args: Record<string, any>};
type FilesystemPanelProps = {data: {[key in Operation]: Information}[]};

// ---------------------------------------------------------------------------
// Styled components
// ---------------------------------------------------------------------------

const SummaryGrid = styled(Box)(({theme}) => ({
    display: 'grid',
    gridTemplateColumns: 'repeat(auto-fill, minmax(130px, 1fr))',
    gap: theme.spacing(1.5),
    padding: theme.spacing(2),
}));

const SummaryCard = styled(Box)(({theme}) => ({
    padding: theme.spacing(1.5, 2),
    borderRadius: Number(theme.shape.borderRadius) * 1.5,
    border: `1px solid ${theme.palette.divider}`,
    backgroundColor: theme.palette.background.paper,
    display: 'flex',
    flexDirection: 'column',
    gap: theme.spacing(0.5),
}));

const SummaryLabel = styled(Typography)(({theme}) => ({
    fontSize: '11px',
    fontWeight: 600,
    textTransform: 'uppercase' as const,
    letterSpacing: '0.5px',
    color: theme.palette.text.disabled,
}));

const SummaryValue = styled(Typography)(({theme}) => ({
    fontFamily: theme.adp.fontFamilyMono,
    fontWeight: 700,
    fontSize: '20px',
}));

const FileRow = styled(Box, {shouldForwardProp: (p) => p !== 'expanded'})<{expanded?: boolean}>(
    ({theme, expanded}) => ({
        display: 'flex',
        alignItems: 'center',
        gap: theme.spacing(1.5),
        padding: theme.spacing(1, 2),
        borderBottom: `1px solid ${theme.palette.divider}`,
        cursor: 'pointer',
        transition: 'background-color 0.1s ease',
        backgroundColor: expanded ? theme.palette.action.hover : 'transparent',
        '&:hover': {backgroundColor: theme.palette.action.hover},
    }),
);

const PathCell = styled(Typography)(({theme}) => ({
    fontFamily: theme.adp.fontFamilyMono,
    fontSize: '12px',
    flex: 1,
    wordBreak: 'break-all',
    minWidth: 0,
}));

const DetailBox = styled(Box)(({theme}) => ({
    padding: theme.spacing(1.5, 2, 1.5, 6),
    backgroundColor: theme.palette.action.hover,
    borderBottom: `1px solid ${theme.palette.divider}`,
    fontSize: '12px',
}));

const StyledTabList = styled(TabList)(({theme}) => ({
    minHeight: 36,
    '& .MuiTab-root': {
        minHeight: 36,
        fontSize: '12px',
        fontWeight: 600,
        textTransform: 'none',
        padding: theme.spacing(0.5, 2),
    },
}));

// ---------------------------------------------------------------------------
// Helpers
// ---------------------------------------------------------------------------

const operationMeta = (op: string): {icon: string; color: string} => {
    switch (op) {
        case 'read':
            return {icon: 'visibility', color: 'primary'};
        case 'readdir':
            return {icon: 'folder_open', color: 'info'};
        case 'write':
            return {icon: 'edit', color: 'success'};
        case 'mkdir':
            return {icon: 'create_new_folder', color: 'success'};
        case 'rename':
            return {icon: 'drive_file_rename_outline', color: 'warning'};
        case 'unlink':
            return {icon: 'delete_outline', color: 'error'};
        case 'rmdir':
            return {icon: 'folder_delete', color: 'error'};
        default:
            return {icon: 'description', color: 'default'};
    }
};

// ---------------------------------------------------------------------------
// Sub-components
// ---------------------------------------------------------------------------

const PAGE_SIZE = 100;

const OperationView = ({items, operation, filter}: {items: Information[]; operation: string; filter: string}) => {
    const [expandedIndex, setExpandedIndex] = useState<number | null>(null);
    const [visibleCount, setVisibleCount] = useState(PAGE_SIZE);
    const deferredFilter = useDeferredValue(filter);

    const filtered = useMemo(() => {
        if (!items) return [];
        if (!deferredFilter) return items;
        const q = deferredFilter.toLowerCase();
        return items.filter((item) => item.path.toLowerCase().includes(q));
    }, [items, deferredFilter]);

    if (!items || items.length === 0) {
        return <EmptyState icon="folder_open" title={`No ${operation} operations found`} />;
    }

    if (filtered.length === 0) {
        return <EmptyState icon="search_off" title="No matching paths" />;
    }

    const visible = filtered.slice(0, visibleCount);
    const hasMore = visibleCount < filtered.length;

    return (
        <Box>
            <Box sx={{display: 'flex', alignItems: 'center', gap: 1, py: 1, px: 2}}>
                <Typography variant="body2" sx={{color: 'text.disabled'}}>
                    {filtered.length === items.length
                        ? `${items.length} operation${items.length !== 1 ? 's' : ''}`
                        : `${filtered.length} of ${items.length} operations`}
                </Typography>
            </Box>
            {visible.map((item, index) => {
                const expanded = expandedIndex === index;
                const hasArgs = Object.keys(item.args).length > 0;

                return (
                    <Box key={index}>
                        <FileRow expanded={expanded} onClick={() => setExpandedIndex(expanded ? null : index)}>
                            <PathCell>{item.path}</PathCell>
                            <FileLink path={item.path}>
                                <Chip
                                    component="span"
                                    clickable
                                    label="Open"
                                    size="small"
                                    icon={<Icon sx={{fontSize: '14px !important'}}>open_in_new</Icon>}
                                    sx={{fontSize: '10px', height: 22}}
                                    variant="outlined"
                                />
                            </FileLink>
                            {hasArgs && (
                                <IconButton size="small" sx={{flexShrink: 0}}>
                                    <Icon sx={{fontSize: 16}}>{expanded ? 'expand_less' : 'expand_more'}</Icon>
                                </IconButton>
                            )}
                        </FileRow>
                        {expanded && hasArgs && (
                            <DetailBox>
                                <Typography sx={{fontSize: '11px', fontWeight: 600, color: 'text.disabled', mb: 0.5}}>
                                    Arguments
                                </Typography>
                                <JsonRenderer value={item.args} />
                            </DetailBox>
                        )}
                    </Box>
                );
            })}
            {hasMore && (
                <Box sx={{display: 'flex', justifyContent: 'center', py: 1.5, borderBottom: 1, borderColor: 'divider'}}>
                    <Chip
                        label={`Show ${Math.min(PAGE_SIZE, filtered.length - visibleCount)} more (${filtered.length - visibleCount} remaining)`}
                        onClick={() => setVisibleCount((c) => c + PAGE_SIZE)}
                        clickable
                        variant="outlined"
                        size="small"
                    />
                </Box>
            )}
        </Box>
    );
};

// ---------------------------------------------------------------------------
// Main component
// ---------------------------------------------------------------------------

export const FilesystemPanel = ({data}: FilesystemPanelProps) => {
    const theme = useTheme();
    const tabs = data ? (Object.keys(data) as Operation[]) : [];
    const [value, setValue] = useState<Operation>(tabs[0]);
    const [filter, setFilter] = useState('');

    const handleChange = (_event: SyntheticEvent, newValue: Operation) => {
        setValue(newValue);
    };

    if (!data || tabs.length === 0) {
        return <EmptyState icon="folder_open" title="No filesystem operations found" />;
    }

    const totalOps = tabs.reduce((sum, tab) => sum + ((data as any)[tab]?.length ?? 0), 0);

    return (
        <Box>
            <SummaryGrid>
                <SummaryCard>
                    <SummaryLabel>Total Operations</SummaryLabel>
                    <SummaryValue>{totalOps}</SummaryValue>
                </SummaryCard>
                {tabs.map((tab) => {
                    const count = (data as any)[tab]?.length ?? 0;
                    const meta = operationMeta(tab);
                    return (
                        <SummaryCard key={tab}>
                            <SummaryLabel>
                                <Box sx={{display: 'flex', alignItems: 'center', gap: 0.5}}>
                                    <Icon sx={{fontSize: 14, color: `${meta.color}.main`}}>{meta.icon}</Icon>
                                    {tab}
                                </Box>
                            </SummaryLabel>
                            <SummaryValue sx={{color: count > 0 ? 'text.primary' : 'text.disabled'}}>
                                {count}
                            </SummaryValue>
                        </SummaryCard>
                    );
                })}
            </SummaryGrid>

            <SectionTitle action={<FilterInput value={filter} onChange={setFilter} placeholder="Filter by path..." />}>
                Operations
            </SectionTitle>

            <TabContext value={value}>
                <Box sx={{borderBottom: 1, borderColor: 'divider'}}>
                    <StyledTabList onChange={handleChange}>
                        {tabs.map((tab) => {
                            const count = (data as any)[tab]?.length ?? 0;
                            const meta = operationMeta(tab);
                            return (
                                <Tab
                                    label={
                                        <Box sx={{display: 'flex', alignItems: 'center', gap: 0.5}}>
                                            <Tooltip title={tab}>
                                                <Icon sx={{fontSize: 16, color: `${meta.color}.main`}}>
                                                    {meta.icon}
                                                </Icon>
                                            </Tooltip>
                                            {tab}
                                            <Chip
                                                label={count}
                                                size="small"
                                                color={meta.color as any}
                                                variant="outlined"
                                                sx={{
                                                    fontSize: '10px',
                                                    height: 18,
                                                    minWidth: 24,
                                                    borderRadius: 1,
                                                    fontWeight: 700,
                                                }}
                                            />
                                        </Box>
                                    }
                                    value={tab}
                                    key={tab}
                                />
                            );
                        })}
                    </StyledTabList>
                </Box>
                {tabs.map((tab) => (
                    <TabPanel value={tab} key={tab} sx={{padding: 0}}>
                        <OperationView items={(data as any)[tab]} operation={tab} filter={filter} />
                    </TabPanel>
                ))}
            </TabContext>
        </Box>
    );
};
