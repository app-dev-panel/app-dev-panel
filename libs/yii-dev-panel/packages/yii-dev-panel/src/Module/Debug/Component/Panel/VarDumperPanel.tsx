import {Alert, AlertTitle, Box, Collapse, Icon, IconButton, Typography} from '@mui/material';
import {styled} from '@mui/material/styles';
import {SectionTitle} from '@yiisoft/yii-dev-panel-sdk/Component/SectionTitle';
import {primitives} from '@yiisoft/yii-dev-panel-sdk/Component/Theme/tokens';
import {parseFilePathWithLineAnchor} from '@yiisoft/yii-dev-panel-sdk/Helper/filePathParser';
import {JsonRenderer} from '@yiisoft/yii-dev-panel/Module/Debug/Component/JsonRenderer';
import {useState} from 'react';

type VarDumperEntry = {variable: any; line: string};
type VarDumperPanelProps = {data: VarDumperEntry[]};

const DumpRow = styled(Box, {shouldForwardProp: (p) => p !== 'expanded'})<{expanded?: boolean}>(
    ({theme, expanded}) => ({
        display: 'flex',
        alignItems: 'flex-start',
        gap: theme.spacing(1.5),
        padding: theme.spacing(1.5, 2),
        borderBottom: `1px solid ${theme.palette.divider}`,
        cursor: 'pointer',
        transition: 'background-color 0.1s ease',
        backgroundColor: expanded ? theme.palette.action.hover : 'transparent',
        '&:hover': {backgroundColor: theme.palette.action.hover},
    }),
);

const IndexBadge = styled(Box)(() => ({
    width: 24,
    height: 24,
    borderRadius: '50%',
    backgroundColor: primitives.amber600,
    color: '#fff',
    display: 'flex',
    alignItems: 'center',
    justifyContent: 'center',
    fontSize: '11px',
    fontWeight: 700,
    flexShrink: 0,
    marginTop: 2,
}));

const DetailBox = styled(Box)(({theme}) => ({
    padding: theme.spacing(2),
    backgroundColor: theme.palette.action.hover,
    borderBottom: `1px solid ${theme.palette.divider}`,
}));

function getPreview(variable: any): string {
    if (variable === null) return 'null';
    if (variable === undefined) return 'undefined';
    const type = typeof variable;
    if (type === 'string') return variable.length > 80 ? variable.slice(0, 80) + '...' : `"${variable}"`;
    if (type === 'number' || type === 'boolean') return String(variable);
    if (Array.isArray(variable)) return `Array(${variable.length})`;
    if (type === 'object')
        return `Object {${Object.keys(variable).slice(0, 3).join(', ')}${Object.keys(variable).length > 3 ? ', ...' : ''}}`;
    return String(variable);
}

export const VarDumperPanel = ({data}: VarDumperPanelProps) => {
    const [expandedIndex, setExpandedIndex] = useState<number | null>(null);

    if (!data || data.length === 0) {
        return (
            <Box m={2}>
                <Alert severity="info">
                    <AlertTitle>No dumped variables found during the process</AlertTitle>
                </Alert>
            </Box>
        );
    }

    return (
        <Box>
            <Box sx={{display: 'flex', alignItems: 'center', gap: 2, mb: 2}}>
                <SectionTitle>{`${data.length} dump${data.length !== 1 ? 's' : ''}`}</SectionTitle>
            </Box>

            {data.map((entry, index) => {
                const expanded = expandedIndex === index;

                return (
                    <Box key={index}>
                        <DumpRow expanded={expanded} onClick={() => setExpandedIndex(expanded ? null : index)}>
                            <IndexBadge>{index + 1}</IndexBadge>
                            <Box sx={{flex: 1, minWidth: 0}}>
                                <Typography
                                    sx={{
                                        fontFamily: primitives.fontFamilyMono,
                                        fontSize: '12px',
                                        color: 'text.primary',
                                        overflow: 'hidden',
                                        textOverflow: 'ellipsis',
                                        whiteSpace: 'nowrap',
                                    }}
                                >
                                    {getPreview(entry.variable)}
                                </Typography>
                            </Box>
                            <Typography
                                component="a"
                                href={`/inspector/files?path=${parseFilePathWithLineAnchor(entry.line)}`}
                                sx={{
                                    fontFamily: primitives.fontFamilyMono,
                                    fontSize: '11px',
                                    color: 'primary.main',
                                    textDecoration: 'none',
                                    flexShrink: 0,
                                    whiteSpace: 'nowrap',
                                    '&:hover': {textDecoration: 'underline'},
                                }}
                                onClick={(e: React.MouseEvent) => e.stopPropagation()}
                            >
                                {entry.line}
                            </Typography>
                            <IconButton size="small" sx={{flexShrink: 0}}>
                                <Icon sx={{fontSize: 16}}>{expanded ? 'expand_less' : 'expand_more'}</Icon>
                            </IconButton>
                        </DumpRow>
                        <Collapse in={expanded}>
                            <DetailBox>
                                <JsonRenderer value={entry.variable} depth={10} />
                            </DetailBox>
                        </Collapse>
                    </Box>
                );
            })}
        </Box>
    );
};
