import {primitives} from '@app-dev-panel/sdk/Component/Theme/tokens';
import {styled} from '@mui/material/styles';
import React, {useMemo, useState} from 'react';
import {FilterInput} from './FilterInput';

type KeyValueRow = {key: string; value: string | number | React.ReactNode};

type KeyValueTableProps = {rows: KeyValueRow[]; labelWidth?: number; filterable?: boolean};

const Table = styled('table')({width: '100%', borderCollapse: 'collapse'});

const Td = styled('td')(({theme}) => ({
    padding: theme.spacing(0.875, 0),
    fontSize: '13px',
    borderBottom: `1px solid ${theme.palette.divider}`,
    verticalAlign: 'top',
}));

const LabelTd = styled(Td)(({theme}) => ({color: theme.palette.text.disabled, fontWeight: 500, fontSize: '12px'}));

const ValueTd = styled(Td)({fontFamily: primitives.fontFamilyMono, fontSize: '12px', wordBreak: 'break-all'});

const matchesFilter = (row: KeyValueRow, filter: string): boolean => {
    const lowerFilter = filter.toLowerCase();
    if (row.key.toLowerCase().includes(lowerFilter)) return true;
    if (typeof row.value === 'string') return row.value.toLowerCase().includes(lowerFilter);
    if (typeof row.value === 'number') return String(row.value).includes(lowerFilter);
    return false;
};

export const useKeyValueFilter = (rows: KeyValueRow[]) => {
    const [filter, setFilter] = useState('');

    const filteredRows = useMemo(() => {
        if (!filter) return rows;
        return rows.filter((row) => matchesFilter(row, filter));
    }, [rows, filter]);

    const filterAction = rows.length > 3 ? <FilterInput value={filter} onChange={setFilter} /> : null;

    return {filteredRows, filterAction};
};

export const KeyValueTable = ({rows, labelWidth = 160, filterable = false}: KeyValueTableProps) => {
    const {filteredRows, filterAction} = useKeyValueFilter(rows);

    return (
        <>
            {filterable && filterAction}
            <Table>
                <tbody>
                    {filteredRows.map((row) => (
                        <tr key={row.key}>
                            <LabelTd style={{width: labelWidth}}>{row.key}</LabelTd>
                            <ValueTd>{row.value}</ValueTd>
                        </tr>
                    ))}
                </tbody>
            </Table>
        </>
    );
};
