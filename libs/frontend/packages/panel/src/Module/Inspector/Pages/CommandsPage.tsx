import {useBreadcrumbs} from '@app-dev-panel/panel/Application/Context/BreadcrumbsContext';
import {
    CommandType,
    useLazyGetCommandsQuery,
    useRunCommandMutation,
} from '@app-dev-panel/panel/Module/Inspector/API/Inspector';
import {ResultDialog} from '@app-dev-panel/panel/Module/Inspector/Component/Command/ResultDialog';
import {InfoBox} from '@app-dev-panel/sdk/Component/InfoBox';
import {EmojiObjects} from '@mui/icons-material';
import {Button, CircularProgress, Link, Typography} from '@mui/material';
import Box from '@mui/material/Box';
import {useEffect, useState} from 'react';

type GroupedCommands = Record<string, CommandType[]>;
type CommandStatusMap = Record<string, {isLoading: boolean; response: null | any}>;
export const CommandsPage = () => {
    const [groupedCommands, setGroupedCommands] = useState<GroupedCommands>({});
    const [commandStatus, setCommandStatus] = useState<CommandStatusMap>({});
    const [showResultDialog, setShowResultDialog] = useState<boolean>(false);

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
            }
        })();
    }, []);

    useBreadcrumbs(() => ['Inspector', 'Commands']);

    const runCommand = async (command: CommandType) => {
        setCommandStatus((prev) => ({...prev, [command.name]: {...prev[command.name], isLoading: true}}));
        const response = await runCommandQuery(command.name);
        setCommandStatus((prev) => ({...prev, [command.name]: {...prev[command.name], isLoading: false}}));
        setShowResultDialog(true);
        console.log(response);
    };
    const commandEntries = Object.entries(groupedCommands as GroupedCommands);

    if (commandEntries.length === 0) {
        return (
            <InfoBox
                title="No commands found"
                text={
                    <>
                        <Typography>
                            Add a command to the "app-dev-panel/api" section into "params.php" on the backend to be able
                            to run the command from the Yii Dev Panel.
                        </Typography>
                        <Typography>
                            You may inspect the section with{' '}
                            <Link href="/inspector/config/parameters?filter=app-dev-panel/api">Inspector</Link>.
                        </Typography>
                        <Typography>
                            See more information on the link{' '}
                            <Link href="https://github.com/app-dev-panel/app-dev-panel">
                                https://github.com/app-dev-panel/app-dev-panel
                            </Link>
                            .
                        </Typography>
                    </>
                }
                severity="info"
                icon={<EmojiObjects />}
            />
        );
    }

    console.log(runCommandQueryInfo.data?.result);
    return (
        <>
            {commandEntries.map(([groupName, commands], index) => (
                <Box key={index}>
                    <h2>{groupName}</h2>
                    {commands.map((command, index) => (
                        <Button
                            key={index}
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
                open={showResultDialog}
                onRerun={() => runCommandQuery(runCommandQueryInfo.originalArgs as string)}
                onClose={() => setShowResultDialog(false)}
            />
        </>
    );
};
