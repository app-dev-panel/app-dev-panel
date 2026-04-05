import {JsonRenderer} from '@app-dev-panel/panel/Module/Debug/Component/JsonRenderer';
import {FileLink} from '@app-dev-panel/sdk/Component/FileLink';
import {MessageCopyButton} from '@app-dev-panel/sdk/Component/MessageCopyButton';
import {SqlHighlight} from '@app-dev-panel/sdk/Component/SqlHighlight';
import {isClassString} from '@app-dev-panel/sdk/Helper/classMatcher';
import {CollectorsMap} from '@app-dev-panel/sdk/Helper/collectors';
import {formatMicrotime} from '@app-dev-panel/sdk/Helper/formatDate';
import {toObjectString} from '@app-dev-panel/sdk/Helper/objectString';
import {Box, Tooltip, Typography} from '@mui/material';
import {styled} from '@mui/material/styles';

type Item = [number, number, string] | [number, number, string, string];

type TimelineDetailCardProps = {
    row: Item;
    fullDetail: string | null;
    logLevel: string | null;
    accentColor: string;
    offsetLabel: string;
};

const CardRoot = styled(Box, {shouldForwardProp: (p) => p !== 'accentColor'})<{accentColor?: string}>(
    ({theme, accentColor}) => ({
        borderLeft: `3px solid ${accentColor ?? 'transparent'}`,
        backgroundColor: theme.palette.action.hover,
        borderBottom: `1px solid ${theme.palette.divider}`,
        fontSize: '12px',
    }),
);

const Header = styled(Box)(({theme}) => ({
    display: 'flex',
    alignItems: 'center',
    gap: theme.spacing(1.5),
    flexWrap: 'wrap',
    padding: theme.spacing(1, 2),
    borderBottom: `1px solid ${theme.palette.divider}`,
}));

const CollectorBadge = styled(Typography)(({theme}) => ({
    fontFamily: theme.adp.fontFamilyMono,
    fontSize: '11px',
    fontWeight: 600,
    padding: '2px 8px',
    borderRadius: 4,
    lineHeight: '16px',
    whiteSpace: 'nowrap',
}));

const MetaLabel = styled(Typography)({
    fontSize: '11px',
});

const ContentArea = styled(Box)(({theme}) => ({
    position: 'relative',
    padding: theme.spacing(1.5, 2),
    '&:hover .copy-btn': {opacity: 1},
}));

const CopyBtn = styled(MessageCopyButton)({});

export const TimelineDetailCard = ({row, fullDetail, logLevel, accentColor, offsetLabel}: TimelineDetailCardProps) => {
    const collectorClass = row[2];
    const shortName = collectorClass.split('\\').pop() ?? collectorClass;
    const isDatabaseLike =
        collectorClass === CollectorsMap.DatabaseCollector ||
        collectorClass === CollectorsMap.ElasticsearchCollector;

    return (
        <CardRoot accentColor={accentColor}>
            {/* Header bar */}
            <Header>
                <Tooltip title={collectorClass} placement="top">
                    <CollectorBadge sx={{color: accentColor, backgroundColor: `${accentColor}18`}}>
                        {shortName}
                    </CollectorBadge>
                </Tooltip>
                <MetaLabel sx={{color: 'text.disabled'}}>{formatMicrotime(row[0])}</MetaLabel>
                <MetaLabel sx={{color: 'text.disabled'}}>Offset: {offsetLabel}</MetaLabel>
                {row[1] != null && (
                    <MetaLabel sx={{color: 'text.disabled'}}>Ref: {String(row[1])}</MetaLabel>
                )}
                <Box sx={{ml: 'auto'}}>
                    <FileLink className={collectorClass}>
                        <Tooltip title="Open in IDE" placement="top">
                            <Typography
                                component="span"
                                sx={(t) => ({
                                    fontFamily: t.adp.fontFamilyMono,
                                    fontSize: '10px',
                                    color: 'primary.main',
                                    cursor: 'pointer',
                                    '&:hover': {textDecoration: 'underline'},
                                })}
                            >
                                Open
                            </Typography>
                        </Tooltip>
                    </FileLink>
                </Box>
            </Header>

            {/* Content body */}
            {fullDetail && (
                <ContentArea className="message-bubble">
                    {isDatabaseLike ? (
                        <SqlHighlight sql={fullDetail} fontSize={12} />
                    ) : (
                        <Box sx={{display: 'flex', gap: 1, alignItems: 'flex-start', backgroundColor: 'background.paper', borderRadius: 1, padding: '8px 12px'}}>
                            {logLevel && (
                                <Typography
                                    sx={{fontSize: '10px', fontWeight: 700, color: 'text.disabled', userSelect: 'none', flexShrink: 0, lineHeight: 1.7, mt: '1px'}}
                                >
                                    {logLevel.toUpperCase()}
                                </Typography>
                            )}
                            <Typography
                                sx={(t) => ({
                                    fontFamily: t.adp.fontFamilyMono,
                                    fontSize: '12px',
                                    color: 'text.primary',
                                    whiteSpace: 'pre-wrap',
                                    wordBreak: 'break-word',
                                    userSelect: 'text',
                                    lineHeight: 1.7,
                                    minWidth: 0,
                                })}
                            >
                                {fullDetail}
                            </Typography>
                        </Box>
                    )}
                    {!isDatabaseLike && <CopyBtn text={fullDetail} className="copy-btn" />}
                </ContentArea>
            )}

            {/* Supplementary JSON data */}
            {Array.isArray(row[3]) && row[3].length > 0 && (
                <Box sx={{px: 2, pb: 1.5}}>
                    <JsonRenderer value={isClassString(row[3]) ? toObjectString(row[3], row[1]) : row[3]} />
                </Box>
            )}
        </CardRoot>
    );
};
