import {OpenInNew} from '@mui/icons-material';
import {TabContext, TabPanel} from '@mui/lab';
import TabList from '@mui/lab/TabList';
import {Alert, AlertTitle, IconButton, List, ListItem, ListItemText, Tab, Tooltip} from '@mui/material';
import Box from '@mui/material/Box';
import {parseFilePath} from '@yiisoft/yii-dev-panel-sdk/Helper/filePathParser';
import React, {SyntheticEvent, useState} from 'react';

type Operation = 'readdir' | 'mkdir' | 'read' | 'unlink';
type Information = {
    path: string;
    args: Record<string, any>;
};
type FilesystemPanelProps = {
    data: {
        [key in Operation]: Information;
    }[];
};

export const FilesystemPanel = ({data}: FilesystemPanelProps) => {
    const tabs = Object.keys(data) as Operation[];

    console.log('tabs', data);

    const [value, setValue] = useState<Operation>(tabs[0]);

    const handleChange = (event: SyntheticEvent, newValue: Operation) => {
        setValue(newValue);
    };

    if (!data || data.length === 0) {
        return (
            <Box m={2}>
                <Alert severity="info">
                    <AlertTitle>No operations with file system found during the process</AlertTitle>
                </Alert>
            </Box>
        );
    }
    return (
        <TabContext value={value}>
            <Box sx={{borderBottom: 1, borderColor: 'divider'}}>
                <TabList onChange={handleChange}>
                    {tabs.map((tab) => (
                        <Tab label={tab} value={tab} key={tab} />
                    ))}
                </TabList>
            </Box>
            {tabs.map((tab) => (
                <TabPanel value={tab} key={tab}>
                    <List dense>
                        {data[tab].map((el, index) => (
                            <ListItem key={index}>
                                <ListItemText
                                    sx={{wordBreak: 'break-all'}}
                                    secondary={Object.keys(el.args).length ? JSON.stringify(el.args) : null}
                                >
                                    <Tooltip title="Open in File Explorer">
                                        <IconButton
                                            size="small"
                                            href={`/inspector/files?path=${parseFilePath(el.path)}`}
                                        >
                                            <OpenInNew fontSize="small" />
                                        </IconButton>
                                    </Tooltip>
                                    {el.path}
                                </ListItemText>
                            </ListItem>
                        ))}
                    </List>
                </TabPanel>
            ))}
        </TabContext>
    );
};
