import {JsonRenderer} from '@app-dev-panel/panel/Module/Debug/Component/JsonRenderer';
import {EmptyState} from '@app-dev-panel/sdk/Component/EmptyState';
import {FilterInput} from '@app-dev-panel/sdk/Component/FilterInput';
import {SectionTitle} from '@app-dev-panel/sdk/Component/SectionTitle';
import {primitives} from '@app-dev-panel/sdk/Component/Theme/tokens';
import {parseFilePathWithLineAnchor} from '@app-dev-panel/sdk/Helper/filePathParser';
import {formatMicrotime} from '@app-dev-panel/sdk/Helper/formatDate';
import {Box, Chip, Collapse, Icon, IconButton, type Theme, Typography} from '@mui/material';
import {styled, useTheme} from '@mui/material/styles';
import {useDeferredValue, useMemo, useState} from 'react';

type Level = 'emergency' | 'alert' | 'critical' | 'error' | 'warning' | 'notice' | 'info' | 'debug';
type LogEntry = {context: object; level: Level; line: string; message: unknown; time: number};
type LogPanelProps = {data: LogEntry[]};

const levelColor = (level: string, theme: Theme): string => {
    switch (level) {
        case 'emergency':
        case 'alert':
        case 'critical':
        case 'error':
            return theme.palette.error.main;
        case 'warning':
            return theme.palette.warning.main;
        case 'notice':
            return theme.palette.primary.main;
        case 'info':
            return theme.palette.success.main;
        case 'debug':
            return theme.palette.text.disabled;
        default:
            return theme.palette.text.disabled;
    }
};

const SEVERITY_ORDER: Level[] = ['emergency', 'alert', 'critical', 'error', 'warning', 'notice', 'info', 'debug'];

const LogRow = styled(Box, {shouldForwardProp: (p) => p !== 'expanded'})<{expanded?: boolean}>(({theme, expanded}) => ({
    display: 'flex',
    alignItems: 'flex-start',
    gap: theme.spacing(1.5),
    padding: theme.spacing(1, 1.5),
    borderBottom: `1px solid ${theme.palette.divider}`,
    cursor: 'pointer',
    transition: 'background-color 0.1s ease',
    backgroundColor: expanded ? theme.palette.action.hover : 'transparent',
    '&:hover': {backgroundColor: theme.palette.action.hover},
}));

const TimeCell = styled(Typography)({
    fontFamily: primitives.fontFamilyMono,
    fontSize: '11px',
    flexShrink: 0,
    width: 110,
    paddingTop: 2,
});

const MessageCell = styled(Typography)({fontSize: '13px', flex: 1, wordBreak: 'break-word'});

const formatMessage = (message: unknown): string => (typeof message === 'string' ? message : JSON.stringify(message));

const DetailBox = styled(Box)(({theme}) => ({
    padding: theme.spacing(1.5, 1.5, 1.5, 15),
    backgroundColor: theme.palette.action.hover,
    borderBottom: `1px solid ${theme.palette.divider}`,
    fontSize: '12px',
}));

const FilterBar = styled(Box)(({theme}) => ({
    display: 'flex',
    alignItems: 'center',
    gap: theme.spacing(0.5),
    flexWrap: 'wrap',
}));

type LevelBadgeProps = {active?: boolean; badgeColor: string};

const LevelBadge = styled('button', {shouldForwardProp: (p) => p !== 'active' && p !== 'badgeColor'})<LevelBadgeProps>(
    ({theme, active, badgeColor}) => ({
        border: 'none',
        cursor: 'pointer',
        fontSize: '10px',
        fontWeight: 600,
        padding: '2px 8px',
        borderRadius: 10,
        whiteSpace: 'nowrap',
        transition: 'all 0.15s',
        backgroundColor: active ? badgeColor : theme.palette.action.hover,
        color: active ? theme.palette.common.white : theme.palette.text.secondary,
        opacity: active ? 1 : 0.6,
        '&:hover': {opacity: 1},
    }),
);

