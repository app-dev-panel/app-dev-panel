import {JsonRenderer} from '@app-dev-panel/panel/Module/Debug/Component/JsonRenderer';
import {EmptyState} from '@app-dev-panel/sdk/Component/EmptyState';
import {FilterInput} from '@app-dev-panel/sdk/Component/FilterInput';
import {SectionTitle} from '@app-dev-panel/sdk/Component/SectionTitle';
import {primitives} from '@app-dev-panel/sdk/Component/Theme/tokens';
import {Box, Chip, Collapse, Icon, IconButton, Typography} from '@mui/material';
import {styled} from '@mui/material/styles';
import {useDeferredValue, useState} from 'react';

type WebViewEntry = {output: string; file: string; parameters: any[]};
type WebViewPanelProps = {data: WebViewEntry[]};

const OUTPUT_PREVIEW_LENGTH = 300;

function basename(filePath: string): string {
    const parts = filePath.replace(/\\/g, '/').split('/');
    return parts[parts.length - 1] ?? filePath;
}

function dirname(filePath: string): string {
    const parts = filePath.replace(/\\/g, '/').split('/');
    parts.pop();
    return parts.join('/');
}

const ViewRow = styled(Box, {shouldForwardProp: (p) => p !== 'expanded'})<{expanded?: boolean}>(
    ({theme, expanded}) => ({
        display: 'flex',
        alignItems: 'flex-start',
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
    padding: theme.spacing(1.5, 1.5, 1.5, 6),
    backgroundColor: theme.palette.action.hover,
    borderBottom: `1px solid ${theme.palette.divider}`,
    fontSize: '12px',
}));

const OutputPreview = styled(Typography)({
    fontFamily: primitives.fontFamilyMono,
    fontSize: '11px',
    whiteSpace: 'pre-wrap',
    wordBreak: 'break-word',
    lineHeight: 1.5,
});

export const WebViewPanel = ({data}: WebViewPanelProps) => {
    const [filter, setFilter] = useState('');
    const deferredFilter = useDeferredValue(filter);
    const [expandedIndex, setExpandedIndex] = useState<number | null>(null);
    const [showFullOutput, setShowFullOutput] = useState<Set<number>>(new Set());

    if (!data || data.length === 0) {
        return <EmptyState icon="web" title="No WebView renders found" />;
    }

    const filtered = deferredFilter
        ? data.filter((entry) => entry.file.toLowerCase().includes(deferredFilter.toLowerCase()))
        : data;

    const toggleFullOutput = (index: number) => {
        setShowFullOutput((prev) => {
            const next = new Set(prev);
            if (next.has(index)) {
                next.delete(index);
            } else {
                next.add(index);
            }
            return next;
        });
    };

    return (
        <Box>
            <SectionTitle
                action={<FilterInput value={filter} onChange={setFilter} placeholder="Filter files..." />}
            >{`${filtered.length} renders`}</SectionTitle>

            {filtered.map((entry, index) => {
                const expanded = expandedIndex === index;
                const isTruncated = entry.output.length > OUTPUT_PREVIEW_LENGTH;
                const showFull = showFullOutput.has(index);
                return (
                    <Box key={index}>
                        <ViewRow expanded={expanded} onClick={() => setExpandedIndex(expanded ? null : index)}>
                            <Box sx={{flex: 1, minWidth: 0}}>
                                <Typography
                                    sx={{fontFamily: primitives.fontFamilyMono, fontSize: '13px', fontWeight: 500}}
                                >
                                    {basename(entry.file)}
                                </Typography>
                                <Typography
                                    sx={{
                                        fontFamily: primitives.fontFamilyMono,
                                        fontSize: '10px',
                                        color: 'text.disabled',
                                    }}
                                >
                                    {dirname(entry.file)}
                                </Typography>
                            </Box>
                            {entry.parameters.length > 0 && (
                                <Chip
                                    label={`${entry.parameters.length} param${entry.parameters.length !== 1 ? 's' : ''}`}
                                    size="small"
                                    variant="outlined"
                                    sx={{fontSize: '10px', height: 20, borderRadius: 1, flexShrink: 0}}
                                />
                            )}
                            <IconButton size="small" sx={{flexShrink: 0}}>
                                <Icon sx={{fontSize: 16}}>{expanded ? 'expand_less' : 'expand_more'}</Icon>
                            </IconButton>
                        </ViewRow>
                        <Collapse in={expanded}>
                            <DetailBox>
                                {entry.output && (
                                    <Box sx={{mb: 1.5}}>
                                        <Typography
                                            sx={{fontSize: '11px', fontWeight: 600, color: 'text.disabled', mb: 0.5}}
                                        >
                                            Output
                                        </Typography>
                                        <OutputPreview sx={{color: 'text.secondary'}}>
                                            {showFull || !isTruncated
                                                ? entry.output
                                                : entry.output.substring(0, OUTPUT_PREVIEW_LENGTH) + '...'}
                                        </OutputPreview>
                                        {isTruncated && (
                                            <Typography
                                                onClick={(e) => {
                                                    e.stopPropagation();
                                                    toggleFullOutput(index);
                                                }}
                                                sx={{
                                                    fontSize: '11px',
                                                    color: 'primary.main',
                                                    cursor: 'pointer',
                                                    mt: 0.5,
                                                    '&:hover': {textDecoration: 'underline'},
                                                }}
                                            >
                                                {showFull ? 'Show less' : 'Show more'}
                                            </Typography>
                                        )}
                                    </Box>
                                )}
                                {entry.parameters.length > 0 && (
                                    <Box>
                                        <Typography
                                            sx={{fontSize: '11px', fontWeight: 600, color: 'text.disabled', mb: 0.5}}
                                        >
                                            Parameters
                                        </Typography>
                                        <JsonRenderer value={entry.parameters} />
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
