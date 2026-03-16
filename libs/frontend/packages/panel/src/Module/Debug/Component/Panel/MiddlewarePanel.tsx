import {JsonRenderer} from '@app-dev-panel/panel/Module/Debug/Component/JsonRenderer';
import {useDebugEntry} from '@app-dev-panel/sdk/API/Debug/Context';
import {SectionTitle} from '@app-dev-panel/sdk/Component/SectionTitle';
import {primitives} from '@app-dev-panel/sdk/Component/Theme/tokens';
import {formatMicrotime} from '@app-dev-panel/sdk/Helper/formatDate';
import {parseObjectId} from '@app-dev-panel/sdk/Helper/objectString';
import {Alert, AlertTitle, Box, Chip, Collapse, Icon, IconButton, Tooltip, Typography} from '@mui/material';
import {styled} from '@mui/material/styles';
import {useState} from 'react';

type MiddlewareType = {memory: number; name: string; time: number};
type BeforeMiddlewareType = {request: string} & MiddlewareType;
type AfterMiddlewareType = {response: string} & MiddlewareType;
type ActionHandlerType = {memory: number; name: string; request: string; startTime: number; endTime: number};
type MiddlewarePanelProps = {
    beforeStack: BeforeMiddlewareType[];
    afterStack: AfterMiddlewareType[];
    actionHandler: ActionHandlerType;
};

type Phase = 'before' | 'handler' | 'after';

const phaseConfig: Record<Phase, {label: string; color: string}> = {
    before: {label: 'BEFORE', color: primitives.blue500},
    handler: {label: 'HANDLER', color: primitives.green600},
    after: {label: 'AFTER', color: primitives.amber600},
};

const MiddlewareRow = styled(Box, {shouldForwardProp: (p) => p !== 'expanded'})<{expanded?: boolean}>(
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

const TimeCell = styled(Typography)({
    fontFamily: primitives.fontFamilyMono,
    fontSize: '11px',
    flexShrink: 0,
    width: 110,
    paddingTop: 2,
});

const NameCell = styled(Typography)({fontSize: '13px', flex: 1, wordBreak: 'break-word'});

const DetailBox = styled(Box)(({theme}) => ({
    padding: theme.spacing(1.5, 1.5, 1.5, 15),
    backgroundColor: theme.palette.action.hover,
    borderBottom: `1px solid ${theme.palette.divider}`,
    fontSize: '12px',
}));

type RowData = {name: string; time: number; phase: Phase; payload?: string; memory: number};

export const MiddlewarePanel = (props: MiddlewarePanelProps) => {
    const {beforeStack, afterStack, actionHandler} = props;
    const [expandedIndex, setExpandedIndex] = useState<number | null>(null);
    const debugEntry = useDebugEntry();

    const rows: RowData[] = [];

    if (beforeStack?.length) {
        for (const m of beforeStack) {
            rows.push({name: m.name, time: m.time, phase: 'before', payload: m.request, memory: m.memory});
        }
    }

    if (typeof actionHandler === 'object' && !Array.isArray(actionHandler) && actionHandler) {
        rows.push({
            name: actionHandler.name,
            time: actionHandler.startTime,
            phase: 'handler',
            payload: actionHandler.request,
            memory: actionHandler.memory,
        });
    }

    if (afterStack?.length) {
        for (const m of afterStack) {
            rows.push({name: m.name, time: m.time, phase: 'after', payload: m.response, memory: m.memory});
        }
    }

    if (rows.length === 0) {
        return (
            <Box m={2}>
                <Alert severity="info">
                    <AlertTitle>No middleware data found during the process</AlertTitle>
                </Alert>
            </Box>
        );
    }

    return (
        <Box>
            <Box sx={{display: 'flex', alignItems: 'center', gap: 2, mb: 2}}>
                <SectionTitle>{`${rows.length} middleware steps`}</SectionTitle>
            </Box>

            {rows.map((row, index) => {
                const expanded = expandedIndex === index;
                const shortName = row.name.split('\\').pop() ?? row.name;
                const config = phaseConfig[row.phase];
                const objectId = parseObjectId(row.payload || '');

                return (
                    <Box key={index}>
                        <MiddlewareRow expanded={expanded} onClick={() => setExpandedIndex(expanded ? null : index)}>
                            <TimeCell sx={{color: 'text.disabled'}}>{formatMicrotime(row.time)}</TimeCell>
                            <Chip
                                label={config.label}
                                size="small"
                                sx={{
                                    fontWeight: 600,
                                    fontSize: '10px',
                                    height: 20,
                                    minWidth: 55,
                                    backgroundColor: config.color,
                                    color: '#fff',
                                    borderRadius: 1,
                                }}
                            />
                            <NameCell>
                                <Tooltip title={row.name}>
                                    <span>{shortName}</span>
                                </Tooltip>
                            </NameCell>
                            {row.memory > 0 && (
                                <Typography sx={{fontSize: '11px', color: 'text.disabled', flexShrink: 0}}>
                                    {(row.memory / 1024 / 1024).toFixed(1)} MB
                                </Typography>
                            )}
                            <IconButton size="small" sx={{flexShrink: 0}}>
                                <Icon sx={{fontSize: 16}}>{expanded ? 'expand_less' : 'expand_more'}</Icon>
                            </IconButton>
                        </MiddlewareRow>
                        <Collapse in={expanded}>
                            <DetailBox>
                                <Typography
                                    variant="caption"
                                    sx={{
                                        fontFamily: primitives.fontFamilyMono,
                                        color: 'text.secondary',
                                        display: 'block',
                                        mb: 1,
                                    }}
                                >
                                    {row.name}
                                </Typography>

                                {objectId && debugEntry && (
                                    <Box sx={{mb: 1}}>
                                        <Chip
                                            component="a"
                                            clickable
                                            href={`/debug/object?debugEntry=${debugEntry.id}&id=${objectId}`}
                                            label="Examine Object"
                                            size="small"
                                            icon={<Icon sx={{fontSize: '14px !important'}}>data_object</Icon>}
                                            sx={{fontSize: '11px', height: 24}}
                                            variant="outlined"
                                        />
                                    </Box>
                                )}

                                {row.payload && <JsonRenderer value={row.payload} depth={3} />}
                            </DetailBox>
                        </Collapse>
                    </Box>
                );
            })}
        </Box>
    );
};
