import {JsonRenderer} from '@app-dev-panel/panel/Module/Debug/Component/JsonRenderer';
import {EmptyState} from '@app-dev-panel/sdk/Component/EmptyState';
import {FilterInput} from '@app-dev-panel/sdk/Component/FilterInput';
import {SectionTitle} from '@app-dev-panel/sdk/Component/SectionTitle';
import {primitives} from '@app-dev-panel/sdk/Component/Theme/tokens';
import {concatClassMethod} from '@app-dev-panel/sdk/Helper/classMethodConcater';
import {formatMillisecondsAsDuration} from '@app-dev-panel/sdk/Helper/formatDate';
import {TabContext, TabPanel} from '@mui/lab';
import TabList from '@mui/lab/TabList';
import {Box, Chip, Collapse, Icon, IconButton, Tab, Typography} from '@mui/material';
import {styled} from '@mui/material/styles';
import {SyntheticEvent, useDeferredValue, useMemo, useState} from 'react';

type SummaryItemType = {
    class: string;
    method: string;
    count: number;
    successCount: number;
    times: number[];
    maxTime: number;
};

type ServiceData = {
    service: string;
    class: string;
    method: string;
    arguments: any[];
    result: any;
    status: 'success' | 'error';
    error: null | string;
    timeStart: number;
    timeEnd: number;
};
type ServicesPanelProps = {data: ServiceData[]};

type Tabs = 'summary' | 'all';

