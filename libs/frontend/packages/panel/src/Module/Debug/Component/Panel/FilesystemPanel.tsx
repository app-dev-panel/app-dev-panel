import {JsonRenderer} from '@app-dev-panel/panel/Module/Debug/Component/JsonRenderer';
import {EmptyState} from '@app-dev-panel/sdk/Component/EmptyState';
import {FileLink} from '@app-dev-panel/sdk/Component/FileLink';
import {primitives} from '@app-dev-panel/sdk/Component/Theme/tokens';
import {TabContext, TabPanel} from '@mui/lab';
import TabList from '@mui/lab/TabList';
import {Box, Chip, Collapse, Icon, IconButton, Tab, Typography} from '@mui/material';
import {styled} from '@mui/material/styles';
import {SyntheticEvent, useState} from 'react';

type Operation = 'readdir' | 'mkdir' | 'read' | 'unlink';
type Information = {path: string; args: Record<string, any>};
type FilesystemPanelProps = {data: {[key in Operation]: Information}[]};

const FileRow = styled(Box, {shouldForwardProp: (p) => p !== 'expanded'})<{expanded?: boolean}>(
    ({theme, expanded}) => ({
        display: 'flex',
        alignItems: 'flex-start',
        gap: theme.spacing(1.5),
        padding: theme.spacing(1, 1.5),
        borderBottom: `1px solid ${theme.palette.divider}`,
        cursor: 'pointer',
        transition: 'background-color 0.1s ease',
        backgroundColor: expanded ? theme.palette.action.hover : 'transparent',
        '&:hover': {backgroundColor: theme.palette.action.hover},
    }),
);

const PathCell = styled(Typography)({
    fontFamily: primitives.fontFamilyMono,
    fontSize: '12px',
    flex: 1,
    wordBreak: 'break-all',
});

const DetailBox = styled(Box)(({theme}) => ({
    padding: theme.spacing(1.5, 1.5, 1.5, 6),
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

const OperationView = ({items, operation}: {items: Information[]; operation: string}) => {
    const [expandedIndex, setExpandedIndex] = useState<number | null>(null);

    if (!items || items.length === 0) {
        return <EmptyState icon="folder_open" title={`No ${operation} operations found`} />;
    }

    return (
        <Box>
            <Box sx={{mt: 1, mb: 1, px: 1.5}}>
                <Typography sx={{fontSize: '12px', color: 'text.disabled'}}>{items.length} operations</Typography>
            </Box>
            {items.map((item, index) => {
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
                        {hasArgs && (
                            <Collapse in={expanded}>
                                <DetailBox>
                                    <Typography
                                        sx={{fontSize: '11px', fontWeight: 600, color: 'text.disabled', mb: 0.5}}
                                    >
                                        Arguments
                                    </Typography>
                                    <JsonRenderer value={item.args} />
                                </DetailBox>
                            </Collapse>
                        )}
                    </Box>
                );
            })}
        </Box>
    );
};

export const FilesystemPanel = ({data}: FilesystemPanelProps) => {
    const tabs = data ? (Object.keys(data) as Operation[]) : [];
    const [value, setValue] = useState<Operation>(tabs[0]);

    const handleChange = (event: SyntheticEvent, newValue: Operation) => {
        setValue(newValue);
    };

    if (!data || tabs.length === 0) {
        return <EmptyState icon="folder_open" title="No filesystem operations found" />;
    }

    return (
        <Box>
            <TabContext value={value}>
                <Box sx={{borderBottom: 1, borderColor: 'divider'}}>
                    <StyledTabList onChange={handleChange}>
                        {tabs.map((tab) => (
                            <Tab
                                label={
                                    <Box sx={{display: 'flex', alignItems: 'center', gap: 0.5}}>
                                        {tab}
                                        <Chip
                                            label={(data as any)[tab]?.length ?? 0}
                                            size="small"
                                            sx={{fontSize: '10px', height: 18, minWidth: 24, borderRadius: 1}}
                                        />
                                    </Box>
                                }
                                value={tab}
                                key={tab}
                            />
                        ))}
                    </StyledTabList>
                </Box>
                {tabs.map((tab) => (
                    <TabPanel value={tab} key={tab} sx={{padding: 0}}>
                        <OperationView items={(data as any)[tab]} operation={tab} />
                    </TabPanel>
                ))}
            </TabContext>
        </Box>
    );
};
