import {JsonRenderer} from '@app-dev-panel/panel/Module/Debug/Component/JsonRenderer';
import {EmptyState} from '@app-dev-panel/sdk/Component/EmptyState';
import {SectionTitle} from '@app-dev-panel/sdk/Component/SectionTitle';
import {primitives} from '@app-dev-panel/sdk/Component/Theme/tokens';
import {Box, Chip, Collapse, Icon, IconButton, Typography} from '@mui/material';
import {styled, useTheme} from '@mui/material/styles';
import {useMemo, useState} from 'react';

type Validation = {value: any; rules: any; result: boolean; errors: any};
type ValidatorPanelProps = {data: Validation[]};

const SummaryGrid = styled(Box)(({theme}) => ({
    display: 'grid',
    gridTemplateColumns: 'repeat(auto-fill, minmax(160px, 1fr))',
    gap: theme.spacing(2),
    marginBottom: theme.spacing(3),
}));

const SummaryCard = styled(Box)(({theme}) => ({
    padding: theme.spacing(2),
    borderRadius: theme.shape.borderRadius * 1.5,
    border: `1px solid ${theme.palette.divider}`,
    backgroundColor: theme.palette.background.paper,
}));

const SummaryLabel = styled(Typography)(({theme}) => ({
    fontSize: '11px',
    fontWeight: 600,
    textTransform: 'uppercase' as const,
    letterSpacing: '0.5px',
    color: theme.palette.text.disabled,
    marginBottom: theme.spacing(0.5),
}));

const SummaryValue = styled(Typography)({fontFamily: primitives.fontFamilyMono, fontWeight: 700, fontSize: '22px'});

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

function truncateValue(value: any): string {
    const str = typeof value === 'string' ? value : JSON.stringify(value);
    return str.length > 80 ? str.substring(0, 80) + '...' : str;
}

function getErrorCount(errors: any): number {
    if (!errors) return 0;
    if (Array.isArray(errors)) return errors.length;
    if (typeof errors === 'object') return Object.keys(errors).length;
    return 0;
}

export const ValidatorPanel = ({data}: ValidatorPanelProps) => {
    const theme = useTheme();
    const [expandedIndex, setExpandedIndex] = useState<number | null>(null);

    const {validCount, invalidCount} = useMemo(() => {
        let valid = 0;
        let invalid = 0;
        for (const v of data ?? []) {
            if (v.result) valid++;
            else invalid++;
        }
        return {validCount: valid, invalidCount: invalid};
    }, [data]);

    if (!data || data.length === 0) {
        return <EmptyState icon="check_circle" title="No validations found" />;
    }

    return (
        <Box>
            <SummaryGrid>
                <SummaryCard>
                    <SummaryLabel>Total</SummaryLabel>
                    <SummaryValue sx={{color: 'primary.main'}}>{data.length}</SummaryValue>
                </SummaryCard>
                <SummaryCard>
                    <SummaryLabel>Valid</SummaryLabel>
                    <SummaryValue sx={{color: 'success.main'}}>{validCount}</SummaryValue>
                </SummaryCard>
                <SummaryCard>
                    <SummaryLabel>Invalid</SummaryLabel>
                    <SummaryValue sx={{color: invalidCount > 0 ? 'error.main' : 'text.disabled'}}>
                        {invalidCount}
                    </SummaryValue>
                </SummaryCard>
            </SummaryGrid>

            <SectionTitle>{`${data.length} validations`}</SectionTitle>

            {data.map((validation, index) => {
                const expanded = expandedIndex === index;
                return (
                    <Box key={index}>
                        <ValidationRow expanded={expanded} onClick={() => setExpandedIndex(expanded ? null : index)}>
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
                            {getErrorCount(validation.errors) > 0 && (
                                <Chip
                                    label={`${getErrorCount(validation.errors)} error${getErrorCount(validation.errors) !== 1 ? 's' : ''}`}
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
                                {getErrorCount(validation.errors) > 0 && (
                                    <Box>
                                        <Typography
                                            sx={{fontSize: '11px', fontWeight: 600, color: 'text.disabled', mb: 0.5}}
                                        >
                                            Errors
                                        </Typography>
                                        <JsonRenderer value={validation.errors} />
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
