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

type QueryAction = {action: 'query.start' | 'query.end'; time: number};
type Query = {
    sql: string;
    rawSql: string;
    line: string;
    params: Record<string, number | string>;
    status: 'success';
    actions: QueryAction[];
    rowsNumber: number;
};
type Keys = 'queries' | 'transactions';
type DatabasePanelProps = {data: {[key in Keys]?: Query[] | any}};

function getQueryTime(actions: QueryAction[]) {
    const start = actions.find((a) => a.action === 'query.start');
    const end = actions.find((a) => a.action === 'query.end');
    return end && start ? end.time - start.time : 0;
}

function durationColor(ms: number, theme: Theme): string {
    if (ms > 100) return theme.palette.error.main;
    if (ms > 30) return theme.palette.warning.main;
    return theme.palette.success.main;
}

const QueryRow = styled(Box, {shouldForwardProp: (p) => p !== 'expanded'})<{expanded?: boolean}>(
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

const SqlCell = styled(Typography)({
    fontFamily: primitives.fontFamilyMono,
    fontSize: '12px',
    flex: 1,
    wordBreak: 'break-word',
    lineHeight: 1.6,
});

const DurationCell = styled(Typography)({
    fontFamily: primitives.fontFamilyMono,
    fontSize: '11px',
    flexShrink: 0,
    textAlign: 'right',
    width: 70,
    paddingTop: 2,
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

const QueriesView = ({queries}: {queries: Query[]}) => {
    const theme = useTheme();
    const [filter, setFilter] = useState('');
    const deferredFilter = useDeferredValue(filter);
    const [expandedIndex, setExpandedIndex] = useState<number | null>(null);

    if (!queries || queries.length === 0) {
        return <EmptyState icon="storage" title="No queries found" />;
    }

    const filtered = deferredFilter
        ? queries.filter((q) => q.sql.toLowerCase().includes(deferredFilter.toLowerCase()))
        : queries;

    const totalTime = queries.reduce((sum, q) => sum + getQueryTime(q.actions), 0);

    return (
        <Box>
            <SectionTitle
                action={<FilterInput value={filter} onChange={setFilter} placeholder="Filter SQL..." />}
            >{`${filtered.length} queries · ${formatMillisecondsAsDuration(totalTime)} total`}</SectionTitle>

            {filtered.map((query, index) => {
                const expanded = expandedIndex === index;
                const ms = getQueryTime(query.actions);
                const color = durationColor(ms, theme);

                return (
                    <Box key={index}>
                        <QueryRow expanded={expanded} onClick={() => setExpandedIndex(expanded ? null : index)}>
                            <Chip
                                label={query.sql.trim().split(/\s/)[0]?.toUpperCase()}
                                size="small"
                                sx={{
                                    fontWeight: 700,
                                    fontSize: '9px',
                                    height: 18,
                                    minWidth: 50,
                                    backgroundColor: 'primary.main',
                                    color: 'common.white',
                                    borderRadius: 1,
                                    flexShrink: 0,
                                    mt: '2px',
                                }}
                            />
                            <SqlCell>{query.sql}</SqlCell>
                            {query.rowsNumber != null && (
                                <Typography
                                    sx={{fontSize: '11px', color: 'text.disabled', flexShrink: 0, whiteSpace: 'nowrap'}}
                                >
                                    {query.rowsNumber} row{query.rowsNumber !== 1 ? 's' : ''}
                                </Typography>
                            )}
                            <DurationCell sx={{color}}>{formatMillisecondsAsDuration(ms)}</DurationCell>
                            <IconButton size="small" sx={{flexShrink: 0}}>
                                <Icon sx={{fontSize: 16}}>{expanded ? 'expand_less' : 'expand_more'}</Icon>
                            </IconButton>
                        </QueryRow>
                        <Collapse in={expanded}>
                            <DetailBox>
                                {Object.keys(query.params).length > 0 && (
                                    <Box sx={{mb: 1.5}}>
                                        <Typography
                                            sx={{fontSize: '11px', fontWeight: 600, color: 'text.disabled', mb: 0.5}}
                                        >
                                            Parameters
                                        </Typography>
                                        <JsonRenderer value={query.params} />
                                    </Box>
                                )}
                                <Typography sx={{fontSize: '11px', fontWeight: 600, color: 'text.disabled', mb: 0.5}}>
                                    Raw SQL
                                </Typography>
                                <Typography
                                    sx={{
                                        fontFamily: primitives.fontFamilyMono,
                                        fontSize: '12px',
                                        color: 'text.secondary',
                                        whiteSpace: 'pre-wrap',
                                        wordBreak: 'break-word',
                                    }}
                                >
                                    {typeof query.rawSql === 'string' ? query.rawSql : JSON.stringify(query.rawSql)}
                                </Typography>
                            </DetailBox>
                        </Collapse>
                    </Box>
                );
            })}
        </Box>
    );
};

export const DatabasePanel = ({data}: DatabasePanelProps) => {
    const tabs = Object.keys(data) as Keys[];
    const [value, setValue] = useState<Keys>(tabs[0]);

    const handleChange = (event: SyntheticEvent, newValue: Keys) => {
        setValue(newValue);
    };

    if (!data || (data.queries?.length === 0 && data.transactions?.length === 0)) {
        return <EmptyState icon="storage" title="No queries found" />;
    }

    return (
        <Box>
            <TabContext value={value}>
                <Box sx={{borderBottom: 1, borderColor: 'divider'}}>
                    <StyledTabList onChange={handleChange}>
                        {tabs.map((tab) => (
                            <Tab label={tab} value={tab} key={tab} />
                        ))}
                    </StyledTabList>
                </Box>
                {tabs.map((tab) => (
                    <TabPanel value={tab} key={tab} sx={{padding: 0}}>
                        {tab === 'queries' ? (
                            <QueriesView queries={data[tab]} />
                        ) : tab === 'transactions' ? (
                            <EmptyState icon="construction" title="Not supported yet" />
                        ) : null}
                    </TabPanel>
                ))}
            </TabContext>
        </Box>
    );
};
