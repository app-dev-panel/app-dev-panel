import {
    CommandType,
    useLazyGetCommandsQuery,
    useRunCommandMutation,
} from '@app-dev-panel/panel/Module/Inspector/API/Inspector';
import {CommandErrorAlert} from '@app-dev-panel/panel/Module/Inspector/Component/Command/CommandErrorAlert';
import {extractCommandError} from '@app-dev-panel/panel/Module/Inspector/Component/Command/extractCommandError';
import {ResultDialog} from '@app-dev-panel/panel/Module/Inspector/Component/Command/ResultDialog';
import {EmptyState} from '@app-dev-panel/sdk/Component/EmptyState';
import {PageHeader} from '@app-dev-panel/sdk/Component/PageHeader';
import {Box, Button, CircularProgress, Link, Typography} from '@mui/material';
import {useEffect, useState} from 'react';

type GroupedCommands = Record<string, CommandType[]>;
type CommandStatusMap = Record<string, {isLoading: boolean; response: null | any}>;
export const CommandsPage = () => {
    const [groupedCommands, setGroupedCommands] = useState<GroupedCommands>({});
    const [commandStatus, setCommandStatus] = useState<CommandStatusMap>({});
    const [showResultDialog, setShowResultDialog] = useState<boolean>(false);
    const [fetchError, setFetchError] = useState<string[] | null>(null);
    const [lastCommand, setLastCommand] = useState<CommandType | null>(null);

    const [getCommandsQuery] = useLazyGetCommandsQuery();
    const [runCommandQuery, runCommandQueryInfo] = useRunCommandMutation();

    useEffect(() => {
        void (async () => {
            const response = await getCommandsQuery();

            if (response.data) {
                const groupedCommands: GroupedCommands = {};
                const commandStatus: CommandStatusMap = {};
                response.data.forEach((command) => {
                    if (command.group in groupedCommands) {
                        groupedCommands[command.group].push(command);
                    } else {
                        groupedCommands[command.group] = [command];
                    }
                    commandStatus[command.name] = {isLoading: false, response: null};
                });
                setCommandStatus(commandStatus);
                setGroupedCommands(groupedCommands);
            } else if (response.error) {
                const error = extractCommandError({error: response.error});
                setFetchError(error?.errors ?? ['Failed to load commands']);
            }
        })();
    }, []);

    const runCommand = async (command: CommandType) => {
        setFetchError(null);
        setLastCommand(command);
        setCommandStatus((prev) => ({...prev, [command.name]: {...prev[command.name], isLoading: true}}));
        const response = await runCommandQuery(command.name);
        setCommandStatus((prev) => ({...prev, [command.name]: {...prev[command.name], isLoading: false}}));

        const error = extractCommandError(response);
        if (!('data' in response) || !response.data) {
            setFetchError(error?.errors ?? ['An unexpected error occurred']);
            return;
        }

        setShowResultDialog(true);
    };
    const commandEntries = Object.entries(groupedCommands as GroupedCommands);

    if (commandEntries.length === 0) {
        return (
            <>
                <PageHeader title="Commands" icon="terminal" description="Run application commands" />
                <EmptyState
                    icon="terminal"
                    title="No commands found"
                    description={
                        <>
                            Add a command to the <code>app-dev-panel/api</code> section in <code>params.php</code> on
                            the backend to run it from ADP. You may inspect the section with the{' '}
                            <Link href="/inspector/config/parameters?filter=app-dev-panel/api">Inspector</Link>. See
                            more information at{' '}
                            <Link href="https://github.com/app-dev-panel/app-dev-panel" target="_blank" rel="noopener">
                                github.com/app-dev-panel/app-dev-panel
                            </Link>
                            .
                        </>
                    }
                />
            </>
        );
    }

    return (
        <>
            <PageHeader title="Commands" icon="terminal" description="Run application commands" />
            {commandEntries.map(([groupName, commands], index) => (
                <Box key={index}>
                    <Typography sx={{fontWeight: 600, fontSize: '16px', mb: 1.5, mt: 2}}>{groupName}</Typography>
                    {commands.map((command, index) => (
                        <Button
                            key={index}
                            variant="outlined"
                            onClick={() => runCommand(command)}
                            disabled={commandStatus[command.name].isLoading}
                            endIcon={
                                commandStatus[command.name].isLoading ? (
                                    <CircularProgress size={24} color="info" />
                                ) : null
                            }
                        >
                            Run {command.title}
                        </Button>
                    ))}
                </Box>
            ))}
            {fetchError && (
                <CommandErrorAlert
                    errors={fetchError}
                    onRetry={lastCommand ? () => runCommand(lastCommand) : undefined}
                    onDismiss={() => setFetchError(null)}
                />
            )}
            <ResultDialog
                status={
                    runCommandQueryInfo.isLoading
                        ? 'loading'
                        : runCommandQueryInfo.data
                          ? runCommandQueryInfo.data.status
                          : 'fail'
                }
                content={
                    runCommandQueryInfo.isLoading
                        ? 'loading'
                        : runCommandQueryInfo.data
                          ? runCommandQueryInfo.data.result
                          : ''
                }
                errors={runCommandQueryInfo.data?.errors}
                commandName={lastCommand?.title}
                open={showResultDialog}
                onRerun={() => runCommandQuery(runCommandQueryInfo.originalArgs as string)}
                onClose={() => setShowResultDialog(false)}
            />
        </>
    );
};
