import {JsonRenderer} from '@app-dev-panel/panel/Module/Debug/Component/JsonRenderer';
import {EmptyState} from '@app-dev-panel/sdk/Component/EmptyState';
import {FilterInput} from '@app-dev-panel/sdk/Component/FilterInput';
import {SectionTitle} from '@app-dev-panel/sdk/Component/SectionTitle';
import {Box, Chip, Collapse, Icon, IconButton, Typography} from '@mui/material';
import {styled} from '@mui/material/styles';
import {useDeferredValue, useState} from 'react';

type Bundle = {
    class: string;
    sourcePath: string | null;
    basePath: string | null;
    baseUrl: string | null;
    css: string[];
    js: string[];
    depends: string[];
    options: Record<string, any>;
};
type AssetBundlePanelProps = {data: {bundles: Record<string, Bundle>; bundleCount: number}};

function shortClassName(fqcn: string): string {
    const parts = fqcn.split('\\');
    return parts[parts.length - 1] ?? fqcn;
}

const SummaryGrid = styled(Box)(({theme}) => ({
    display: 'grid',
    gridTemplateColumns: 'repeat(auto-fill, minmax(160px, 1fr))',
    gap: theme.spacing(2),
    marginBottom: theme.spacing(3),
}));

const SummaryCard = styled(Box)(({theme}) => ({
    padding: theme.spacing(2),
    borderRadius: Number(theme.shape.borderRadius) * 1.5,
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

const SummaryValue = styled(Typography)(({theme}) => ({
    fontFamily: theme.adp.fontFamilyMono,
    fontWeight: 700,
    fontSize: '22px',
}));

const BundleRow = styled(Box, {shouldForwardProp: (p) => p !== 'expanded'})<{expanded?: boolean}>(
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

const ClassCell = styled(Typography)(({theme}) => ({
    fontFamily: theme.adp.fontFamilyMono,
    fontSize: '12px',
    flex: 1,
    wordBreak: 'break-word',
    minWidth: 0,
}));

const DetailBox = styled(Box)(({theme}) => ({
    padding: theme.spacing(1.5, 1.5, 1.5, 6),
    backgroundColor: theme.palette.action.hover,
    borderBottom: `1px solid ${theme.palette.divider}`,
    fontSize: '12px',
}));

const FieldLabel = styled(Typography)(({theme}) => ({
    fontSize: '11px',
    fontWeight: 600,
    color: theme.palette.text.disabled,
    marginBottom: theme.spacing(0.5),
}));

const FileList = styled(Box)(({theme}) => ({marginLeft: theme.spacing(1), marginBottom: theme.spacing(1)}));

const FileItem = styled(Typography)(({theme}) => ({
    fontFamily: theme.adp.fontFamilyMono,
    fontSize: '11px',
    lineHeight: 1.8,
}));

export const AssetBundlePanel = ({data}: AssetBundlePanelProps) => {
    const [filter, setFilter] = useState('');
    const deferredFilter = useDeferredValue(filter);
    const [expandedKey, setExpandedKey] = useState<string | null>(null);

    if (!data || !data.bundles || Object.keys(data.bundles).length === 0) {
        return <EmptyState icon="inventory_2" title="No asset bundles found" />;
    }

    const bundleEntries = Object.entries(data.bundles);
    const filtered = deferredFilter
        ? bundleEntries.filter(([key, bundle]) => {
              const lower = deferredFilter.toLowerCase();
              return key.toLowerCase().includes(lower) || bundle.class.toLowerCase().includes(lower);
          })
        : bundleEntries;

    return (
        <Box>
            <SummaryGrid>
                <SummaryCard>
                    <SummaryLabel>Total Bundles</SummaryLabel>
                    <SummaryValue sx={{color: 'primary.main'}}>{data.bundleCount}</SummaryValue>
                </SummaryCard>
            </SummaryGrid>

            <SectionTitle
                action={<FilterInput value={filter} onChange={setFilter} placeholder="Filter bundles..." />}
            >{`${filtered.length} bundles`}</SectionTitle>

            {filtered.map(([key, bundle]) => {
                const expanded = expandedKey === key;
                return (
                    <Box key={key}>
                        <BundleRow expanded={expanded} onClick={() => setExpandedKey(expanded ? null : key)}>
                            <ClassCell>{shortClassName(bundle.class)}</ClassCell>
                            {bundle.css.length > 0 && (
                                <Chip
                                    label={`${bundle.css.length} CSS`}
                                    size="small"
                                    sx={{
                                        fontWeight: 600,
                                        fontSize: '10px',
                                        height: 20,
                                        borderRadius: 1,
                                        backgroundColor: 'primary.main',
                                        color: 'common.white',
                                        flexShrink: 0,
                                    }}
                                />
                            )}
                            {bundle.js.length > 0 && (
                                <Chip
                                    label={`${bundle.js.length} JS`}
                                    size="small"
                                    sx={{
                                        fontWeight: 600,
                                        fontSize: '10px',
                                        height: 20,
                                        borderRadius: 1,
                                        backgroundColor: 'warning.main',
                                        color: 'common.white',
                                        flexShrink: 0,
                                    }}
                                />
                            )}
                            {bundle.depends.length > 0 && (
                                <Chip
                                    label={`${bundle.depends.length} dep${bundle.depends.length !== 1 ? 's' : ''}`}
                                    size="small"
                                    variant="outlined"
                                    sx={{fontSize: '10px', height: 20, borderRadius: 1, flexShrink: 0}}
                                />
                            )}
                            <IconButton size="small" sx={{flexShrink: 0}}>
                                <Icon sx={{fontSize: 16}}>{expanded ? 'expand_less' : 'expand_more'}</Icon>
                            </IconButton>
                        </BundleRow>
                        <Collapse in={expanded}>
                            <DetailBox>
                                <Box sx={{mb: 1.5}}>
                                    <FieldLabel>Full Class Name</FieldLabel>
                                    <Typography
                                        sx={(theme) => ({
                                            fontFamily: theme.adp.fontFamilyMono,
                                            fontSize: '12px',
                                            color: 'text.secondary',
                                            wordBreak: 'break-all',
                                        })}
                                    >
                                        {bundle.class}
                                    </Typography>
                                </Box>
                                {bundle.sourcePath && (
                                    <Box sx={{mb: 1.5}}>
                                        <FieldLabel>Source Path</FieldLabel>
                                        <Typography
                                            sx={(theme) => ({
                                                fontFamily: theme.adp.fontFamilyMono,
                                                fontSize: '11px',
                                                color: 'text.secondary',
                                                wordBreak: 'break-all',
                                            })}
                                        >
                                            {bundle.sourcePath}
                                        </Typography>
                                    </Box>
                                )}
                                {bundle.basePath && (
                                    <Box sx={{mb: 1.5}}>
                                        <FieldLabel>Base Path</FieldLabel>
                                        <Typography
                                            sx={(theme) => ({
                                                fontFamily: theme.adp.fontFamilyMono,
                                                fontSize: '11px',
                                                color: 'text.secondary',
                                                wordBreak: 'break-all',
                                            })}
                                        >
                                            {bundle.basePath}
                                        </Typography>
                                    </Box>
                                )}
                                {bundle.baseUrl && (
                                    <Box sx={{mb: 1.5}}>
                                        <FieldLabel>Base URL</FieldLabel>
                                        <Typography
                                            sx={(theme) => ({
                                                fontFamily: theme.adp.fontFamilyMono,
                                                fontSize: '11px',
                                                color: 'text.secondary',
                                                wordBreak: 'break-all',
                                            })}
                                        >
                                            {bundle.baseUrl}
                                        </Typography>
                                    </Box>
                                )}
                                {bundle.css.length > 0 && (
                                    <Box sx={{mb: 1.5}}>
                                        <FieldLabel>CSS Files</FieldLabel>
                                        <FileList>
                                            {bundle.css.map((file, i) => (
                                                <FileItem key={i} sx={{color: 'text.secondary'}}>
                                                    {file}
                                                </FileItem>
                                            ))}
                                        </FileList>
                                    </Box>
                                )}
                                {bundle.js.length > 0 && (
                                    <Box sx={{mb: 1.5}}>
                                        <FieldLabel>JS Files</FieldLabel>
                                        <FileList>
                                            {bundle.js.map((file, i) => (
                                                <FileItem key={i} sx={{color: 'text.secondary'}}>
                                                    {file}
                                                </FileItem>
                                            ))}
                                        </FileList>
                                    </Box>
                                )}
                                {bundle.depends.length > 0 && (
                                    <Box sx={{mb: 1.5}}>
                                        <FieldLabel>Dependencies</FieldLabel>
                                        <Box sx={{display: 'flex', gap: 0.5, flexWrap: 'wrap', ml: 1}}>
                                            {bundle.depends.map((dep, i) => (
                                                <Chip
                                                    key={i}
                                                    label={shortClassName(dep)}
                                                    size="small"
                                                    variant="outlined"
                                                    sx={(theme) => ({
                                                        fontFamily: theme.adp.fontFamilyMono,
                                                        fontSize: '10px',
                                                        height: 20,
                                                        borderRadius: 1,
                                                    })}
                                                />
                                            ))}
                                        </Box>
                                    </Box>
                                )}
                                {Object.keys(bundle.options).length > 0 && (
                                    <Box>
                                        <FieldLabel>Options</FieldLabel>
                                        <JsonRenderer value={bundle.options} />
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
