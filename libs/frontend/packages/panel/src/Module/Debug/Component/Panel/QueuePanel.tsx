import {JsonRenderer} from '@app-dev-panel/panel/Module/Debug/Component/JsonRenderer';
import {EmptyState} from '@app-dev-panel/sdk/Component/EmptyState';
import {FilterInput} from '@app-dev-panel/sdk/Component/FilterInput';
import {SectionTitle} from '@app-dev-panel/sdk/Component/SectionTitle';
import {primitives} from '@app-dev-panel/sdk/Component/Theme/tokens';
import {formatMillisecondsAsDuration} from '@app-dev-panel/sdk/Helper/formatDate';
import {TabContext, TabPanel} from '@mui/lab';
import TabList from '@mui/lab/TabList';
import {Box, Chip, Collapse, Icon, IconButton, Tab, type Theme, Typography} from '@mui/material';
import {styled, useTheme} from '@mui/material/styles';
import {SyntheticEvent, useDeferredValue, useState} from 'react';

type Message = {
    messageClass: string;
    bus: string;
    transport: string | null;
    dispatched: boolean;
    handled: boolean;
    failed: boolean;
    duration: number;
    message?: any;
};

type QueuePanelProps = {
    data: {
        pushes: Record<string, {message: any; middlewares: any[]}[]>;
        statuses: {id: string; status: string}[];
        processingMessages: Record<string, any[]>;
        messages?: Message[];
        messageCount?: number;
        failedCount?: number;
    };
};

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