const ServiceRow = styled(Box, {shouldForwardProp: (p) => p !== 'expanded'})<{expanded?: boolean}>(
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

const MethodCell = styled(Typography)({
    fontFamily: primitives.fontFamilyMono,
    fontSize: '12px',
    flex: 1,
    wordBreak: 'break-word',
});

const DetailBox = styled(Box)(({theme}) => ({
    padding: theme.spacing(1.5, 1.5, 1.5, 6),
    backgroundColor: theme.palette.action.hover,
    borderBottom: `1px solid ${theme.palette.divider}`,
    fontSize: '12px',
}));

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

const SummaryView = ({summaryRows}: {summaryRows: Record<string, SummaryItemType>}) => {
    const rows = Object.values(summaryRows);
    const [expandedIndex, setExpandedIndex] = useState<number | null>(null);

    return (
        <Box>
            {rows.map((row, index) => {
                const expanded = expandedIndex === index;
                const total = row.times.reduce((acc, v) => acc + v, 0);
                const avg = total / row.times.length;
                const errors = row.count - row.successCount;

                return (
                    <Box key={index}>
                        <ServiceRow expanded={expanded} onClick={() => setExpandedIndex(expanded ? null : index)}>
                            <MethodCell>{concatClassMethod(row.class, row.method)}</MethodCell>
                            <Chip
                                label={`${row.count} call${row.count !== 1 ? 's' : ''}`}
                                size="small"
                                sx={{fontSize: '10px', height: 20, borderRadius: 1, flexShrink: 0}}
                                variant="outlined"
                            />
                            {errors > 0 && (
                                <Chip
                                    label={`${errors} err`}
                                    size="small"
                                    sx={{
                                        fontSize: '10px',
                                        height: 20,
                                        borderRadius: 1,
                                        backgroundColor: 'error.light',
                                        color: 'error.main',
                                        flexShrink: 0,
                                    }}
                                />
                            )}
                            <Typography
                                sx={{
                                    fontFamily: primitives.fontFamilyMono,
                                    fontSize: '11px',
                                    color: 'text.disabled',
                                    flexShrink: 0,
                                    width: 80,
                                    textAlign: 'right',
                                }}
                            >
                                {formatMillisecondsAsDuration(total)}
                            </Typography>
                            <IconButton size="small" sx={{flexShrink: 0}}>
                                <Icon sx={{fontSize: 16}}>{expanded ? 'expand_less' : 'expand_more'}</Icon>
                            </IconButton>
                        </ServiceRow>
                        <Collapse in={expanded}>
                            <DetailBox>
                                <Box sx={{display: 'flex', gap: 3, mb: 1}}>
                                    <Typography variant="caption" sx={{color: 'text.disabled'}}>
                                        Total: {formatMillisecondsAsDuration(total)}
                                    </Typography>
                                    <Typography variant="caption" sx={{color: 'text.disabled'}}>
                                        Max: {formatMillisecondsAsDuration(row.maxTime)}
                                    </Typography>
                                    <Typography variant="caption" sx={{color: 'text.disabled'}}>
                                        Avg: {formatMillisecondsAsDuration(avg)}
                                    </Typography>
                                </Box>
                            </DetailBox>
                        </Collapse>
                    </Box>
                );
            })}
        </Box>
    );
};

type AllRow = {
    class: string;
    method: string;
    time: number;
    success: number;
    arguments: any[];
    result: any;
    error: null | string;
};

const AllView = ({rows}: {rows: AllRow[]}) => {
    const [filter, setFilter] = useState('');
    const deferredFilter = useDeferredValue(filter);
    const [expandedIndex, setExpandedIndex] = useState<number | null>(null);

    const filtered = deferredFilter
        ? rows.filter((r) => concatClassMethod(r.class, r.method).toLowerCase().includes(deferredFilter.toLowerCase()))
        : rows;

    return (
        <Box>
            <SectionTitle
                action={<FilterInput value={filter} onChange={setFilter} placeholder="Filter services..." />}
            >{`${filtered.length} calls`}</SectionTitle>

            {filtered.map((row, index) => {
                const expanded = expandedIndex === index;
                const isError = !row.success;

                return (
                    <Box key={index}>
                        <ServiceRow expanded={expanded} onClick={() => setExpandedIndex(expanded ? null : index)}>
                            <MethodCell>{concatClassMethod(row.class, row.method)}</MethodCell>
                            <Chip
                                label={isError ? 'ERROR' : 'OK'}
                                size="small"
                                sx={{
                                    fontWeight: 600,
                                    fontSize: '10px',
                                    height: 20,
                                    minWidth: 40,
                                    backgroundColor: isError ? 'error.main' : 'success.main',
                                    color: 'common.white',
                                    borderRadius: 1,
                                    flexShrink: 0,
                                }}
                            />
                            <Typography
                                sx={{
                                    fontFamily: primitives.fontFamilyMono,
                                    fontSize: '11px',
                                    color: 'text.disabled',
                                    flexShrink: 0,
                                    width: 80,
                                    textAlign: 'right',
                                }}
                            >
                                {formatMillisecondsAsDuration(row.time)}
                            </Typography>
                            <IconButton size="small" sx={{flexShrink: 0}}>
                                <Icon sx={{fontSize: 16}}>{expanded ? 'expand_less' : 'expand_more'}</Icon>
                            </IconButton>
                        </ServiceRow>
                        <Collapse in={expanded}>
                            <DetailBox>
                                <Box sx={{mb: 1.5}}>
                                    <Typography
                                        sx={{fontSize: '11px', fontWeight: 600, color: 'text.disabled', mb: 0.5}}
                                    >
                                        Arguments
                                    </Typography>
                                    <JsonRenderer
                                        value={row.arguments.length === 1 ? row.arguments[0] : row.arguments}
                                    />
                                </Box>
                                <Box>
                                    <Typography
                                        sx={{fontSize: '11px', fontWeight: 600, color: 'text.disabled', mb: 0.5}}
                                    >
                                        {row.error ? 'Error' : 'Result'}
                                    </Typography>
                                    <JsonRenderer value={row.error ? row.error : row.result} />
                                </Box>
                            </DetailBox>
                        </Collapse>
                    </Box>
                );
            })}
        </Box>
    );
};

export const ServicesPanel = ({data}: ServicesPanelProps) => {
    const [value, setValue] = useState<Tabs>('summary');

    const handleChange = (event: SyntheticEvent, newValue: Tabs) => {
        setValue(newValue);
    };

    const allRows = useMemo(() => {
        if (!Array.isArray(data)) return [];
        return data.map((el) => ({
            class: el.class,
            method: el.method,
            success: Number(el.status === 'success'),
            time: el.timeEnd - el.timeStart,
            arguments: el.arguments,
            result: el.result,
            error: el.error,
        }));
    }, [data]);

    const summaryRows = useMemo(() => {
        const result: Record<string, SummaryItemType> = {};
        for (const el of allRows) {
            const key = el.class + el.method;
            if (key in result) {
                result[key].count += 1;
                result[key].successCount += el.success;
                result[key].times = [...result[key].times, el.time];
                if (el.time > result[key].maxTime) result[key].maxTime = el.time;
            } else {
                result[key] = {
                    class: el.class,
                    method: el.method,
                    count: 1,
                    successCount: el.success,
                    maxTime: el.time,
                    times: [el.time],
                };
            }
        }
        return result;
    }, [allRows]);

    if (!data || data.length === 0) {
        return <EmptyState icon="miscellaneous_services" title="No spied services found" />;
    }

    return (
        <Box>
            <TabContext value={value}>
                <Box sx={{borderBottom: 1, borderColor: 'divider'}}>
                    <StyledTabList onChange={handleChange}>
                        <Tab label="Summary" value="summary" />
                        <Tab label="All" value="all" />
                    </StyledTabList>
                </Box>
                <TabPanel value="summary" sx={{padding: 0}}>
                    <SummaryView summaryRows={summaryRows} />
                </TabPanel>
                <TabPanel value="all" sx={{padding: 0}}>
                    <AllView rows={allRows} />
                </TabPanel>
            </TabContext>
        </Box>
    );
};
