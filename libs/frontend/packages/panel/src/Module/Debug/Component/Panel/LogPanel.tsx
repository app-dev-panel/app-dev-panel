import {JsonRenderer} from '@app-dev-panel/panel/Module/Debug/Component/JsonRenderer';
import {SectionTitle} from '@app-dev-panel/sdk/Component/SectionTitle';
import {primitives} from '@app-dev-panel/sdk/Component/Theme/tokens';
import {parseFilePathWithLineAnchor} from '@app-dev-panel/sdk/Helper/filePathParser';
import {formatMicrotime} from '@app-dev-panel/sdk/Helper/formatDate';
import {Alert, AlertTitle, Box, Chip, Collapse, Icon, IconButton, TextField, Typography} from '@mui/material';
import {styled} from '@mui/material/styles';
import {useState} from 'react';

type Level = 'emergency' | 'alert' | 'critical' | 'error' | 'warning' | 'notice' | 'info' | 'debug';
type LogEntry = {context: object; level: Level; line: string; message: string; time: number};
type LogPanelProps = {data: LogEntry[]};

const levelColor: Record<string, string> = {
    emergency: primitives.red600,
    alert: primitives.red600,
    critical: primitives.red600,
    error: primitives.red600,
    warning: primitives.amber600,
    notice: primitives.blue500,
    info: primitives.green600,
    debug: primitives.gray400,
};

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

const DetailBox = styled(Box)(({theme}) => ({
    padding: theme.spacing(1.5, 1.5, 1.5, 15),
    backgroundColor: theme.palette.action.hover,
    borderBottom: `1px solid ${theme.palette.divider}`,
    fontSize: '12px',
}));

export const LogPanel = ({data}: LogPanelProps) => {
    const [filter, setFilter] = useState('');
    const [expandedIndex, setExpandedIndex] = useState<number | null>(null);

    if (!data || data.length === 0) {
        return (
            <Box m={2}>
                <Alert severity="info">
                    <AlertTitle>No logs found during the process</AlertTitle>
                </Alert>
            </Box>
        );
    }

    const filtered = filter
        ? data.filter(
              (e) =>
                  e.message.toLowerCase().includes(filter.toLowerCase()) ||
                  e.level.toLowerCase().includes(filter.toLowerCase()),
          )
        : data;

    return (
        <Box>
            <Box sx={{display: 'flex', alignItems: 'center', gap: 2, mb: 2}}>
                <SectionTitle>{`${filtered.length} log entries`}</SectionTitle>
                <TextField
                    size="small"
                    placeholder="Filter logs..."
                    value={filter}
                    onChange={(e) => setFilter(e.target.value)}
                    InputProps={{sx: {fontSize: '13px'}}}
                    sx={{ml: 'auto', width: 240}}
                />
            </Box>

            {filtered.map((entry, index) => {
                const expanded = expandedIndex === index;
                const color = levelColor[entry.level] || primitives.gray400;
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
                                    color: '#fff',
                                    borderRadius: 1,
                                }}
                            />
                            <MessageCell>{entry.message}</MessageCell>
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
