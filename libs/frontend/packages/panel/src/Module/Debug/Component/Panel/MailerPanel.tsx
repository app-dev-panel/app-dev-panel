import {EmptyState} from '@app-dev-panel/sdk/Component/EmptyState';
import {SectionTitle} from '@app-dev-panel/sdk/Component/SectionTitle';
import {nl2br} from '@app-dev-panel/sdk/Helper/nl2br';
import {
    Box,
    Button,
    Chip,
    Collapse,
    Dialog,
    DialogActions,
    DialogContent,
    DialogContentText,
    DialogTitle,
    Icon,
    IconButton,
    Typography,
} from '@mui/material';
import {styled} from '@mui/material/styles';
import {useCallback, useState} from 'react';

type PreviewType = 'html' | 'raw';
type MailMessageType = {
    from: Record<string, string>;
    to: Record<string, string>;
    subject: string;
    date: string;
    textBody: string;
    htmlBody: string;
    raw: string;
    charset: string;
    replyTo: Record<string, string>;
    cc: Record<string, string>;
    bcc: Record<string, string>;
};
type MailerPanelProps = {data: {messages: MailMessageType[]}};

function serializeSender(sender: Record<string, string>): string {
    return Object.entries(sender)
        .map(([key, value]) => `${key} <${value}>`)
        .join(', ');
}

