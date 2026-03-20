import {EmptyState} from '@app-dev-panel/sdk/Component/EmptyState';
import {FilterInput} from '@app-dev-panel/sdk/Component/FilterInput';
import {SectionTitle} from '@app-dev-panel/sdk/Component/SectionTitle';
import {primitives} from '@app-dev-panel/sdk/Component/Theme/tokens';
import {formatMillisecondsAsDuration} from '@app-dev-panel/sdk/Helper/formatDate';
import {Box, type Theme, Typography} from '@mui/material';
import {styled, useTheme} from '@mui/material/styles';
import {useDeferredValue, useState} from 'react';

type Render = {template: string; renderTime: number};
type TwigPanelProps = {data: {renders: Render[]; totalTime: number; renderCount: number}};

function durationColor(ms: number, theme: Theme): string {
    if (ms > 100) return theme.palette.error.main;
    if (ms > 30) return theme.palette.warning.main;
    return theme.palette.success.main;
}

const RenderRow = styled(Box)(({theme}) => ({
    display: 'flex',
    alignItems: 'center',
    gap: theme.spacing(1.5),
    padding: theme.spacing(1, 1.5),
    borderBottom: `1px solid ${theme.palette.divider}`,
    transition: 'background-color 0.1s ease',
    '&:hover': {backgroundColor: theme.palette.action.hover},
}));

const TemplateCell = styled(Typography)({
    fontFamily: primitives.fontFamilyMono,
    fontSize: '12px',
    flex: 1,
    wordBreak: 'break-all',
    minWidth: 0,
});

const DurationCell = styled(Typography)({
    fontFamily: primitives.fontFamilyMono,
    fontSize: '11px',
    flexShrink: 0,
    textAlign: 'right',
    width: 80,
});

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

export const TwigPanel = ({data}: TwigPanelProps) => {
    const theme = useTheme();
    const [filter, setFilter] = useState('');
    const deferredFilter = useDeferredValue(filter);

    if (!data || !data.renders || data.renders.length === 0) {
        return <EmptyState icon="code" title="No Twig renders found" />;
    }

    const filtered = deferredFilter
        ? data.renders.filter((r) => r.template.toLowerCase().includes(deferredFilter.toLowerCase()))
        : data.renders;

    return (
        <Box>
            <SummaryGrid>
                <SummaryCard>
                    <SummaryLabel>Templates Rendered</SummaryLabel>
                    <SummaryValue sx={{color: 'primary.main'}}>{data.renderCount}</SummaryValue>
                </SummaryCard>
                <SummaryCard>
                    <SummaryLabel>Total Render Time</SummaryLabel>
                    <SummaryValue sx={{color: 'text.primary', fontSize: '18px'}}>
                        {formatMillisecondsAsDuration(data.totalTime)}
                    </SummaryValue>
                </SummaryCard>
            </SummaryGrid>

            <SectionTitle
                action={<FilterInput value={filter} onChange={setFilter} placeholder="Filter templates..." />}
            >{`${filtered.length} renders`}</SectionTitle>

            {filtered.map((render, index) => {
                const color = durationColor(render.renderTime, theme);
                return (
                    <RenderRow key={index}>
                        <TemplateCell>{render.template}</TemplateCell>
                        <DurationCell sx={{color}}>{formatMillisecondsAsDuration(render.renderTime)}</DurationCell>
                    </RenderRow>
                );
            })}
        </Box>
    );
};
