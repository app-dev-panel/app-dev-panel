import {JsonRenderer} from '@app-dev-panel/panel/Module/Debug/Component/JsonRenderer';
import {EmptyState} from '@app-dev-panel/sdk/Component/EmptyState';
import {FilterInput} from '@app-dev-panel/sdk/Component/FilterInput';
import {SectionTitle} from '@app-dev-panel/sdk/Component/SectionTitle';
import {primitives} from '@app-dev-panel/sdk/Component/Theme/tokens';
import {Box, Chip, Collapse, Icon, IconButton, Typography} from '@mui/material';
import {styled, useTheme} from '@mui/material/styles';
import {useMemo, useState} from 'react';

type Validation = {value: any; rules: any; result: boolean; errors: any};
type ValidatorPanelProps = {data: Validation[]};

const ValidationRow = styled(Box, {shouldForwardProp: (p) => p !== 'expanded'})<{expanded?: boolean}>(
    ({theme, expanded}) => ({
        display: 'flex',
        alignItems: 'center',
        gap: theme.spacing(1.5),
        padding: theme.spacing(1, 1.5),
        borderBottom: `1px solid ${theme.palette.divider}`,
        cursor: 'pointer',
        transition: 'background-color 0.1s ease',
        backgroundColor: expanded ? theme.palette.action.hover : 'transparent',
        '&:hover': {backgroundColor: theme.palette.action.hover},
    }),
);

const ValuePreview = styled(Typography)({
    fontFamily: primitives.fontFamilyMono,
    fontSize: '12px',
    flex: 1,
    wordBreak: 'break-word',
    minWidth: 0,
    overflow: 'hidden',
    textOverflow: 'ellipsis',
    whiteSpace: 'nowrap',
});

const DetailBox = styled(Box)(({theme}) => ({
    padding: theme.spacing(1.5, 1.5, 1.5, 6),
    backgroundColor: theme.palette.action.hover,
    borderBottom: `1px solid ${theme.palette.divider}`,
    fontSize: '12px',
}));

const ErrorTable = styled('table')(({theme}) => ({
    width: '100%',
    borderCollapse: 'collapse',
    fontSize: '12px',
    fontFamily: primitives.fontFamilyMono,
    '& th': {
        textAlign: 'left',
        padding: theme.spacing(0.5, 1),
        fontWeight: 600,
        fontSize: '10px',
        textTransform: 'uppercase',
        letterSpacing: '0.5px',
        color: theme.palette.text.disabled,
        borderBottom: `1px solid ${theme.palette.divider}`,
    },
    '& td': {
        padding: theme.spacing(0.5, 1),
        borderBottom: `1px solid ${theme.palette.divider}`,
        color: theme.palette.text.secondary,
    },
    '& td:first-of-type': {fontWeight: 600, color: theme.palette.error.main, whiteSpace: 'nowrap', width: 1},
}));

function truncateValue(value: any): string {
    const str = typeof value === 'string' ? value : JSON.stringify(value);
    return str.length > 80 ? str.substring(0, 80) + '...' : str;
}

function flattenErrors(errors: any): {field: string; message: string}[] {
    if (!errors || (Array.isArray(errors) && errors.length === 0)) return [];
    if (Array.isArray(errors)) {
        return errors.flatMap((e, i) => {
            if (typeof e === 'string') return [{field: String(i), message: e}];
            if (typeof e === 'object') return flattenErrors(e);
            return [{field: String(i), message: String(e)}];
        });
    }
    if (typeof errors === 'object') {
        const rows: {field: string; message: string}[] = [];
        for (const [field, msgs] of Object.entries(errors)) {
            if (Array.isArray(msgs)) {
                for (const msg of msgs) {
                    rows.push({field, message: String(msg)});
                }
            } else {
                rows.push({field, message: String(msgs)});
            }
        }
        return rows;
    }
    return [];
}

