import {JsonRenderer} from '@app-dev-panel/panel/Module/Debug/Component/JsonRenderer';
import {EmptyState} from '@app-dev-panel/sdk/Component/EmptyState';
import {FilterInput} from '@app-dev-panel/sdk/Component/FilterInput';
import {SectionTitle} from '@app-dev-panel/sdk/Component/SectionTitle';
import {primitives} from '@app-dev-panel/sdk/Component/Theme/tokens';
import {formatMicrotime} from '@app-dev-panel/sdk/Helper/formatDate';
import {searchVariants} from '@app-dev-panel/sdk/Helper/layoutTranslit';
import {Box, Chip, Collapse, Icon, IconButton, Typography} from '@mui/material';
import {styled, useTheme} from '@mui/material/styles';
import {useDeferredValue, useMemo, useState} from 'react';

type HttpDumpEntry = {
    time: number;
    method: string;
    uri: string;
    headers: Record<string, string | string[]>;
    body: string;
    query: Record<string, string>;
    cookies: Record<string, string>;
    files: unknown[];
};

type HttpDumpPanelProps = {data: HttpDumpEntry[]};

const methodColor = (method: string): string => {
    switch (method.toUpperCase()) {
        case 'GET':
            return '#16A34A';
        case 'POST':
            return '#2563EB';
        case 'PUT':
        case 'PATCH':
            return '#D97706';
        case 'DELETE':
            return '#DC2626';
        default:
            return '#6B7280';
    }
};

const DumpRow = styled(Box, {shouldForwardProp: (p) => p !== 'expanded'})<{expanded?: boolean}>(
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

const DetailBox = styled(Box)(({theme}) => ({
    padding: theme.spacing(2),
    backgroundColor: theme.palette.action.hover,
    borderBottom: `1px solid ${theme.palette.divider}`,
    fontSize: '12px',
}));

const DetailSection = styled(Box)(({theme}) => ({marginBottom: theme.spacing(2), '&:last-child': {marginBottom: 0}}));

export const HttpDumpPanel = ({data}: HttpDumpPanelProps) => {
    const theme = useTheme();
    const [filter, setFilter] = useState('');
    const deferredFilter = useDeferredValue(filter);
    const [expandedIndex, setExpandedIndex] = useState<number | null>(null);

    if (!data || data.length === 0) {
        return <EmptyState icon="cloud_upload" title="No HTTP dumps captured" />;
    }

    const filtered = useMemo(() => {
        if (!deferredFilter) return data;
        const variants = searchVariants(deferredFilter.toLowerCase());
        return data.filter((e) => {
            const searchText = `${e.method} ${e.uri}`.toLowerCase();
            return variants.some((v) => searchText.includes(v));
        });
    }, [data, deferredFilter]);

    return (
        <Box>
            <SectionTitle action={<FilterInput value={filter} onChange={setFilter} placeholder="Filter requests..." />}>
                {`${filtered.length} HTTP dump${filtered.length !== 1 ? 's' : ''}`}
            </SectionTitle>

            {filtered.map((entry, index) => {
                const expanded = expandedIndex === index;
                const headerCount = Object.keys(entry.headers ?? {}).length;
                const hasBody = !!entry.body && entry.body !== '';
                const hasQuery = Object.keys(entry.query ?? {}).length > 0;

                return (
                    <Box key={index}>
                        <DumpRow expanded={expanded} onClick={() => setExpandedIndex(expanded ? null : index)}>
                            <Chip
                                label={entry.method}
                                size="small"
                                sx={{
                                    fontWeight: 700,
                                    fontSize: '11px',
                                    height: 22,
                                    minWidth: 52,
                                    backgroundColor: methodColor(entry.method),
                                    color: 'common.white',
                                    borderRadius: 1,
                                }}
                            />
                            <Typography
                                sx={{
                                    flex: 1,
                                    fontFamily: primitives.fontFamilyMono,
                                    fontSize: '13px',
                                    wordBreak: 'break-all',
                                }}
                            >
                                {entry.uri}
                            </Typography>
                            <Typography variant="caption" sx={{color: 'text.disabled', flexShrink: 0}}>
                                {formatMicrotime(entry.time)}
                            </Typography>
                            <IconButton size="small" sx={{flexShrink: 0}}>
                                <Icon sx={{fontSize: 16}}>{expanded ? 'expand_less' : 'expand_more'}</Icon>
                            </IconButton>
                        </DumpRow>
                        <Collapse in={expanded}>
                            <DetailBox>
                                {hasQuery && (
                                    <DetailSection>
                                        <Typography variant="subtitle2" sx={{mb: 0.5, color: 'text.secondary'}}>
                                            Query Parameters
                                        </Typography>
                                        <JsonRenderer value={entry.query} depth={2} />
                                    </DetailSection>
                                )}
                                {headerCount > 0 && (
                                    <DetailSection>
                                        <Typography variant="subtitle2" sx={{mb: 0.5, color: 'text.secondary'}}>
                                            Headers ({headerCount})
                                        </Typography>
                                        <JsonRenderer value={entry.headers} depth={2} />
                                    </DetailSection>
                                )}
                                {hasBody && (
                                    <DetailSection>
                                        <Typography variant="subtitle2" sx={{mb: 0.5, color: 'text.secondary'}}>
                                            Body
                                        </Typography>
                                        <Box
                                            component="pre"
                                            sx={{
                                                fontFamily: primitives.fontFamilyMono,
                                                fontSize: '12px',
                                                whiteSpace: 'pre-wrap',
                                                wordBreak: 'break-word',
                                                p: 1.5,
                                                borderRadius: 1,
                                                backgroundColor:
                                                    theme.palette.mode === 'dark'
                                                        ? theme.palette.background.default
                                                        : theme.palette.grey[50],
                                            }}
                                        >
                                            {entry.body}
                                        </Box>
                                    </DetailSection>
                                )}
                                {Object.keys(entry.cookies ?? {}).length > 0 && (
                                    <DetailSection>
                                        <Typography variant="subtitle2" sx={{mb: 0.5, color: 'text.secondary'}}>
                                            Cookies
                                        </Typography>
                                        <JsonRenderer value={entry.cookies} depth={2} />
                                    </DetailSection>
                                )}
                            </DetailBox>
                        </Collapse>
                    </Box>
                );
            })}
        </Box>
    );
};
