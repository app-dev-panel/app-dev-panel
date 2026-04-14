import {JsonRenderer} from '@app-dev-panel/panel/Module/Debug/Component/JsonRenderer';
import {type TimelineItem} from '@app-dev-panel/panel/Module/Debug/Component/Panel/timelineTypes';
import {FileLink} from '@app-dev-panel/sdk/Component/FileLink';
import {MessageCopyButton} from '@app-dev-panel/sdk/Component/MessageCopyButton';
import {SqlHighlight} from '@app-dev-panel/sdk/Component/SqlHighlight';
import {isClassString} from '@app-dev-panel/sdk/Helper/classMatcher';
import {CollectorsMap} from '@app-dev-panel/sdk/Helper/collectors';
import {formatMicrotime} from '@app-dev-panel/sdk/Helper/formatDate';
import {toObjectString} from '@app-dev-panel/sdk/Helper/objectString';
import {Box, Typography} from '@mui/material';
import {styled} from '@mui/material/styles';

type TimelineDetailCardProps = {
    row: TimelineItem;
    fullDetail: string | null;
    logLevel: string | null;
    accentColor: string;
    offsetLabel: string;
    rawValue?: unknown;
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

const MetaLabel = styled(Typography)({fontSize: '11px'});

const ContentArea = styled(Box)(({theme}) => ({
    position: 'relative',
    padding: theme.spacing(1.5, 2),
    '&:hover .copy-btn': {opacity: 1},
}));

export const TimelineDetailCard = ({
    row,
    fullDetail,
    logLevel,
    accentColor,
    offsetLabel,
    rawValue,
}: TimelineDetailCardProps) => {
    const collectorClass = row[2];
    const isDatabaseLike =
        collectorClass === CollectorsMap.DatabaseCollector || collectorClass === CollectorsMap.ElasticsearchCollector;
    const isEvent = collectorClass === CollectorsMap.EventCollector;
    const showDetail = fullDetail && !isEvent;

    return (
        <CardRoot accentColor={accentColor}>
            <Header>
                <MetaLabel sx={{color: 'text.disabled'}}>{formatMicrotime(row[0])}</MetaLabel>
                <MetaLabel sx={{color: 'text.disabled'}}>Offset: {offsetLabel}</MetaLabel>
                {row[1] != null && <MetaLabel sx={{color: 'text.disabled'}}>Ref: {String(row[1])}</MetaLabel>}
                <FileLink className={collectorClass}>
                    <Typography
                        component="span"
                        sx={(t) => ({
                            fontFamily: t.adp.fontFamilyMono,
                            fontSize: '11px',
                            color: 'primary.main',
                            cursor: 'pointer',
                            '&:hover': {textDecoration: 'underline'},
                        })}
                    >
                        {collectorClass.split('\\').pop() ?? collectorClass}
                    </Typography>
                </FileLink>
            </Header>

            {showDetail && (
                <ContentArea className="message-bubble">
                    {isDatabaseLike ? (
                        <SqlHighlight sql={fullDetail} fontSize={12} />
                    ) : (
                        <Box
                            sx={{
                                display: 'flex',
                                gap: 1,
                                alignItems: 'flex-start',
                                backgroundColor: 'background.paper',
                                borderRadius: 1,
                                padding: '8px 12px',
                            }}
                        >
                            {logLevel && (
                                <Typography
                                    sx={{
                                        fontSize: '10px',
                                        fontWeight: 700,
                                        color: 'text.disabled',
                                        userSelect: 'none',
                                        flexShrink: 0,
                                        lineHeight: 1.7,
                                        mt: '1px',
                                    }}
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
                    {!isDatabaseLike && <MessageCopyButton text={fullDetail} />}
                </ContentArea>
            )}

            {isEvent && rawValue != null ? (
                <Box sx={{px: 2, pb: 1.5}}>
                    <JsonRenderer value={rawValue} />
                </Box>
            ) : Array.isArray(row[3]) && row[3].length > 0 ? (
                <Box sx={{px: 2, pb: 1.5}}>
                    <JsonRenderer value={isClassString(row[3]) ? toObjectString(row[3], row[1]) : row[3]} />
                </Box>
            ) : null}
        </CardRoot>
    );
};
