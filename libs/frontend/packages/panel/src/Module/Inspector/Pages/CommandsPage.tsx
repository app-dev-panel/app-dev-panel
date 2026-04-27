import {
    CommandType,
    useGetCommandsQuery,
    useRunCommandMutation,
} from '@app-dev-panel/panel/Module/Inspector/API/Inspector';
import {
    CommandButton,
    type CommandRunStatus,
} from '@app-dev-panel/panel/Module/Inspector/Component/Command/CommandButton';
import {CommandErrorAlert} from '@app-dev-panel/panel/Module/Inspector/Component/Command/CommandErrorAlert';
import {extractCommandError} from '@app-dev-panel/panel/Module/Inspector/Component/Command/extractCommandError';
import {ResultDialog} from '@app-dev-panel/panel/Module/Inspector/Component/Command/ResultDialog';
import {EmptyState} from '@app-dev-panel/sdk/Component/EmptyState';
import {FilterInput} from '@app-dev-panel/sdk/Component/FilterInput';
import {FullScreenCircularProgress} from '@app-dev-panel/sdk/Component/FullScreenCircularProgress';
import {PageHeader} from '@app-dev-panel/sdk/Component/PageHeader';
import {PageToolbar} from '@app-dev-panel/sdk/Component/PageToolbar';
import {QueryErrorState} from '@app-dev-panel/sdk/Component/QueryErrorState';
import {searchVariants} from '@app-dev-panel/sdk/Helper/layoutTranslit';
import {Box, Icon, Link, Typography} from '@mui/material';
import {useDeferredValue, useEffect, useMemo, useState} from 'react';

type GroupedCommands = Record<string, CommandType[]>;
type CommandRunState = {status: CommandRunStatus};
type CommandStatusMap = Record<string, CommandRunState>;

const groupHeadingIcon: Record<string, string> = {
    test: 'science',
    tests: 'science',
    analyse: 'insights',
    analyze: 'insights',
    analysis: 'insights',
    composer: 'inventory_2',
    coverage: 'pie_chart',
    build: 'construction',
    deploy: 'rocket_launch',
    db: 'storage',
    database: 'storage',
    cache: 'memory',
    server: 'dns',
};

const formatGroupLabel = (group: string): string => {
    if (!group) return 'Other';
    return group.charAt(0).toUpperCase() + group.slice(1);
};

const resolveGroupHeadingIcon = (group: string): string => groupHeadingIcon[group.toLowerCase()] ?? 'terminal';

export const CommandsPage = () => {
    const {data: commands, isLoading, isError, error, refetch} = useGetCommandsQuery();
    const [runCommandQuery, runCommandQueryInfo] = useRunCommandMutation();

    const [commandStatus, setCommandStatus] = useState<CommandStatusMap>({});
    const [showResultDialog, setShowResultDialog] = useState<boolean>(false);
    const [runError, setRunError] = useState<string[] | null>(null);
    const [lastCommand, setLastCommand] = useState<CommandType | null>(null);
    const [activeCommandName, setActiveCommandName] = useState<string | null>(null);
    const [filter, setFilter] = useState('');
    const deferredFilter = useDeferredValue(filter);

    const filteredCommands = useMemo<CommandType[]>(() => {
        if (!commands) return [];
        const query = deferredFilter.trim().toLowerCase();
        if (!query) return commands;
        const variants = searchVariants(query).map((v) => v.toLowerCase());
        return commands.filter((command) => {
            const haystack = [command.title, command.description, command.group, command.name]
                .filter(Boolean)
                .join(' ')
                .toLowerCase();
            return variants.some((v) => haystack.includes(v));
        });
    }, [commands, deferredFilter]);

    const groupedCommands = useMemo<GroupedCommands>(() => {
        const grouped: GroupedCommands = {};
        for (const command of filteredCommands) {
            (grouped[command.group || 'Other'] ??= []).push(command);
        }
        return grouped;
    }, [filteredCommands]);

    const totalCount = commands?.length ?? 0;
    const visibleCount = filteredCommands.length;

    useEffect(() => {
        if (!commands) return;
        const status: CommandStatusMap = {};
        for (const command of commands) {
            status[command.name] = {status: 'idle'};
        }
        setCommandStatus(status);
    }, [commands]);

    const runCommand = async (command: CommandType) => {
        setRunError(null);
        setLastCommand(command);
        setActiveCommandName(command.name);
        setCommandStatus((prev) => ({...prev, [command.name]: {status: 'loading'}}));
        const response = await runCommandQuery(command.name);

        const commandError = extractCommandError(response);
        if (!('data' in response) || !response.data) {
            setCommandStatus((prev) => ({...prev, [command.name]: {status: 'error'}}));
            setRunError(commandError?.errors ?? ['An unexpected error occurred']);
            return;
        }

        setCommandStatus((prev) => ({
            ...prev,
            [command.name]: {status: response.data.status === 'ok' ? 'success' : 'error'},
        }));
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

    if (totalCount === 0) {
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

    const countLabel =
        deferredFilter.trim().length > 0 && visibleCount !== totalCount
            ? `${visibleCount} of ${totalCount} commands`
            : `${totalCount} ${totalCount === 1 ? 'command' : 'commands'}`;

    return (
        <>
            <PageHeader title="Commands" icon="terminal" description="Run application commands" />
            <PageToolbar actions={<FilterInput value={filter} onChange={setFilter} placeholder="Filter commands..." />}>
                {countLabel}
            </PageToolbar>
            {commandEntries.length === 0 ? (
                <EmptyState
                    icon="filter_alt_off"
                    title="No matching commands"
                    description="Try a different filter or clear the search."
                />
            ) : (
                <Box sx={{display: 'flex', flexDirection: 'column', gap: 3, mt: 2}}>
                    {commandEntries.map(([groupName, commands]) => (
                        <Box key={groupName} component="section">
                            <Box sx={{display: 'flex', alignItems: 'center', gap: 1, mb: 1.5, color: 'text.secondary'}}>
                                <Icon className="material-icons" sx={{fontSize: 18, color: 'text.secondary'}}>
                                    {resolveGroupHeadingIcon(groupName)}
                                </Icon>
                                <Typography
                                    variant="overline"
                                    sx={{fontWeight: 700, letterSpacing: '0.6px', color: 'text.secondary'}}
                                >
                                    {formatGroupLabel(groupName)}
                                </Typography>
                                <Box
                                    sx={{
                                        display: 'inline-flex',
                                        alignItems: 'center',
                                        justifyContent: 'center',
                                        minWidth: 22,
                                        height: 18,
                                        px: 0.75,
                                        borderRadius: 999,
                                        fontSize: '11px',
                                        fontWeight: 600,
                                        bgcolor: 'action.hover',
                                        color: 'text.secondary',
                                    }}
                                >
                                    {commands.length}
                                </Box>
                            </Box>
                            <Box
                                sx={{
                                    display: 'grid',
                                    gap: 1.5,
                                    gridTemplateColumns: {
                                        xs: '1fr',
                                        sm: 'repeat(2, minmax(0, 1fr))',
                                        lg: 'repeat(3, minmax(0, 1fr))',
                                    },
                                }}
                            >
                                {commands.map((command) => (
                                    <CommandButton
                                        key={command.name}
                                        title={command.title}
                                        description={command.description}
                                        group={command.group}
                                        status={commandStatus[command.name]?.status ?? 'idle'}
                                        disabled={runCommandQueryInfo.isLoading && activeCommandName !== command.name}
                                        onClick={() => runCommand(command)}
                                        fullWidth
                                    />
                                ))}
                            </Box>
                        </Box>
                    ))}
                </Box>
            )}
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