const MailRow = styled(Box, {shouldForwardProp: (p) => p !== 'expanded'})<{expanded?: boolean}>(
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

const IndexBadge = styled(Box)(({theme}) => ({
    width: 24,
    height: 24,
    borderRadius: '50%',
    backgroundColor: theme.palette.primary.main,
    color: theme.palette.common.white,
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

const FieldRow = styled(Box)(({theme}) => ({
    display: 'flex',
    gap: theme.spacing(1),
    marginBottom: theme.spacing(0.5),
    fontSize: '12px',
}));

const FieldLabel = styled(Typography)(({theme}) => ({
    fontSize: '12px',
    fontWeight: 600,
    color: theme.palette.text.disabled,
    width: 60,
    flexShrink: 0,
}));

const FieldValue = styled(Typography)({fontSize: '12px', wordBreak: 'break-word'});

type PreviewDialogProps = {onClose: () => void; open: boolean; previewType: PreviewType; message: MailMessageType};
const PreviewDialog = ({message, open, onClose, previewType}: PreviewDialogProps) => {
    if (!message) return null;

    return (
        <Dialog open={open} onClose={onClose} fullScreen>
            <DialogTitle sx={{fontWeight: 600, fontSize: '16px'}}>{message.subject}</DialogTitle>
            <DialogContent>
                {previewType === 'html' ? (
                    <DialogContentText dangerouslySetInnerHTML={{__html: message.htmlBody}} />
                ) : (
                    <DialogContentText
                        sx={(theme) => ({
                            fontFamily: theme.adp.fontFamilyMono,
                            fontSize: '12px',
                            whiteSpace: 'pre-wrap',
                        })}
                    >
                        {nl2br(message.raw)}
                    </DialogContentText>
                )}
            </DialogContent>
            <DialogActions>
                <Button onClick={onClose}>Close</Button>
            </DialogActions>
        </Dialog>
    );
};

export const MailerPanel = ({data}: MailerPanelProps) => {
    const [expandedIndex, setExpandedIndex] = useState<number | null>(null);
    const [dialogOpen, setDialogOpen] = useState(false);
    const [previewType, setPreviewType] = useState<PreviewType>('html');
    const [selectedMessage, setSelectedMessage] = useState<MailMessageType | null>(null);

    const handleDialogClose = useCallback(() => {
        setDialogOpen(false);
        setSelectedMessage(null);
    }, []);

    const openPreview = (message: MailMessageType, type: PreviewType) => {
        setSelectedMessage(message);
        setPreviewType(type);
        setDialogOpen(true);
    };

    if (!data || data.messages.length === 0) {
        return <EmptyState icon="mail" title="No dumped mails found" />;
    }

    return (
        <Box>
            <Box sx={{display: 'flex', alignItems: 'center', gap: 2, mb: 2}}>
                <SectionTitle>{`${data.messages.length} message${data.messages.length !== 1 ? 's' : ''}`}</SectionTitle>
            </Box>

            {data.messages.map((entry, index) => {
                const expanded = expandedIndex === index;

                return (
                    <Box key={index}>
                        <MailRow expanded={expanded} onClick={() => setExpandedIndex(expanded ? null : index)}>
                            <IndexBadge>{index + 1}</IndexBadge>
                            <Box sx={{flex: 1, minWidth: 0}}>
                                <Typography sx={{fontSize: '13px', fontWeight: 500}}>{entry.subject}</Typography>
                                <Typography sx={{fontSize: '11px', color: 'text.disabled'}}>
                                    To: {serializeSender(entry.to)}
                                </Typography>
                            </Box>
                            <Typography sx={{fontSize: '11px', color: 'text.disabled', flexShrink: 0}}>
                                {entry.date}
                            </Typography>
                            <IconButton size="small" sx={{flexShrink: 0}}>
                                <Icon sx={{fontSize: 16}}>{expanded ? 'expand_less' : 'expand_more'}</Icon>
                            </IconButton>
                        </MailRow>
                        <Collapse in={expanded}>
                            <DetailBox>
                                <FieldRow>
                                    <FieldLabel>From</FieldLabel>
                                    <FieldValue>{serializeSender(entry.from)}</FieldValue>
                                </FieldRow>
                                <FieldRow>
                                    <FieldLabel>To</FieldLabel>
                                    <FieldValue>{serializeSender(entry.to)}</FieldValue>
                                </FieldRow>
                                {Object.keys(entry.cc).length > 0 && (
                                    <FieldRow>
                                        <FieldLabel>CC</FieldLabel>
                                        <FieldValue>{serializeSender(entry.cc)}</FieldValue>
                                    </FieldRow>
                                )}
                                {Object.keys(entry.bcc).length > 0 && (
                                    <FieldRow>
                                        <FieldLabel>BCC</FieldLabel>
                                        <FieldValue>{serializeSender(entry.bcc)}</FieldValue>
                                    </FieldRow>
                                )}
                                {Object.keys(entry.replyTo).length > 0 && (
                                    <FieldRow>
                                        <FieldLabel>Reply-To</FieldLabel>
                                        <FieldValue>{serializeSender(entry.replyTo)}</FieldValue>
                                    </FieldRow>
                                )}
                                <FieldRow>
                                    <FieldLabel>Charset</FieldLabel>
                                    <FieldValue>{entry.charset}</FieldValue>
                                </FieldRow>

                                <Box sx={{display: 'flex', gap: 1, mt: 1.5}}>
                                    {entry.htmlBody && (
                                        <Chip
                                            clickable
                                            onClick={(e) => {
                                                e.stopPropagation();
                                                openPreview(entry, 'html');
                                            }}
                                            label="Preview HTML"
                                            size="small"
                                            icon={<Icon sx={{fontSize: '14px !important'}}>html</Icon>}
                                            sx={{fontSize: '11px', height: 24}}
                                            variant="outlined"
                                        />
                                    )}
                                    <Chip
                                        clickable
                                        onClick={(e) => {
                                            e.stopPropagation();
                                            openPreview(entry, 'raw');
                                        }}
                                        label="View Raw"
                                        size="small"
                                        icon={<Icon sx={{fontSize: '14px !important'}}>raw_on</Icon>}
                                        sx={{fontSize: '11px', height: 24}}
                                        variant="outlined"
                                    />
                                </Box>
                            </DetailBox>
                        </Collapse>
                    </Box>
                );
            })}

            <PreviewDialog
                open={dialogOpen}
                onClose={handleDialogClose}
                message={selectedMessage!}
                previewType={previewType}
            />
        </Box>
    );
};
