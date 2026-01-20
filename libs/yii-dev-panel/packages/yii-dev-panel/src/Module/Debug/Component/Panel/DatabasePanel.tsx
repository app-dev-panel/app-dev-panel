import ArrowForwardIosSharpIcon from '@mui/icons-material/ArrowForwardIosSharp';
import ExpandMoreIcon from '@mui/icons-material/ExpandMore';
import {TabContext, TabPanel} from '@mui/lab';
import TabList from '@mui/lab/TabList';
import {Alert, AlertTitle, styled, Tab, Typography} from '@mui/material';
import MuiAccordion, {AccordionProps} from '@mui/material/Accordion';
import MuiAccordionDetails from '@mui/material/AccordionDetails';
import MuiAccordionSummary, {AccordionSummaryProps} from '@mui/material/AccordionSummary';
import Box from '@mui/material/Box';
import {formatMillisecondsAsDuration} from '@yiisoft/yii-dev-panel-sdk/Helper/formatDate';
import {JsonRenderer} from '@yiisoft/yii-dev-panel/Module/Debug/Component/JsonRenderer';
import React, {SyntheticEvent, useState} from 'react';

const Accordion = styled((props: AccordionProps) => <MuiAccordion disableGutters elevation={0} square {...props} />)(
    ({theme}) => ({
        border: `1px solid ${theme.palette.divider}`,
        '&:not(:last-child)': {
            borderBottom: 0,
        },
        '&:before': {
            display: 'none',
        },
    }),
);

const AccordionSummary = styled((props: AccordionSummaryProps) => (
    <MuiAccordionSummary expandIcon={<ArrowForwardIosSharpIcon sx={{fontSize: '0.9rem'}} />} {...props} />
))(({theme}) => ({
    backgroundColor: theme.palette.mode === 'dark' ? 'rgba(255, 255, 255, .05)' : 'rgba(0, 0, 0, .03)',
    flexDirection: 'row-reverse',
    '& .MuiAccordionSummary-expandIconWrapper.Mui-expanded': {
        transform: 'rotate(90deg)',
    },
    '& .MuiAccordionSummary-content': {
        marginLeft: theme.spacing(1),
    },
}));

const AccordionDetails = styled(MuiAccordionDetails)(({theme}) => ({
    padding: theme.spacing(2),
    borderTop: '1px solid rgba(0, 0, 0, .125)',
}));

type TransactionsPanelProps = {
    tab: Keys;
};
const TransactionsPanel = ({tab}: TransactionsPanelProps) => {
    return (
        <TabPanel value={tab} sx={{padding: '0'}}>
            <Box m={2}>
                <Alert severity="warning">
                    <AlertTitle>Not supported yet</AlertTitle>
                </Alert>
            </Box>
        </TabPanel>
    );
};
type QueriesPanelProps = {
    tab: Keys;
    queries: Query[];
};
const QueriesPanel = ({tab, queries}: QueriesPanelProps) => {
    const [expanded, setExpanded] = React.useState<number>(-1);

    const handleAccordion = (panel: number) => (event: React.SyntheticEvent, isExpanded: boolean) => {
        setExpanded(isExpanded ? panel : -1);
    };
    if (!queries || queries.length === 0) {
        return (
            <TabPanel value={tab} sx={{padding: '0'}}>
                <Box m={2}>
                    <Alert severity="success">
                        <AlertTitle>No queries found during the process</AlertTitle>
                    </Alert>
                </Box>
            </TabPanel>
        );
    }

    return (
        <TabPanel value={tab} sx={{padding: '0'}}>
            {Object.values(queries).map((el: Query, index) => (
                <Accordion key={index} expanded={expanded === index} onChange={handleAccordion(index)}>
                    <AccordionSummary expandIcon={<ExpandMoreIcon />}>
                        <Typography sx={{flexGrow: 1}}>{el.sql}</Typography>
                        <Typography sx={{color: 'text.secondary'}}>{getQueryTime(el.actions)}</Typography>
                    </AccordionSummary>
                    <AccordionDetails>
                        {Object.keys(el.params).length > 0 && (
                            <>
                                <JsonRenderer value={el.params} />
                                <br />
                            </>
                        )}
                        <JsonRenderer value={el.rawSql} />
                    </AccordionDetails>
                </Accordion>
            ))}
        </TabPanel>
    );
};

type QueryAction = {
    action: 'query.start' | 'query.end';
    time: number;
};
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
type DatabasePanelProps = {
    data: {
        [key in Keys]?: Query[] | any;
    };
};

function getQueryTime(actions: QueryAction[]) {
    const start = actions.find((a) => a.action === 'query.start');
    const end = actions.find((a) => a.action === 'query.end');

    return formatMillisecondsAsDuration(end.time - start.time);
}

export const DatabasePanel = ({data}: DatabasePanelProps) => {
    const tabs = Object.keys(data) as Keys[];

    const [value, setValue] = useState<Keys>(tabs[0]);

    const handleChange = (event: SyntheticEvent, newValue: Keys) => {
        setValue(newValue);
    };

    if (!data || (data.queries?.length === 0 && data.transactions?.length === 0)) {
        return (
            <Box m={2}>
                <Alert severity="info">
                    <AlertTitle>No queries found during the process</AlertTitle>
                </Alert>
            </Box>
        );
    }
    return (
        <TabContext value={value}>
            <Box sx={{borderBottom: 1, borderColor: 'divider'}}>
                <TabList onChange={handleChange}>
                    {tabs.map((tab) => (
                        <Tab label={tab} value={tab} key={tab} />
                    ))}
                </TabList>
            </Box>
            {tabs.map((tab) => (
                <>
                    {tab === 'queries' ? (
                        <QueriesPanel key={tab} tab={tab} queries={data[tab]} />
                    ) : tab === 'transactions' ? (
                        <TransactionsPanel tab={tab} />
                    ) : null}
                </>
            ))}
        </TabContext>
    );
};
