import {
    CommandType,
    useGetCommandsQuery,
    useRunCommandMutation,
} from '@app-dev-panel/panel/Module/Inspector/API/Inspector';
import {CommandErrorAlert} from '@app-dev-panel/panel/Module/Inspector/Component/Command/CommandErrorAlert';
import {extractCommandError} from '@app-dev-panel/panel/Module/Inspector/Component/Command/extractCommandError';
import {ResultDialog} from '@app-dev-panel/panel/Module/Inspector/Component/Command/ResultDialog';
import {EmptyState} from '@app-dev-panel/sdk/Component/EmptyState';
import {FullScreenCircularProgress} from '@app-dev-panel/sdk/Component/FullScreenCircularProgress';
import {PageHeader} from '@app-dev-panel/sdk/Component/PageHeader';
import {QueryErrorState} from '@app-dev-panel/sdk/Component/QueryErrorState';
import {Box, Button, CircularProgress, Link, Typography} from '@mui/material';
import {useEffect, useMemo, useState} from 'react';

type GroupedCommands = Record<string, CommandType[]>;
type CommandStatusMap = Record<string, {isLoading: boolean; response: null | unknown}>;

export const CommandsPage = () => {
    const {data: commands, isLoading, isError, error, refetch} = useGetCommandsQuery();
    const [runCommandQuery, runCommandQueryInfo] = useRunCommandMutation();

    const [commandStatus, setCommandStatus] = useState<CommandStatusMap>({});
    const [showResultDialog, setShowResultDialog] = useState<boolean>(false);
    const [runError, setRunError] = useState<string[] | null>(null);
    const [lastCommand, setLastCommand] = useState<CommandType | null>(null);

    const groupedCommands = useMemo<GroupedCommands>(() => {
        if (!commands) return {};
        const grouped: GroupedCommands = {};
        for (const command of commands) {
            (grouped[command.group] ??= []).push(command);
        }
        return grouped;
    }, [commands]);

    useEffect(() => {
        if (!commands) return;
        const status: CommandStatusMap = {};
        for (const command of commands) {
            status[command.name] = {isLoading: false, response: null};
        }
        setCommandStatus(status);
    }, [commands]);

    const runCommand = async (command: CommandType) => {
        setRunError(null);
        setLastCommand(command);
        setCommandStatus((prev) => ({...prev, [command.name]: {...prev[command.name], isLoading: true}}));
        const response = await runCommandQuery(command.name);
        setCommandStatus((prev) => ({...prev, [command.name]: {...prev[command.name], isLoading: false}}));

        const commandError = extractCommandError(response);
        if (!('data' in response) || !response.data) {
            setRunError(commandError?.errors ?? ['An unexpected error occurred']);
            return;
        }

        setShowResultDialog(true);
    };

    if (isLoading) {
        return <FullScreenCircularProgress />;
    }

    if (isError) {
        return (
            <>
                <PageHeader title="Commands" icon="terminal" description="Run application commands" />
                <QueryErrorState
                    error={error}
                    title="Failed to load commands"
                    fallback="Failed to load commands."
                    onRetry={refetch}
                />
            </>
        );
    }

    const commandEntries = Object.entries(groupedCommands);

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
                            <Link
                                href="https://github.com/app-dev-panel/app-dev-panel"
                                target="_blank"
                                rel="noopener noreferrer"
                            >
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
                            disabled={commandStatus[command.name]?.isLoading}
                            endIcon={
                                commandStatus[command.name]?.isLoading ? (
                                    <CircularProgress size={24} color="info" />
                                ) : null
                            }
                        >
                            Run {command.title}
                        </Button>
                    ))}
                </Box>
            ))}
            {runError && (
                <CommandErrorAlert
                    errors={runError}
                    onRetry={lastCommand ? () => runCommand(lastCommand) : undefined}
                    onDismiss={() => setRunError(null)}
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
