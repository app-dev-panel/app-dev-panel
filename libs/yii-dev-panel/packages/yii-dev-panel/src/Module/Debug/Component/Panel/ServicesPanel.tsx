import {TabContext, TabPanel} from '@mui/lab';
import TabList from '@mui/lab/TabList';
import {Alert, AlertTitle, Tab, Typography} from '@mui/material';
import Box from '@mui/material/Box';
import {GridColDef, GridValidRowModel} from '@mui/x-data-grid';
import {DataTable} from '@yiisoft/yii-dev-panel-sdk/Component/Grid';
import {concatClassMethod} from '@yiisoft/yii-dev-panel-sdk/Helper/classMethodConcater';
import {formatMillisecondsAsDuration} from '@yiisoft/yii-dev-panel-sdk/Helper/formatDate';
import {JsonRenderer} from '@yiisoft/yii-dev-panel/Module/Debug/Component/JsonRenderer';
import React, {SyntheticEvent, useMemo, useState} from 'react';

type SummaryItemType = {
    class: string;
    method: string;
    count: number;
    successCount: number;
    times: number[];
    maxTime: number;
};

type AllItemType = {
    service: string;
    class: string;
    method: string;
    time: number;
    success: number;
    arguments: any[];
    result: any;
    error: null | string;
};

const summaryColumns: GridColDef<SummaryItemType>[] = [
    {
        field: 'class',
        headerName: 'Method',
        flex: 1,
        renderCell: ({row}) => {
            return (
                <Typography component="span" sx={{wordBreak: 'break-word'}}>
                    {concatClassMethod(row.class, row.method)}
                </Typography>
            );
        },
    },
    {
        field: 'calls',
        headerName: 'Calls (Total / Error)',
        flex: 1,
        renderCell: ({row}) => {
            const errors = row.count - row.successCount;
            return (
                <>
                    {row.count} {errors > 0 && <> / {errors}</>}
                </>
            );
        },
    },
    {
        field: 'time',
        headerName: 'Time (Total / Max / Avg)',
        flex: 1,
        renderCell: ({row}) => {
            const total = row.times.reduce((acc, v) => acc + v, 0);
            const milliseconds = total / row.times.length;
            return (
                <>
                    {formatMillisecondsAsDuration(total)} / {formatMillisecondsAsDuration(row.maxTime)} /{' '}
                    {formatMillisecondsAsDuration(milliseconds)}
                </>
            );
        },
    },
];
const allColumns: GridColDef<AllItemType>[] = [
    {
        field: 'class',
        headerName: 'Method',
        flex: 1,
        renderCell: ({row}) => {
            return (
                <Typography component="span" sx={{wordBreak: 'break-word'}}>
                    {concatClassMethod(row.class, row.method)}
                </Typography>
            );
        },
    },
    {
        field: 'time',
        headerName: 'Time',
        flex: 0.5,
        renderCell: ({row}) => formatMillisecondsAsDuration(row.time),
    },
    {
        field: 'arguments',
        headerName: 'Arguments',
        flex: 3,
        renderCell: ({row}) => <JsonRenderer value={row.arguments.length === 1 ? row.arguments[0] : row.arguments} />,
    },
    {
        field: 'result',
        headerName: 'Result',
        flex: 3,
        renderCell: ({row}) => <JsonRenderer value={row.error ? row.error : row.result} />,
    },
];

type Tabs = 'summary' | 'all';
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
type ServicesPanelProps = {
    data: ServiceData[];
};

export const ServicesPanel = ({data}: ServicesPanelProps) => {
    const [value, setValue] = useState<Tabs>('summary');

    const handleChange = (event: SyntheticEvent, newValue: Tabs) => {
        setValue(newValue);
    };

    const allRows = useMemo(() => {
        if (!Array.isArray(data)) {
            return [];
        }
        return data.map(
            (el) =>
                ({
                    service: el.service,
                    class: el.class,
                    method: el.method,
                    success: Number(el.status === 'success'),
                    time: el.timeEnd - el.timeStart,
                    arguments: el.arguments,
                    result: el.result,
                    error: el.error,
                }) satisfies AllItemType,
        );
    }, [data]);

    const summaryRows = useMemo(() => {
        const result: Record<string, SummaryItemType> = {};
        for (const el of allRows) {
            const key = el.class + el.method;
            if (key in result) {
                result[key].count = result[key].count + 1;
                result[key].successCount = result[key].successCount + el.success;
                result[key].times = [...result[key].times, el.time];
                if (el.time > result[key].maxTime) {
                    result[key].maxTime = el.time;
                }
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
        return (
            <Box m={2}>
                <Alert severity="info">
                    <AlertTitle>No spied services found during the process</AlertTitle>
                </Alert>
            </Box>
        );
    }

    return (
        <TabContext value={value}>
            <Box sx={{borderBottom: 1, borderColor: 'divider'}}>
                <TabList onChange={handleChange}>
                    <Tab label={'Summary'} value={'summary'} />
                    <Tab label={'All'} value={'all'} />
                </TabList>
            </Box>
            <TabPanel value={'summary'} sx={{padding: '0'}}>
                <DataTable
                    getRowId={() => Math.random() * 1000}
                    rows={Object.values(summaryRows) as GridValidRowModel[]}
                    columns={summaryColumns}
                />
            </TabPanel>
            <TabPanel value={'all'} sx={{padding: '0'}}>
                <DataTable
                    sortModel={[{field: 'time', sort: 'desc'}]}
                    getRowId={() => Math.random() * 1000}
                    rows={allRows as GridValidRowModel[]}
                    columns={allColumns}
                />
            </TabPanel>
        </TabContext>
    );
};
