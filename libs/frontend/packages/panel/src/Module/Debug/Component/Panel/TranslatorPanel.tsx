import {EmptyState} from '@app-dev-panel/sdk/Component/EmptyState';
import {FilterInput} from '@app-dev-panel/sdk/Component/FilterInput';
import {SectionTitle} from '@app-dev-panel/sdk/Component/SectionTitle';
import {primitives} from '@app-dev-panel/sdk/Component/Theme/tokens';
import {Box, Chip, Icon, Typography} from '@mui/material';
import {styled, useTheme} from '@mui/material/styles';
import {useDeferredValue, useMemo, useState} from 'react';

type Translation = {
    category: string;
    locale: string;
    message: string;
    translation: string | null;
    missing: boolean;
    fallbackLocale: string | null;
};

type TranslatorData = {
    translations: Translation[];
    missingCount: number;
    totalCount: number;
    locales: string[];
    categories: string[];
};

type TranslatorPanelProps = {data: TranslatorData};

// ---------------------------------------------------------------------------
// Styled components
// ---------------------------------------------------------------------------

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

const TranslationRow = styled(Box, {shouldForwardProp: (p) => p !== 'isMissing'})<{isMissing?: boolean}>(
    ({theme, isMissing}) => ({
        display: 'flex',
        alignItems: 'center',
        gap: theme.spacing(1.5),
        padding: theme.spacing(1, 1.5),
        borderBottom: `1px solid ${theme.palette.divider}`,
        transition: 'background-color 0.1s ease',
        '&:hover': {backgroundColor: theme.palette.action.hover},
        ...(isMissing && {borderLeft: `3px solid ${theme.palette.warning.main}`}),
    }),
);

const MessageCell = styled(Typography)({
    fontFamily: primitives.fontFamilyMono,
    fontSize: '12px',
    flex: 1,
    wordBreak: 'break-all',
    minWidth: 0,
});

const TranslationCell = styled(Typography)({
    fontFamily: primitives.fontFamilyMono,
    fontSize: '12px',
    flex: 1,
    wordBreak: 'break-all',
    minWidth: 0,
});

// ---------------------------------------------------------------------------
// TranslatorPanel
// ---------------------------------------------------------------------------

export const TranslatorPanel = ({data}: TranslatorPanelProps) => {
    const theme = useTheme();
    const [filter, setFilter] = useState('');
    const deferredFilter = useDeferredValue(filter);

    if (!data || data.totalCount === 0) {
        return (
            <EmptyState
                icon="translate"
                title="No translations"
                description="No translation lookups were recorded during this request."
            />
        );
    }

    const {translations, missingCount, totalCount, locales, categories} = data;

    const filtered = useMemo(() => {
        if (!deferredFilter.trim()) return translations;
        const lower = deferredFilter.toLowerCase();
        return translations.filter(
            (t) =>
                t.message.toLowerCase().includes(lower) ||
                t.category.toLowerCase().includes(lower) ||
                t.locale.toLowerCase().includes(lower) ||
                (t.translation && t.translation.toLowerCase().includes(lower)),
        );
    }, [translations, deferredFilter]);

    return (
        <Box>
            {/* Summary cards */}
            <SummaryGrid>
                <SummaryCard>
                    <SummaryLabel>Total Lookups</SummaryLabel>
                    <SummaryValue sx={{color: 'primary.main'}}>{totalCount}</SummaryValue>
                </SummaryCard>
                <SummaryCard>
                    <SummaryLabel>Missing</SummaryLabel>
                    <SummaryValue sx={{color: missingCount > 0 ? 'warning.main' : 'text.disabled'}}>
                        {missingCount}
                    </SummaryValue>
                </SummaryCard>
                <SummaryCard>
                    <SummaryLabel>Locales</SummaryLabel>
                    <Box sx={{display: 'flex', gap: 0.5, flexWrap: 'wrap', mt: 0.5}}>
                        {locales.map((locale) => (
                            <Chip
                                key={locale}
                                label={locale}
                                size="small"
                                variant="outlined"
                                sx={{fontSize: '11px', height: 22, borderRadius: 0.75, fontWeight: 600}}
                            />
                        ))}
                    </Box>
                </SummaryCard>
                <SummaryCard>
                    <SummaryLabel>Categories</SummaryLabel>
                    <Box sx={{display: 'flex', gap: 0.5, flexWrap: 'wrap', mt: 0.5}}>
                        {categories.map((cat) => (
                            <Chip
                                key={cat}
                                label={cat}
                                size="small"
                                variant="outlined"
                                sx={{fontSize: '11px', height: 22, borderRadius: 0.75, fontWeight: 600}}
                            />
                        ))}
                    </Box>
                </SummaryCard>
            </SummaryGrid>

            {/* Translation list */}
            <SectionTitle
                action={<FilterInput value={filter} onChange={setFilter} placeholder="Filter translations..." />}
            >{`${filtered.length} translations`}</SectionTitle>

            {filtered.map((t, index) => (
                <TranslationRow key={index} isMissing={t.missing}>
                    <Icon
                        sx={{
                            fontSize: 16,
                            color: t.missing ? theme.palette.warning.main : theme.palette.success.main,
                            flexShrink: 0,
                        }}
                    >
                        {t.missing ? 'warning' : 'check_circle'}
                    </Icon>
                    <Chip
                        label={t.locale}
                        size="small"
                        sx={{
                            fontWeight: 700,
                            fontSize: '10px',
                            height: 20,
                            minWidth: 32,
                            borderRadius: 0.5,
                            backgroundColor: theme.palette.action.hover,
                            color: theme.palette.text.secondary,
                            flexShrink: 0,
                        }}
                    />
                    <Chip
                        label={t.category}
                        size="small"
                        variant="outlined"
                        sx={{fontSize: '10px', height: 20, borderRadius: 0.5, flexShrink: 0}}
                    />
                    <MessageCell sx={{color: 'text.primary'}}>{t.message}</MessageCell>
                    <TranslationCell sx={{color: t.missing ? 'warning.main' : 'text.secondary'}}>
                        {t.missing ? '(missing)' : t.translation}
                    </TranslationCell>
                    {t.fallbackLocale && (
                        <Chip
                            label={`fallback: ${t.fallbackLocale}`}
                            size="small"
                            sx={{
                                fontSize: '9px',
                                height: 18,
                                borderRadius: 0.5,
                                backgroundColor: theme.palette.info.light,
                                color: theme.palette.info.main,
                                flexShrink: 0,
                            }}
                        />
                    )}
                </TranslationRow>
            ))}
        </Box>
    );
};
