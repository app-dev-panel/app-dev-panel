import {EmptyState} from '@app-dev-panel/sdk/Component/EmptyState';
import {FilterInput} from '@app-dev-panel/sdk/Component/FilterInput';
import {SectionTitle} from '@app-dev-panel/sdk/Component/SectionTitle';
import {primitives} from '@app-dev-panel/sdk/Component/Theme/tokens';
import {formatMillisecondsAsDuration} from '@app-dev-panel/sdk/Helper/formatDate';
import {Box, Chip, Collapse, Icon, IconButton, type Theme, Typography} from '@mui/material';
import {styled, useTheme} from '@mui/material/styles';
import {useDeferredValue, useState} from 'react';

type Message = {
    messageClass: string;
    bus: string;
    transport: string | null;
    dispatched: boolean;
    handled: boolean;
    failed: boolean;
    duration: number;
};
type MessengerPanelProps = {data: {messages: Message[]; messageCount: number; failedCount: number}};

function shortClassName(fqcn: string): string {
    const parts = fqcn.split('\\');
    return parts[parts.length - 1] ?? fqcn;
}

function durationColor(ms: number, theme: Theme): string {
    if (ms > 100) return theme.palette.error.main;
    if (ms > 30) return theme.palette.warning.main;
    return theme.palette.success.main;
}

const SummaryGrid = styled(Box)(({theme}) => ({
    display: 'grid',
    gridTemplateColumns: 'repeat(auto-fill, minmax(160px, 1fr))',
    gap: theme.spacing(2),
    marginBottom: theme.spacing(3),
}));

const SummaryCard = styled(Box)(({theme}) => ({
    padding: theme.spacing(2),
    borderRadius: theme.shape.borderRadius * 1.5,
    border: `1px solid ${theme.palette.divider}`,
    backgroundColor: theme.palette.background.paper,
}));

const SummaryLabel = styled(Typography)(({theme}) => ({
    fontSize: '11px',
    fontWeight: 600,
    textTransform: 'uppercase' as const,
    letterSpacing: '0.5px',
    color: theme.palette.text.disabled,
    marginBottom: theme.spacing(0.5),
}));

const SummaryValue = styled(Typography)({fontFamily: primitives.fontFamilyMono, fontWeight: 700, fontSize: '22px'});

const MessageRow = styled(Box, {shouldForwardProp: (p) => p !== 'expanded'})<{expanded?: boolean}>(
    ({theme, expanded}) => ({
        display: 'flex',
        alignItems: 'center',
        gap: theme.spacing(1.5),
        padding: theme.spacing(1, 1.5),
        borderBottom: `1px solid ${theme.palette.divider}`,
        cursor: 'pointer',
        transition: 'background-color 0.1s ease',
        backgroundColor: expanded ? theme.palette.action.hover : 'transparent',
        '&:hover': {backgroundColor: theme.palette.action.hover},
    }),
);

const ClassCell = styled(Typography)({
    fontFamily: primitives.fontFamilyMono,
    fontSize: '12px',
    flex: 1,
    wordBreak: 'break-word',
    minWidth: 0,
});

const DurationCell = styled(Typography)({
    fontFamily: primitives.fontFamilyMono,
    fontSize: '11px',
    flexShrink: 0,
    textAlign: 'right',
    width: 80,
});

const DetailBox = styled(Box)(({theme}) => ({
    padding: theme.spacing(1.5, 1.5, 1.5, 6),
    backgroundColor: theme.palette.action.hover,
    borderBottom: `1px solid ${theme.palette.divider}`,
    fontSize: '12px',
}));

export const MessengerPanel = ({data}: MessengerPanelProps) => {
    const theme = useTheme();
    const [filter, setFilter] = useState('');
    const deferredFilter = useDeferredValue(filter);
    const [expandedIndex, setExpandedIndex] = useState<number | null>(null);

    if (!data || !data.messages || data.messages.length === 0) {
        return <EmptyState icon="send" title="No messages found" />;
    }

    const filtered = deferredFilter
        ? data.messages.filter((m) => m.messageClass.toLowerCase().includes(deferredFilter.toLowerCase()))
        : data.messages;

    return (
        <Box>
            <SummaryGrid>
                <SummaryCard>
                    <SummaryLabel>Total Messages</SummaryLabel>
                    <SummaryValue sx={{color: 'primary.main'}}>{data.messageCount}</SummaryValue>
                </SummaryCard>
                <SummaryCard>
                    <SummaryLabel>Failed</SummaryLabel>
                    <SummaryValue sx={{color: data.failedCount > 0 ? 'error.main' : 'text.disabled'}}>
                        {data.failedCount}
                    </SummaryValue>
                </SummaryCard>
            </SummaryGrid>

            <SectionTitle
                action={<FilterInput value={filter} onChange={setFilter} placeholder="Filter messages..." />}
            >{`${filtered.length} messages`}</SectionTitle>

            {filtered.map((message, index) => {
                const expanded = expandedIndex === index;
                const color = durationColor(message.duration, theme);
                return (
                    <Box key={index}>
                        <MessageRow expanded={expanded} onClick={() => setExpandedIndex(expanded ? null : index)}>
                            <ClassCell>{shortClassName(message.messageClass)}</ClassCell>
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
                            <DurationCell sx={{color}}>{formatMillisecondsAsDuration(message.duration)}</DurationCell>
                            <IconButton size="small" sx={{flexShrink: 0}}>
                                <Icon sx={{fontSize: 16}}>{expanded ? 'expand_less' : 'expand_more'}</Icon>
                            </IconButton>
                        </MessageRow>
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
                            </DetailBox>
                        </Collapse>
                    </Box>
                );
            })}
        </Box>
    );
};