export const ValidatorPanel = ({data}: ValidatorPanelProps) => {
    const theme = useTheme();
    const [expandedIndex, setExpandedIndex] = useState<number | null>(null);
    const [filter, setFilter] = useState('');

    const {validCount, invalidCount} = useMemo(() => {
        let valid = 0;
        let invalid = 0;
        for (const v of data ?? []) {
            if (v.result) valid++;
            else invalid++;
        }
        return {validCount: valid, invalidCount: invalid};
    }, [data]);

    const filtered = useMemo(() => {
        if (!data) return [];
        if (!filter.trim()) return data;
        const q = filter.toLowerCase();
        return data.filter((v) => {
            const valueStr = typeof v.value === 'string' ? v.value : JSON.stringify(v.value);
            if (valueStr.toLowerCase().includes(q)) return true;
            const rulesStr = typeof v.rules === 'string' ? v.rules : JSON.stringify(v.rules);
            if (rulesStr.toLowerCase().includes(q)) return true;
            const errStr = JSON.stringify(v.errors);
            if (errStr.toLowerCase().includes(q)) return true;
            const status = v.result ? 'valid' : 'invalid';
            if (status.includes(q)) return true;
            return false;
        });
    }, [data, filter]);

    if (!data || data.length === 0) {
        return <EmptyState icon="check_circle" title="No validations found" />;
    }

    return (
        <Box>
            <SectionTitle
                action={
                    <Box sx={{display: 'flex', alignItems: 'center', gap: 1}}>
                        <Chip
                            label={`${validCount} valid`}
                            size="small"
                            sx={{
                                fontWeight: 600,
                                fontSize: '10px',
                                height: 20,
                                borderRadius: 1,
                                backgroundColor: theme.palette.success.main,
                                color: 'common.white',
                            }}
                        />
                        <Chip
                            label={`${invalidCount} invalid`}
                            size="small"
                            sx={{
                                fontWeight: 600,
                                fontSize: '10px',
                                height: 20,
                                borderRadius: 1,
                                backgroundColor:
                                    invalidCount > 0 ? theme.palette.error.main : theme.palette.text.disabled,
                                color: 'common.white',
                            }}
                        />
                        <FilterInput value={filter} onChange={setFilter} placeholder="Filter validations..." />
                    </Box>
                }
            >{`${filtered.length} validations`}</SectionTitle>

            {filtered.map((validation, index) => {
                const originalIndex = data.indexOf(validation);
                const expanded = expandedIndex === originalIndex;
                const errorRows = flattenErrors(validation.errors);
                return (
                    <Box key={originalIndex}>
                        <ValidationRow
                            expanded={expanded}
                            onClick={() => setExpandedIndex(expanded ? null : originalIndex)}
                        >
                            <Icon
                                sx={{
                                    fontSize: 18,
                                    color: validation.result ? theme.palette.success.main : theme.palette.error.main,
                                    flexShrink: 0,
                                }}
                            >
                                {validation.result ? 'check_circle' : 'cancel'}
                            </Icon>
                            <Chip
                                label={validation.result ? 'VALID' : 'INVALID'}
                                size="small"
                                sx={{
                                    fontWeight: 700,
                                    fontSize: '9px',
                                    height: 18,
                                    minWidth: 55,
                                    borderRadius: 1,
                                    backgroundColor: validation.result
                                        ? theme.palette.success.main
                                        : theme.palette.error.main,
                                    color: 'common.white',
                                    flexShrink: 0,
                                }}
                            />
                            <ValuePreview sx={{color: 'text.secondary'}}>
                                {truncateValue(validation.value)}
                            </ValuePreview>
                            {errorRows.length > 0 && (
                                <Chip
                                    label={`${errorRows.length} error${errorRows.length !== 1 ? 's' : ''}`}
                                    size="small"
                                    variant="outlined"
                                    sx={{
                                        fontSize: '10px',
                                        height: 20,
                                        borderRadius: 1,
                                        borderColor: theme.palette.error.main,
                                        color: theme.palette.error.main,
                                        flexShrink: 0,
                                    }}
                                />
                            )}
                            <IconButton size="small" sx={{flexShrink: 0}}>
                                <Icon sx={{fontSize: 16}}>{expanded ? 'expand_less' : 'expand_more'}</Icon>
                            </IconButton>
                        </ValidationRow>
                        <Collapse in={expanded}>
                            <DetailBox>
                                <Box sx={{mb: 1.5}}>
                                    <Typography
                                        sx={{fontSize: '11px', fontWeight: 600, color: 'text.disabled', mb: 0.5}}
                                    >
                                        Value
                                    </Typography>
                                    <JsonRenderer value={validation.value} />
                                </Box>
                                <Box sx={{mb: 1.5}}>
                                    <Typography
                                        sx={{fontSize: '11px', fontWeight: 600, color: 'text.disabled', mb: 0.5}}
                                    >
                                        Rules
                                    </Typography>
                                    <JsonRenderer value={validation.rules} />
                                </Box>
                                {errorRows.length > 0 && (
                                    <Box>
                                        <Typography
                                            sx={{fontSize: '11px', fontWeight: 600, color: 'text.disabled', mb: 0.5}}
                                        >
                                            Errors
                                        </Typography>
                                        <ErrorTable>
                                            <thead>
                                                <tr>
                                                    <th>Field</th>
                                                    <th>Message</th>
                                                </tr>
                                            </thead>
                                            <tbody>
                                                {errorRows.map((row, i) => (
                                                    <tr key={i}>
                                                        <td>{row.field}</td>
                                                        <td>{row.message}</td>
                                                    </tr>
                                                ))}
                                            </tbody>
                                        </ErrorTable>
                                    </Box>
                                )}
                            </DetailBox>
                        </Collapse>
                    </Box>
                );
            })}
        </Box>
    );
};
