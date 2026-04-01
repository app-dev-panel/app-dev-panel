import {searchVariants} from '@app-dev-panel/sdk/Helper/layoutTranslit';
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

const ValueTd = styled(Td)(({theme}) => ({
    fontFamily: theme.adp.fontFamilyMono,
    fontSize: '12px',
    wordBreak: 'break-all',
}));

const matchesFilter = (row: KeyValueRow, filter: string): boolean => {
    const variants = searchVariants(filter.toLowerCase());
    const key = row.key.toLowerCase();
    if (variants.some((v) => key.includes(v))) return true;
    if (typeof row.value === 'string') {
        const val = row.value.toLowerCase();
        return variants.some((v) => val.includes(v));
    }
    if (typeof row.value === 'number') {
        const val = String(row.value);
        return variants.some((v) => val.includes(v));
    }
    return false;
};

export const useKeyValueFilter = (rows: KeyValueRow[]) => {
    const [filter, setFilter] = useState('');

    const filteredRows = useMemo(() => {
        if (!filter) return rows;
        return rows.filter((row) => matchesFilter(row, filter));
    }, [rows, filter]);

    const filterAction = <FilterInput value={filter} onChange={setFilter} />;

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
