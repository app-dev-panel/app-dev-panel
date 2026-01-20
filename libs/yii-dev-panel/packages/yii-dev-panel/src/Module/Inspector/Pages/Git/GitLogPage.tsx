import {GetApp, Refresh, Sync} from '@mui/icons-material';
import {Button, CircularProgress, Divider, List, ListItem, ListItemText} from '@mui/material';
import Box from '@mui/material/Box';
import Typography from '@mui/material/Typography';
import {useCommandMutation, useGetLogQuery} from '@yiisoft/yii-dev-panel/Module/Inspector/API/GitApi';
import {useBreadcrumbs} from '@yiisoft/yii-dev-panel/Application/Context/BreadcrumbsContext';
import {useCallback} from 'react';

export const GitLogPage = () => {
    const getLogQuery = useGetLogQuery();
    const [commandMutation, commandInfo] = useCommandMutation();

    const onPullHandler = useCallback(() => commandMutation({command: 'pull'}), []);
    const onFetchHandler = useCallback(() => commandMutation({command: 'fetch'}), []);
    const onRefreshHandler = () => getLogQuery.refetch();

    useBreadcrumbs(() => [{title: 'Inspector', href: 'inspector'}, {title: 'Git', href: 'git'}, 'Log']);

    return (
        <>
            <h2>{'Git log'}</h2>
            {getLogQuery.isSuccess && (
                <>
                    <Box>
                        <Box display="flex">
                            <Button
                                variant="outlined"
                                onClick={onRefreshHandler}
                                color={getLogQuery.isSuccess ? 'primary' : 'error'}
                                disabled={getLogQuery.isFetching}
                                startIcon={<Refresh />}
                                endIcon={getLogQuery.isFetching ? <CircularProgress size={24} color="info" /> : null}
                            >
                                Refresh
                            </Button>
                            <Button
                                variant="outlined"
                                sx={{marginLeft: 'auto'}}
                                onClick={onPullHandler}
                                color={commandInfo.isSuccess || commandInfo.isUninitialized ? 'primary' : 'error'}
                                disabled={commandInfo.isLoading}
                                startIcon={<GetApp />}
                                endIcon={
                                    commandInfo.isLoading && commandInfo.originalArgs?.command === 'pull' ? (
                                        <CircularProgress size={24} color="info" />
                                    ) : null
                                }
                            >
                                Pull
                            </Button>
                            <Button
                                variant="outlined"
                                onClick={onFetchHandler}
                                color={commandInfo.isSuccess || commandInfo.isUninitialized ? 'primary' : 'error'}
                                disabled={commandInfo.isLoading}
                                startIcon={<Sync />}
                                endIcon={
                                    commandInfo.isLoading && commandInfo.originalArgs?.command === 'fetch' ? (
                                        <CircularProgress size={24} color="info" />
                                    ) : null
                                }
                            >
                                Fetch
                            </Button>
                        </Box>
                        <List>
                            <ListItem>
                                <ListItemText primary="Branch" secondary={getLogQuery.data.currentBranch} />
                            </ListItem>
                            <Divider>History</Divider>
                            {getLogQuery.data.commits.map((commit, index) => (
                                <ListItem key={index}>
                                    <ListItemText
                                        primary={
                                            <>
                                                <Typography
                                                    sx={{display: 'inline'}}
                                                    component="span"
                                                    variant="body2"
                                                    color="text.primary"
                                                >
                                                    {commit.sha}:&nbsp;
                                                </Typography>
                                                {commit.message}
                                            </>
                                        }
                                        secondary={`by ${commit.author.name} (${commit.author.email})`}
                                    />
                                </ListItem>
                            ))}
                        </List>
                    </Box>
                </>
            )}
        </>
    );
};
