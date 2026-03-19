import {primitives} from '@app-dev-panel/sdk/Component/Theme/tokens';
import {InputAdornment, TextField} from '@mui/material';
import {styled} from '@mui/material/styles';
import React, {useMemo, useState} from 'react';

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

export const KeyValueTable = ({rows, labelWidth = 160, filterable = false}: KeyValueTableProps) => {
    const [filter, setFilter] = useState('');

    const filteredRows = useMemo(() => {
        if (!filter) return rows;
        return rows.filter((row) => matchesFilter(row, filter));
    }, [rows, filter]);

    return (
        <>
            {filterable && rows.length > 3 && (
                <TextField
                    size="small"
                    placeholder="Filter..."
                    value={filter}
                    onChange={(e) => setFilter(e.target.value)}
                    slotProps={{
                        input: {
                            startAdornment: (
                                <InputAdornment position="start" sx={{color: 'text.disabled', fontSize: '14px'}}>
                                    /
                                </InputAdornment>
                            ),
                        },
                    }}
                    sx={{
                        mb: 1,
                        maxWidth: 260,
                        '& .MuiOutlinedInput-root': {fontSize: '12px', height: 30},
                        '& .MuiInputAdornment-root': {mr: 0},
                    }}
                />
            )}
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