export const LogPanel = ({data}: LogPanelProps) => {
    const theme = useTheme();
    const [filter, setFilter] = useState('');
    const deferredFilter = useDeferredValue(filter);
    const [expandedIndex, setExpandedIndex] = useState<number | null>(null);
    const [activeLevels, setActiveLevels] = useState<Set<Level>>(new Set());

    if (!data || data.length === 0) {
        return <EmptyState icon="description" title="No logs found" />;
    }

    // Count entries per severity level
    const levelCounts = useMemo(() => {
        const counts = new Map<Level, number>();
        for (const entry of data) {
            counts.set(entry.level, (counts.get(entry.level) || 0) + 1);
        }
        return counts;
    }, [data]);

    const presentLevels = useMemo(
        () => SEVERITY_ORDER.filter((level) => (levelCounts.get(level) || 0) > 0),
        [levelCounts],
    );

    const toggleLevel = (level: Level) => {
        setActiveLevels((prev) => {
            const next = new Set(prev);
            if (next.has(level)) {
                next.delete(level);
            } else {
                next.add(level);
            }
            return next;
        });
        setExpandedIndex(null);
    };

    const filtered = useMemo(() => {
        let result = data;

        // Filter by active severity levels
        if (activeLevels.size > 0) {
            result = result.filter((e) => activeLevels.has(e.level));
        }

        // Filter by text search
        if (deferredFilter) {
            const lower = deferredFilter.toLowerCase();
            result = result.filter(
                (e) => formatMessage(e.message).toLowerCase().includes(lower) || e.level.toLowerCase().includes(lower),
            );
        }

        return result;
    }, [data, activeLevels, deferredFilter]);

    const action = (
        <FilterBar>
            {presentLevels.map((level) => (
                <LevelBadge
                    key={level}
                    active={activeLevels.has(level)}
                    badgeColor={levelColor(level, theme)}
                    onClick={() => toggleLevel(level)}
                >
                    {level.toUpperCase()} {levelCounts.get(level)}
                </LevelBadge>
            ))}
            <FilterInput value={filter} onChange={setFilter} placeholder="Filter logs..." />
        </FilterBar>
    );

    return (
        <Box>
            <SectionTitle action={action}>{`${filtered.length} log entries`}</SectionTitle>

            {filtered.map((entry, index) => {
                const expanded = expandedIndex === index;
                const color = levelColor(entry.level, theme);
                return (
                    <Box key={index}>
                        <LogRow expanded={expanded} onClick={() => setExpandedIndex(expanded ? null : index)}>
                            <TimeCell sx={{color: 'text.disabled'}}>{formatMicrotime(entry.time)}</TimeCell>
                            <Chip
                                label={entry.level.toUpperCase()}
                                size="small"
                                sx={{
                                    fontWeight: 600,
                                    fontSize: '10px',
                                    height: 20,
                                    minWidth: 60,
                                    backgroundColor: color,
                                    color: 'common.white',
                                    borderRadius: 1,
                                }}
                            />
                            <MessageCell>{formatMessage(entry.message)}</MessageCell>
                            <IconButton size="small" sx={{flexShrink: 0}}>
                                <Icon sx={{fontSize: 16}}>{expanded ? 'expand_less' : 'expand_more'}</Icon>
                            </IconButton>
                        </LogRow>
                        <Collapse in={expanded}>
                            <DetailBox>
                                {entry.line && (
                                    <Box sx={{mb: 1}}>
                                        <Typography
                                            variant="caption"
                                            component="a"
                                            href={`/inspector/files?path=${parseFilePathWithLineAnchor(entry.line)}`}
                                            sx={{
                                                fontFamily: primitives.fontFamilyMono,
                                                color: 'primary.main',
                                                textDecoration: 'none',
                                                '&:hover': {textDecoration: 'underline'},
                                            }}
                                        >
                                            {entry.line}
                                        </Typography>
                                    </Box>
                                )}
                                {entry.context && Object.keys(entry.context).length > 0 && (
                                    <JsonRenderer value={entry.context} depth={2} />
                                )}
                            </DetailBox>
                        </Collapse>
                    </Box>
                );
            })}
        </Box>
    );
};
