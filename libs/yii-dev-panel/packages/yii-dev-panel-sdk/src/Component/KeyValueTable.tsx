import {styled} from '@mui/material/styles';
import {primitives} from '@yiisoft/yii-dev-panel-sdk/Component/Theme/tokens';

type KeyValueRow = {key: string; value: string | number | React.ReactNode};

type KeyValueTableProps = {rows: KeyValueRow[]; labelWidth?: number};

const Table = styled('table')(({theme}) => ({width: '100%', borderCollapse: 'collapse'}));

const Td = styled('td')(({theme}) => ({
    padding: theme.spacing(0.875, 0),
    fontSize: '13px',
    borderBottom: `1px solid ${theme.palette.divider}`,
    verticalAlign: 'top',
}));

const LabelTd = styled(Td)(({theme}) => ({color: theme.palette.text.disabled, fontWeight: 500, fontSize: '12px'}));

const ValueTd = styled(Td)({fontFamily: primitives.fontFamilyMono, fontSize: '12px', wordBreak: 'break-all'});

import React from 'react';

export const KeyValueTable = ({rows, labelWidth = 160}: KeyValueTableProps) => {
    return (
        <Table>
            <tbody>
                {rows.map((row) => (
                    <tr key={row.key}>
                        <LabelTd style={{width: labelWidth}}>{row.key}</LabelTd>
                        <ValueTd>{row.value}</ValueTd>
                    </tr>
                ))}
            </tbody>
        </Table>
    );
};
