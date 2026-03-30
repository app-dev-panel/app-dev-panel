import {FilesystemPanel} from '@app-dev-panel/panel/Module/Debug/Component/Panel/FilesystemPanel';
import {HttpClientPanel} from '@app-dev-panel/panel/Module/Debug/Component/Panel/HttpClientPanel';
import {EmptyState} from '@app-dev-panel/sdk/Component/EmptyState';
import {TabContext, TabPanel} from '@mui/lab';
import TabList from '@mui/lab/TabList';
import {Box, Chip, Tab} from '@mui/material';
import {styled} from '@mui/material/styles';
import {type SyntheticEvent, useCallback, useMemo, useState} from 'react';

type IOPanelProps = {filesystem: any | null; http: any | null};

const IOTabList = styled(TabList)(({theme}) => ({
    minHeight: 40,
    borderBottom: `1px solid ${theme.palette.divider}`,
    '& .MuiTab-root': {
        minHeight: 40,
        fontSize: '13px',
        fontWeight: 600,
        textTransform: 'none',
        padding: theme.spacing(0.5, 2),
    },
}));

const CountChip = styled(Chip)({
    fontSize: '10px',
    height: 18,
    minWidth: 24,
    borderRadius: 4,
    fontWeight: 700,
    marginLeft: 6,
});

export const IOPanel = ({filesystem, http}: IOPanelProps) => {
    const hasFilesystem = filesystem != null;
    const hasHttp = http != null;

    const filesystemCount = useMemo(() => {
        if (!filesystem || typeof filesystem !== 'object') return 0;
        return Object.values(filesystem).reduce(
            (sum: number, entries: any) => sum + (Array.isArray(entries) ? entries.length : 0),
            0,
        );
    }, [filesystem]);

    const httpCount = useMemo(() => {
        if (!http) return 0;
        return Array.isArray(http) ? http.length : 0;
    }, [http]);

    const defaultTab = hasFilesystem ? 'filesystem' : 'http';
    const [activeTab, setActiveTab] = useState(defaultTab);

    const handleTabChange = useCallback((_: SyntheticEvent, value: string) => {
        setActiveTab(value);
    }, []);

    if (!hasFilesystem && !hasHttp) {
        return <EmptyState icon="swap_horiz" title="No I/O operations found" />;
    }

    return (
        <TabContext value={activeTab}>
            <IOTabList onChange={handleTabChange}>
                {hasFilesystem && (
                    <Tab
                        label={
                            <Box sx={{display: 'flex', alignItems: 'center'}}>
                                Filesystem
                                <CountChip label={filesystemCount} size="small" variant="outlined" />
                            </Box>
                        }
                        value="filesystem"
                    />
                )}
                {hasHttp && (
                    <Tab
                        label={
                            <Box sx={{display: 'flex', alignItems: 'center'}}>
                                HTTP
                                <CountChip label={httpCount} size="small" variant="outlined" />
                            </Box>
                        }
                        value="http"
                    />
                )}
            </IOTabList>
            {hasFilesystem && (
                <TabPanel value="filesystem" sx={{p: 0}}>
                    <FilesystemPanel data={filesystem} />
                </TabPanel>
            )}
            {hasHttp && (
                <TabPanel value="http" sx={{p: 0}}>
                    <HttpClientPanel data={http} />
                </TabPanel>
            )}
        </TabContext>
    );
};