const ItemRow = styled(Box, {shouldForwardProp: (p) => p !== 'expanded'})<{expanded?: boolean}>(
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

const DetailBox = styled(Box)(({theme}) => ({
    padding: theme.spacing(1.5, 1.5, 1.5, 6),
    backgroundColor: theme.palette.action.hover,
    borderBottom: `1px solid ${theme.palette.divider}`,
    fontSize: '12px',
}));

const StatusRow = styled(Box)(({theme}) => ({
    display: 'flex',
    alignItems: 'center',
    gap: theme.spacing(1.5),
    padding: theme.spacing(1, 1.5),
    borderBottom: `1px solid ${theme.palette.divider}`,
    transition: 'background-color 0.1s ease',
    '&:hover': {backgroundColor: theme.palette.action.hover},
}));

const IdCell = styled(Typography)({
    fontFamily: primitives.fontFamilyMono,
    fontSize: '12px',
    flex: 1,
    wordBreak: 'break-all',
    minWidth: 0,
});

function statusColor(status: string, theme: ReturnType<typeof useTheme>): string {
    switch (status.toLowerCase()) {
        case 'done':
        case 'completed':
            return theme.palette.success.main;
        case 'failed':
        case 'error':
            return theme.palette.error.main;
        case 'waiting':
        case 'pending':
            return theme.palette.warning.main;
        default:
            return theme.palette.text.disabled;
    }
}

function shortClassName(fqcn: string): string {
    const parts = fqcn.split('\\');
    return parts[parts.length - 1] ?? fqcn;
}

function messageDurationColor(ms: number, theme: Theme): string {
    if (ms > 100) return theme.palette.error.main;
    if (ms > 30) return theme.palette.warning.main;
    return theme.palette.success.main;
}

const MessageClassCell = styled(Typography)({
    fontFamily: primitives.fontFamilyMono,
    fontSize: '12px',
    flex: 1,
    wordBreak: 'break-word',
    minWidth: 0,
});

const MessageDurationCell = styled(Typography)({
    fontFamily: primitives.fontFamilyMono,
    fontSize: '11px',
    flexShrink: 0,
    textAlign: 'right',
    width: 80,
});

const MessagesView = ({messages}: {messages: Message[]}) => {
    const theme = useTheme();
    const [filter, setFilter] = useState('');
    const deferredFilter = useDeferredValue(filter);
    const [expandedIndex, setExpandedIndex] = useState<number | null>(null);

    if (!messages || messages.length === 0) {
        return <EmptyState icon="send" title="No messages found" />;
    }

    const filtered = deferredFilter
        ? messages.filter((m) => m.messageClass.toLowerCase().includes(deferredFilter.toLowerCase()))
        : messages;

    return (
        <Box>
            <SectionTitle action={<FilterInput value={filter} onChange={setFilter} placeholder="Filter messages..." />}>
                {`${filtered.length} messages`}
            </SectionTitle>
            {filtered.map((message, index) => {
                const expanded = expandedIndex === index;
                const color = messageDurationColor(message.duration, theme);
                return (
                    <Box key={index}>
                        <ItemRow expanded={expanded} onClick={() => setExpandedIndex(expanded ? null : index)}>
                            <MessageClassCell>{shortClassName(message.messageClass)}</MessageClassCell>
                            <Chip
                                label={message.bus}
                                size="small"
                                variant="outlined"
                                sx={{
                                    fontFamily: primitives.fontFamilyMono,
                                    fontSize: '10px',
                                    height: 20,
                                    borderRadius: 1,
                                    flexShrink: 0,
                                }}
                            />
                            {message.transport && (
                                <Chip
                                    label={message.transport}
                                    size="small"
                                    variant="outlined"
                                    sx={{
                                        fontFamily: primitives.fontFamilyMono,
                                        fontSize: '10px',
                                        height: 20,
                                        borderRadius: 1,
                                        flexShrink: 0,
                                    }}
                                />
                            )}
                            {message.failed ? (
                                <Chip
                                    label="FAILED"
                                    size="small"
                                    sx={{
                                        fontWeight: 700,
                                        fontSize: '9px',
                                        height: 18,
                                        minWidth: 50,
                                        borderRadius: 1,
                                        backgroundColor: theme.palette.error.main,
                                        color: 'common.white',
                                        flexShrink: 0,
                                    }}
                                />
                            ) : message.handled ? (
                                <Chip
                                    label="HANDLED"
                                    size="small"
                                    sx={{
                                        fontWeight: 700,
                                        fontSize: '9px',
                                        height: 18,
                                        minWidth: 50,
                                        borderRadius: 1,
                                        backgroundColor: theme.palette.success.main,
                                        color: 'common.white',
                                        flexShrink: 0,
                                    }}
                                />
                            ) : message.dispatched ? (
                                <Chip
                                    label="DISPATCHED"
                                    size="small"
                                    sx={{
                                        fontWeight: 700,
                                        fontSize: '9px',
                                        height: 18,
                                        minWidth: 50,
                                        borderRadius: 1,
                                        backgroundColor: theme.palette.warning.main,
                                        color: 'common.white',
                                        flexShrink: 0,
                                    }}
                                />
                            ) : null}
                            <MessageDurationCell sx={{color}}>
                                {formatMillisecondsAsDuration(message.duration)}
                            </MessageDurationCell>
                            <IconButton size="small" sx={{flexShrink: 0}}>
                                <Icon sx={{fontSize: 16}}>{expanded ? 'expand_less' : 'expand_more'}</Icon>
                            </IconButton>
                        </ItemRow>
                        <Collapse in={expanded}>
                            <DetailBox>
                                <Typography sx={{fontSize: '11px', fontWeight: 600, color: 'text.disabled', mb: 0.5}}>
                                    Full Class Name
                                </Typography>
                                <Typography
                                    sx={{
                                        fontFamily: primitives.fontFamilyMono,
                                        fontSize: '12px',
                                        color: 'text.secondary',
                                        wordBreak: 'break-all',
                                    }}
                                >
                                    {message.messageClass}
                                </Typography>
                                {message.message != null && (
                                    <Box sx={{mt: 1.5}}>
                                        <Typography
                                            sx={{fontSize: '11px', fontWeight: 600, color: 'text.disabled', mb: 0.5}}
                                        >
                                            Message Data
                                        </Typography>
                                        <JsonRenderer value={message.message} />
                                    </Box>
                                )}
                            </DetailBox>
                        </Collapse>
                    </Box>
                );
            })}
        </Box>
    );
};

const PushesView = ({pushes}: {pushes: Record<string, {message: any; middlewares: any[]}[]>}) => {
    const [expandedKey, setExpandedKey] = useState<string | null>(null);
    const queueNames = Object.keys(pushes);

    if (queueNames.length === 0) {
        return <EmptyState icon="upload" title="No pushes found" />;
    }

    const totalPushes = queueNames.reduce((sum, name) => sum + pushes[name].length, 0);

    return (
        <Box>
            <SectionTitle>{`${totalPushes} pushes across ${queueNames.length} queues`}</SectionTitle>
            {queueNames.map((queueName) => (
                <Box key={queueName}>
                    <Box sx={{px: 1.5, py: 1, backgroundColor: 'action.selected'}}>
                        <Typography sx={{fontSize: '12px', fontWeight: 600}}>
                            {queueName}
                            <Chip
                                label={pushes[queueName].length}
                                size="small"
                                sx={{fontSize: '10px', height: 18, minWidth: 24, borderRadius: 1, ml: 1}}
                            />
                        </Typography>
                    </Box>
                    {pushes[queueName].map((push, index) => {
                        const key = `${queueName}-${index}`;
                        const expanded = expandedKey === key;
                        return (
                            <Box key={key}>
                                <ItemRow expanded={expanded} onClick={() => setExpandedKey(expanded ? null : key)}>
                                    <Typography sx={{fontFamily: primitives.fontFamilyMono, fontSize: '12px', flex: 1}}>
                                        Message #{index + 1}
                                    </Typography>
                                    {push.middlewares.length > 0 && (
                                        <Chip
                                            label={`${push.middlewares.length} middleware${push.middlewares.length !== 1 ? 's' : ''}`}
                                            size="small"
                                            variant="outlined"
                                            sx={{fontSize: '10px', height: 20, borderRadius: 1, flexShrink: 0}}
                                        />
                                    )}
                                    <IconButton size="small" sx={{flexShrink: 0}}>
                                        <Icon sx={{fontSize: 16}}>{expanded ? 'expand_less' : 'expand_more'}</Icon>
                                    </IconButton>
                                </ItemRow>
                                <Collapse in={expanded}>
                                    <DetailBox>
                                        <Typography
                                            sx={{fontSize: '11px', fontWeight: 600, color: 'text.disabled', mb: 0.5}}
                                        >
                                            Message
                                        </Typography>
                                        <JsonRenderer value={push.message} />
                                        {push.middlewares.length > 0 && (
                                            <Box sx={{mt: 1.5}}>
                                                <Typography
                                                    sx={{
                                                        fontSize: '11px',
                                                        fontWeight: 600,
                                                        color: 'text.disabled',
                                                        mb: 0.5,
                                                    }}
                                                >
                                                    Middlewares
                                                </Typography>
                                                <JsonRenderer value={push.middlewares} />
                                            </Box>
                                        )}
                                    </DetailBox>
                                </Collapse>
                            </Box>
                        );
                    })}
                </Box>
            ))}
        </Box>
    );
};

const StatusesView = ({statuses}: {statuses: {id: string; status: string}[]}) => {
    const theme = useTheme();

    if (!statuses || statuses.length === 0) {
        return <EmptyState icon="info" title="No statuses found" />;
    }

    return (
        <Box>
            <SectionTitle>{`${statuses.length} statuses`}</SectionTitle>
            {statuses.map((entry, index) => (
                <StatusRow key={index}>
                    <IdCell>{entry.id}</IdCell>
                    <Chip
                        label={entry.status.toUpperCase()}
                        size="small"
                        sx={{
                            fontWeight: 700,
                            fontSize: '9px',
                            height: 18,
                            minWidth: 60,
                            borderRadius: 1,
                            backgroundColor: statusColor(entry.status, theme),
                            color: 'common.white',
                            flexShrink: 0,
                        }}
                    />
                </StatusRow>
            ))}
        </Box>
    );
};

const ProcessingView = ({processingMessages}: {processingMessages: Record<string, any[]>}) => {
    const [expandedKey, setExpandedKey] = useState<string | null>(null);
    const queueNames = Object.keys(processingMessages);

    if (queueNames.length === 0) {
        return <EmptyState icon="hourglass_empty" title="No processing messages found" />;
    }

    const totalProcessing = queueNames.reduce((sum, name) => sum + processingMessages[name].length, 0);

    return (
        <Box>
            <SectionTitle>{`${totalProcessing} processing across ${queueNames.length} queues`}</SectionTitle>
            {queueNames.map((queueName) => (
                <Box key={queueName}>
                    <Box sx={{px: 1.5, py: 1, backgroundColor: 'action.selected'}}>
                        <Typography sx={{fontSize: '12px', fontWeight: 600}}>
                            {queueName}
                            <Chip
                                label={processingMessages[queueName].length}
                                size="small"
                                sx={{fontSize: '10px', height: 18, minWidth: 24, borderRadius: 1, ml: 1}}
                            />
                        </Typography>
                    </Box>
                    {processingMessages[queueName].map((msg, index) => {
                        const key = `${queueName}-${index}`;
                        const expanded = expandedKey === key;
                        return (
                            <Box key={key}>
                                <ItemRow expanded={expanded} onClick={() => setExpandedKey(expanded ? null : key)}>
                                    <Typography sx={{fontFamily: primitives.fontFamilyMono, fontSize: '12px', flex: 1}}>
                                        Processing #{index + 1}
                                    </Typography>
                                    <IconButton size="small" sx={{flexShrink: 0}}>
                                        <Icon sx={{fontSize: 16}}>{expanded ? 'expand_less' : 'expand_more'}</Icon>
                                    </IconButton>
                                </ItemRow>
                                <Collapse in={expanded}>
                                    <DetailBox>
                                        <JsonRenderer value={msg} />
                                    </DetailBox>
                                </Collapse>
                            </Box>
                        );
                    })}
                </Box>
            ))}
        </Box>
    );
};

type TabKey = 'messages' | 'pushes' | 'statuses' | 'processing';

export const QueuePanel = ({data}: QueuePanelProps) => {
    if (!data) {
        return <EmptyState icon="queue" title="No queue data found" />;
    }

    const messageCount = data.messages?.length ?? 0;
    const pushCount = Object.values(data.pushes ?? {}).reduce((sum, items) => sum + items.length, 0);
    const statusCount = data.statuses?.length ?? 0;
    const processingCount = Object.values(data.processingMessages ?? {}).reduce((sum, items) => sum + items.length, 0);

    const hasMessages = messageCount > 0;
    const hasQueueOps = pushCount > 0 || statusCount > 0 || processingCount > 0;

    if (!hasMessages && !hasQueueOps) {
        return <EmptyState icon="queue" title="No queue operations found" />;
    }

    // Only messages — render directly without tabs
    if (hasMessages && !hasQueueOps) {
        return <MessagesView messages={data.messages ?? []} />;
    }

    // Only queue ops — render queue tabs without messages tab
    if (!hasMessages && hasQueueOps) {
        return (
            <QueueTabsView
                data={data}
                pushCount={pushCount}
                statusCount={statusCount}
                processingCount={processingCount}
            />
        );
    }

    // Both messages and queue ops — render all tabs
    return (
        <QueueTabsView
            data={data}
            pushCount={pushCount}
            statusCount={statusCount}
            processingCount={processingCount}
            showMessages
            messageCount={messageCount}
        />
    );
};

const QueueTabsView = ({
    data,
    pushCount,
    statusCount,
    processingCount,
    showMessages = false,
    messageCount = 0,
}: {
    data: QueuePanelProps['data'];
    pushCount: number;
    statusCount: number;
    processingCount: number;
    showMessages?: boolean;
    messageCount?: number;
}) => {
    const defaultTab: TabKey = showMessages ? 'messages' : 'pushes';
    const [value, setValue] = useState<TabKey>(defaultTab);
    const handleChange = (_event: SyntheticEvent, newValue: TabKey) => setValue(newValue);

    return (
        <Box>
            <TabContext value={value}>
                <Box sx={{borderBottom: 1, borderColor: 'divider'}}>
                    <StyledTabList onChange={handleChange}>
                        {showMessages && (
                            <Tab
                                label={
                                    <Box sx={{display: 'flex', alignItems: 'center', gap: 0.5}}>
                                        Messages
                                        <Chip
                                            label={messageCount}
                                            size="small"
                                            sx={{fontSize: '10px', height: 18, minWidth: 24, borderRadius: 1}}
                                        />
                                    </Box>
                                }
                                value="messages"
                            />
                        )}
                        <Tab
                            label={
                                <Box sx={{display: 'flex', alignItems: 'center', gap: 0.5}}>
                                    Pushes
                                    <Chip
                                        label={pushCount}
                                        size="small"
                                        sx={{fontSize: '10px', height: 18, minWidth: 24, borderRadius: 1}}
                                    />
                                </Box>
                            }
                            value="pushes"
                        />
                        <Tab
                            label={
                                <Box sx={{display: 'flex', alignItems: 'center', gap: 0.5}}>
                                    Statuses
                                    <Chip
                                        label={statusCount}
                                        size="small"
                                        sx={{fontSize: '10px', height: 18, minWidth: 24, borderRadius: 1}}
                                    />
                                </Box>
                            }
                            value="statuses"
                        />
                        <Tab
                            label={
                                <Box sx={{display: 'flex', alignItems: 'center', gap: 0.5}}>
                                    Processing
                                    <Chip
                                        label={processingCount}
                                        size="small"
                                        sx={{fontSize: '10px', height: 18, minWidth: 24, borderRadius: 1}}
                                    />
                                </Box>
                            }
                            value="processing"
                        />
                    </StyledTabList>
                </Box>
                {showMessages && (
                    <TabPanel value="messages" sx={{padding: 0}}>
                        <MessagesView messages={data.messages ?? []} />
                    </TabPanel>
                )}
                <TabPanel value="pushes" sx={{padding: 0}}>
                    <PushesView pushes={data.pushes ?? {}} />
                </TabPanel>
                <TabPanel value="statuses" sx={{padding: 0}}>
                    <StatusesView statuses={data.statuses ?? []} />
                </TabPanel>
                <TabPanel value="processing" sx={{padding: 0}}>
                    <ProcessingView processingMessages={data.processingMessages ?? {}} />
                </TabPanel>
            </TabContext>
        </Box>
    );
};
