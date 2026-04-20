import {CodeHighlight} from '@app-dev-panel/sdk/Component/CodeHighlight';
import {EmptyState} from '@app-dev-panel/sdk/Component/EmptyState';
import {SectionTitle} from '@app-dev-panel/sdk/Component/SectionTitle';
import {formatBytes} from '@app-dev-panel/sdk/Helper/formatBytes';
import {
    attachmentDataUrl,
    countImages,
    extractLinks,
    type MailAttachment,
    rewriteCidReferences,
} from '@app-dev-panel/sdk/Helper/mailPreview';
import {
    Box,
    Button,
    Chip,
    Icon,
    IconButton,
    Link,
    Tab,
    Tabs,
    ToggleButton,
    ToggleButtonGroup,
    Typography,
} from '@mui/material';
import {styled} from '@mui/material/styles';
import {type SyntheticEvent, useCallback, useMemo, useState} from 'react';

type AddressMap = Record<string, string>;

type MailMessage = {
    from: AddressMap;
    to: AddressMap;
    cc: AddressMap;
    bcc: AddressMap;
    replyTo: AddressMap;
    subject: string;
    textBody: string | null;
    htmlBody: string | null;
    raw: string;
    charset: string;
    date: string | null;
    messageId: string | null;
    headers: Record<string, string>;
    size: number;
    attachments: MailAttachment[];
};

type MailerPanelProps = {data: {messages: MailMessage[]}};

type PreviewTab = 'html' | 'text' | 'source' | 'raw';
type Viewport = 'desktop' | 'tablet' | 'mobile';

const VIEWPORT_WIDTHS: Record<Viewport, number | '100%'> = {desktop: '100%', tablet: 768, mobile: 375};

const serializeAddresses = (addresses: AddressMap): string =>
    Object.entries(addresses)
        .map(([email, name]) => (name && name !== email ? `${name} <${email}>` : email))
        .join(', ');

const formatSummaryRecipients = (addresses: AddressMap): string => Object.keys(addresses).join(', ') || '—';

const attachmentIconName = (contentType: string): string => {
    if (contentType.startsWith('image/')) return 'image';
    if (contentType === 'application/pdf') return 'picture_as_pdf';
    if (contentType.startsWith('text/')) return 'description';
    if (contentType.includes('zip') || contentType.includes('compressed')) return 'folder_zip';
    return 'attach_file';
};

const ListRow = styled(Box)(({theme}) => ({
    display: 'flex',
    alignItems: 'stretch',
    gap: theme.spacing(2),
    padding: theme.spacing(1.5, 2),
    borderBottom: `1px solid ${theme.palette.divider}`,
    cursor: 'pointer',
    transition: 'background-color 0.1s ease',
    '&:hover': {backgroundColor: theme.palette.action.hover},
}));

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

const ThumbFrame = styled(Box)(({theme}) => ({
    width: 220,
    height: 72,
    flexShrink: 0,
    border: `1px solid ${theme.palette.divider}`,
    borderRadius: theme.shape.borderRadius,
    overflow: 'hidden',
    position: 'relative',
    backgroundColor: theme.palette.common.white,
    '& iframe': {
        width: 640,
        height: 220,
        border: 0,
        transform: 'scale(0.34)',
        transformOrigin: 'top left',
        pointerEvents: 'none',
    },
}));

const TextThumb = styled(Box)(({theme}) => ({
    width: 220,
    height: 72,
    flexShrink: 0,
    border: `1px dashed ${theme.palette.divider}`,
    borderRadius: theme.shape.borderRadius,
    padding: theme.spacing(0.75, 1),
    overflow: 'hidden',
    fontFamily: theme.adp.fontFamilyMono,
    fontSize: '10px',
    lineHeight: 1.3,
    color: theme.palette.text.disabled,
    whiteSpace: 'pre-wrap',
    wordBreak: 'break-word',
}));

const MetaLabel = styled(Typography)(({theme}) => ({
    fontSize: theme.typography.overline.fontSize,
    fontWeight: theme.typography.overline.fontWeight,
    letterSpacing: '0.05em',
    textTransform: 'uppercase',
    color: theme.palette.text.disabled,
}));

const FieldRow = styled(Box)(({theme}) => ({
    display: 'flex',
    gap: theme.spacing(1),
    padding: theme.spacing(0.75, 0),
    fontSize: '12px',
    borderBottom: `1px solid ${theme.palette.divider}`,
    '&:last-of-type': {borderBottom: 'none'},
}));

const FieldKey = styled(Typography)(({theme}) => ({
    fontSize: '11px',
    fontWeight: 600,
    color: theme.palette.text.disabled,
    width: 80,
    flexShrink: 0,
    textTransform: 'uppercase',
    letterSpacing: '0.04em',
}));

const FieldValue = styled(Typography)({fontSize: '12px', wordBreak: 'break-word', flex: 1});

const MessageListThumbnail = ({message}: {message: MailMessage}) => {
    const srcDoc = useMemo(() => {
        if (!message.htmlBody) return '';
        return rewriteCidReferences(message.htmlBody, message.attachments);
    }, [message.htmlBody, message.attachments]);

    if (message.htmlBody) {
        return (
            <ThumbFrame>
                <iframe title="preview" sandbox="" srcDoc={srcDoc} />
            </ThumbFrame>
        );
    }
    const snippet = (message.textBody ?? '').slice(0, 240);
    return <TextThumb>{snippet || '(no body)'}</TextThumb>;
};

const ListView = ({messages, onSelect}: {messages: MailMessage[]; onSelect: (index: number) => void}) => (
    <Box>
        <Box sx={{display: 'flex', alignItems: 'center', gap: 2, mb: 2}}>
            <SectionTitle>{`${messages.length} message${messages.length !== 1 ? 's' : ''}`}</SectionTitle>
        </Box>
        {messages.map((message, index) => {
            const attachments = message.attachments.filter((a) => !a.inline);
            return (
                <ListRow
                    key={index}
                    role="button"
                    aria-label={`Open message ${index + 1}`}
                    onClick={() => onSelect(index)}
                >
                    <IndexBadge>{index + 1}</IndexBadge>
                    <Box sx={{flex: 1, minWidth: 0, display: 'flex', flexDirection: 'column', gap: 0.25}}>
                        <Typography sx={{fontSize: '13px', fontWeight: 500}}>
                            {message.subject || '(no subject)'}
                        </Typography>
                        <Typography sx={{fontSize: '11px', color: 'text.disabled'}}>
                            To: {formatSummaryRecipients(message.to)}
                        </Typography>
                        <Box sx={{display: 'flex', gap: 0.75, mt: 0.5, flexWrap: 'wrap'}}>
                            {attachments.length > 0 && (
                                <Chip
                                    size="small"
                                    variant="outlined"
                                    icon={<Icon sx={{fontSize: '14px !important'}}>attach_file</Icon>}
                                    label={`${attachments.length} file${attachments.length !== 1 ? 's' : ''}`}
                                    sx={{fontSize: '11px', height: 22}}
                                />
                            )}
                            {message.htmlBody && (
                                <Chip
                                    size="small"
                                    variant="outlined"
                                    label="HTML"
                                    sx={{fontSize: '11px', height: 22}}
                                />
                            )}
                            {message.size > 0 && (
                                <Chip
                                    size="small"
                                    variant="outlined"
                                    label={formatBytes(message.size)}
                                    sx={{fontSize: '11px', height: 22, color: 'text.disabled'}}
                                />
                            )}
                        </Box>
                    </Box>
                    <MessageListThumbnail message={message} />
                    <Box sx={{display: 'flex', flexDirection: 'column', alignItems: 'flex-end', flexShrink: 0}}>
                        <Typography sx={{fontSize: '11px', color: 'text.disabled'}}>{message.date ?? ''}</Typography>
                        <Icon sx={{fontSize: 16, color: 'text.disabled', mt: 'auto'}}>chevron_right</Icon>
                    </Box>
                </ListRow>
            );
        })}
    </Box>
);

type DetailProps = {message: MailMessage; index: number; total: number; onBack: () => void};

const LeftColumn = ({message}: {message: MailMessage}) => {
    const attachments = message.attachments.filter((a) => !a.inline);
    const inlineAttachments = message.attachments.filter((a) => a.inline);
    const links = useMemo(() => extractLinks(message.htmlBody ?? ''), [message.htmlBody]);
    const headerEntries = Object.entries(message.headers);

    return (
        <Box sx={{width: {xs: '100%', md: 340}, flexShrink: 0, display: 'flex', flexDirection: 'column'}}>
            <SectionTitle>Recipients</SectionTitle>
            <Box sx={{display: 'flex', flexDirection: 'column'}}>
                <FieldRow>
                    <FieldKey>From</FieldKey>
                    <FieldValue>{serializeAddresses(message.from) || '—'}</FieldValue>
                </FieldRow>
                <FieldRow>
                    <FieldKey>To</FieldKey>
                    <FieldValue>{serializeAddresses(message.to) || '—'}</FieldValue>
                </FieldRow>
                {Object.keys(message.cc).length > 0 && (
                    <FieldRow>
                        <FieldKey>CC</FieldKey>
                        <FieldValue>{serializeAddresses(message.cc)}</FieldValue>
                    </FieldRow>
                )}
                {Object.keys(message.bcc).length > 0 && (
                    <FieldRow>
                        <FieldKey>BCC</FieldKey>
                        <FieldValue>{serializeAddresses(message.bcc)}</FieldValue>
                    </FieldRow>
                )}
                {Object.keys(message.replyTo).length > 0 && (
                    <FieldRow>
                        <FieldKey>Reply-To</FieldKey>
                        <FieldValue>{serializeAddresses(message.replyTo)}</FieldValue>
                    </FieldRow>
                )}
            </Box>

            <SectionTitle>Message info</SectionTitle>
            <Box sx={{display: 'flex', flexDirection: 'column'}}>
                {message.messageId && (
                    <FieldRow>
                        <FieldKey>Message-ID</FieldKey>
                        <FieldValue sx={{fontFamily: 'monospace'}}>{message.messageId}</FieldValue>
                    </FieldRow>
                )}
                {message.date && (
                    <FieldRow>
                        <FieldKey>Date</FieldKey>
                        <FieldValue>{message.date}</FieldValue>
                    </FieldRow>
                )}
                <FieldRow>
                    <FieldKey>Charset</FieldKey>
                    <FieldValue>{message.charset}</FieldValue>
                </FieldRow>
                <FieldRow>
                    <FieldKey>Size</FieldKey>
                    <FieldValue>{formatBytes(message.size)}</FieldValue>
                </FieldRow>
            </Box>

            {attachments.length > 0 && (
                <>
                    <SectionTitle>Attachments · {attachments.length}</SectionTitle>
                    <Box sx={{display: 'flex', flexDirection: 'column', gap: 0.5}}>
                        {attachments.map((attachment, idx) => (
                            <Box
                                key={`${attachment.filename}-${idx}`}
                                sx={{
                                    display: 'flex',
                                    alignItems: 'center',
                                    gap: 1,
                                    padding: 1,
                                    border: '1px solid',
                                    borderColor: 'divider',
                                    borderRadius: 1,
                                }}
                            >
                                <Icon sx={{fontSize: 20, color: 'text.disabled'}}>
                                    {attachmentIconName(attachment.contentType)}
                                </Icon>
                                <Box sx={{flex: 1, minWidth: 0}}>
                                    <Typography sx={{fontSize: '12px', fontWeight: 500, wordBreak: 'break-all'}}>
                                        {attachment.filename}
                                    </Typography>
                                    <Typography sx={{fontSize: '11px', color: 'text.disabled'}}>
                                        {formatBytes(attachment.size)} · {attachment.contentType}
                                    </Typography>
                                </Box>
                                <IconButton
                                    size="small"
                                    component="a"
                                    href={attachmentDataUrl(attachment)}
                                    download={attachment.filename}
                                    aria-label={`Download ${attachment.filename}`}
                                >
                                    <Icon sx={{fontSize: 18}}>download</Icon>
                                </IconButton>
                            </Box>
                        ))}
                    </Box>
                </>
            )}

            {inlineAttachments.length > 0 && (
                <>
                    <SectionTitle>Inline images · {inlineAttachments.length}</SectionTitle>
                    <Box sx={{display: 'flex', flexWrap: 'wrap', gap: 1}}>
                        {inlineAttachments.map((attachment, idx) => (
                            <Chip
                                key={`${attachment.filename}-${idx}`}
                                size="small"
                                variant="outlined"
                                icon={<Icon sx={{fontSize: '14px !important'}}>image</Icon>}
                                label={`${attachment.filename} · ${formatBytes(attachment.size)}`}
                                sx={{fontSize: '11px', height: 22}}
                            />
                        ))}
                    </Box>
                </>
            )}

            {links.length > 0 && (
                <>
                    <SectionTitle>Links · {links.length}</SectionTitle>
                    <Box sx={{display: 'flex', flexDirection: 'column', gap: 0.5}}>
                        {links.map((href) => (
                            <Link
                                key={href}
                                href={href}
                                target="_blank"
                                rel="noopener noreferrer"
                                sx={{fontSize: '12px', wordBreak: 'break-all'}}
                            >
                                {href}
                            </Link>
                        ))}
                    </Box>
                </>
            )}

            {headerEntries.length > 0 && (
                <>
                    <SectionTitle>Headers · {headerEntries.length}</SectionTitle>
                    <Box sx={{display: 'flex', flexDirection: 'column'}}>
                        {headerEntries.map(([name, value]) => (
                            <FieldRow key={name}>
                                <FieldKey>{name}</FieldKey>
                                <FieldValue sx={{fontFamily: 'monospace', fontSize: '11px'}}>{value}</FieldValue>
                            </FieldRow>
                        ))}
                    </Box>
                </>
            )}
        </Box>
    );
};

const PreviewPane = ({message}: {message: MailMessage}) => {
    const [tab, setTab] = useState<PreviewTab>(message.htmlBody ? 'html' : message.textBody ? 'text' : 'raw');
    const [viewport, setViewport] = useState<Viewport>('desktop');

    const handleTab = useCallback((_: SyntheticEvent, next: PreviewTab) => setTab(next), []);
    const handleViewport = useCallback((_: unknown, next: Viewport | null) => {
        if (next) setViewport(next);
    }, []);

    const htmlSrcDoc = useMemo(
        () => rewriteCidReferences(message.htmlBody ?? '', message.attachments),
        [message.htmlBody, message.attachments],
    );

    const viewportWidth = VIEWPORT_WIDTHS[viewport];

    return (
        <Box sx={{flex: 1, minWidth: 0, display: 'flex', flexDirection: 'column'}}>
            <Box
                sx={{
                    display: 'flex',
                    alignItems: 'center',
                    justifyContent: 'space-between',
                    borderBottom: '1px solid',
                    borderColor: 'divider',
                    gap: 2,
                    flexWrap: 'wrap',
                }}
            >
                <Tabs
                    value={tab}
                    onChange={handleTab}
                    sx={{minHeight: 36, '& .MuiTab-root': {minHeight: 36, fontSize: '12px', textTransform: 'none'}}}
                >
                    <Tab value="html" label="HTML Preview" disabled={!message.htmlBody} />
                    <Tab value="text" label="Text" disabled={!message.textBody} />
                    <Tab value="source" label="HTML Source" disabled={!message.htmlBody} />
                    <Tab value="raw" label="Raw" />
                </Tabs>
                {tab === 'html' && (
                    <ToggleButtonGroup
                        value={viewport}
                        exclusive
                        size="small"
                        onChange={handleViewport}
                        sx={{'& .MuiToggleButton-root': {padding: '4px 10px', fontSize: '11px'}}}
                    >
                        <ToggleButton value="desktop" aria-label="Desktop">
                            <Icon sx={{fontSize: 16, mr: 0.5}}>desktop_windows</Icon>
                            Desktop
                        </ToggleButton>
                        <ToggleButton value="tablet" aria-label="Tablet">
                            <Icon sx={{fontSize: 16, mr: 0.5}}>tablet_mac</Icon>
                            Tablet
                        </ToggleButton>
                        <ToggleButton value="mobile" aria-label="Mobile">
                            <Icon sx={{fontSize: 16, mr: 0.5}}>smartphone</Icon>
                            Mobile
                        </ToggleButton>
                    </ToggleButtonGroup>
                )}
            </Box>

            <Box sx={{p: 2, flex: 1, minHeight: 320, overflow: 'auto', backgroundColor: 'background.default'}}>
                {tab === 'html' && message.htmlBody && (
                    <Box sx={{display: 'flex', justifyContent: 'center'}}>
                        <Box
                            sx={{
                                width: viewportWidth,
                                minHeight: 480,
                                backgroundColor: 'common.white',
                                border: '1px solid',
                                borderColor: 'divider',
                                borderRadius: 1,
                                overflow: 'hidden',
                            }}
                        >
                            <iframe
                                title="html-preview"
                                sandbox=""
                                srcDoc={htmlSrcDoc}
                                style={{width: '100%', height: 600, border: 0, display: 'block'}}
                            />
                        </Box>
                    </Box>
                )}
                {tab === 'text' && (
                    <Box
                        component="pre"
                        sx={(theme) => ({
                            margin: 0,
                            fontFamily: theme.adp.fontFamilyMono,
                            fontSize: '12px',
                            whiteSpace: 'pre-wrap',
                            wordBreak: 'break-word',
                        })}
                    >
                        {message.textBody ?? ''}
                    </Box>
                )}
                {tab === 'source' && message.htmlBody && <CodeHighlight language="html" code={message.htmlBody} />}
                {tab === 'raw' && (
                    <Box
                        component="pre"
                        sx={(theme) => ({
                            margin: 0,
                            fontFamily: theme.adp.fontFamilyMono,
                            fontSize: '12px',
                            whiteSpace: 'pre-wrap',
                            wordBreak: 'break-word',
                        })}
                    >
                        {message.raw}
                    </Box>
                )}
            </Box>
        </Box>
    );
};

const DetailView = ({message, index, total, onBack}: DetailProps) => {
    const attachments = message.attachments.filter((a) => !a.inline);
    const images = useMemo(() => countImages(message.htmlBody ?? ''), [message.htmlBody]);
    const links = useMemo(() => extractLinks(message.htmlBody ?? ''), [message.htmlBody]);
    const contentType =
        message.htmlBody && message.textBody ? 'multipart/alternative' : message.htmlBody ? 'text/html' : 'text/plain';

    return (
        <Box>
            <Box sx={{display: 'flex', alignItems: 'center', gap: 1.5, mb: 2, flexWrap: 'wrap'}}>
                <Button
                    onClick={onBack}
                    size="small"
                    startIcon={<Icon sx={{fontSize: '16px !important'}}>arrow_back</Icon>}
                    sx={{textTransform: 'none'}}
                >
                    Back
                </Button>
                <Typography sx={{fontSize: '12px', color: 'text.disabled'}}>
                    Message {index + 1} of {total}
                </Typography>
                <Box sx={{flex: 1}} />
                {message.htmlBody && (
                    <Chip size="small" variant="outlined" label="multipart" sx={{fontSize: '11px', height: 22}} />
                )}
                {attachments.length > 0 && (
                    <Chip
                        size="small"
                        variant="outlined"
                        icon={<Icon sx={{fontSize: '14px !important'}}>attach_file</Icon>}
                        label={`${attachments.length} file${attachments.length !== 1 ? 's' : ''}`}
                        sx={{fontSize: '11px', height: 22}}
                    />
                )}
            </Box>

            <Typography sx={{fontSize: '18px', fontWeight: 600, mb: 0.5}}>
                {message.subject || '(no subject)'}
            </Typography>
            <Typography sx={{fontSize: '12px', color: 'text.disabled', mb: 1.5}}>
                {serializeAddresses(message.from) || '—'}
                {' → '}
                {serializeAddresses(message.to) || '—'}
                {message.date ? ` · ${message.date}` : ''}
            </Typography>

            <Box
                sx={{
                    display: 'flex',
                    gap: 2,
                    mb: 2,
                    flexWrap: 'wrap',
                    pb: 1.5,
                    borderBottom: '1px solid',
                    borderColor: 'divider',
                }}
            >
                <MetaLabel>Size: {formatBytes(message.size)}</MetaLabel>
                <MetaLabel>Links: {links.length}</MetaLabel>
                <MetaLabel>Images: {images}</MetaLabel>
                <MetaLabel>Type: {contentType}</MetaLabel>
                <MetaLabel>Charset: {message.charset}</MetaLabel>
            </Box>

            <Box sx={{display: 'flex', gap: 3, alignItems: 'flex-start', flexDirection: {xs: 'column', md: 'row'}}}>
                <LeftColumn message={message} />
                <PreviewPane message={message} />
            </Box>
        </Box>
    );
};

export const MailerPanel = ({data}: MailerPanelProps) => {
    const messages = data?.messages ?? [];
    const [selectedIndex, setSelectedIndex] = useState<number | null>(null);

    if (messages.length === 0) {
        return <EmptyState icon="mail" title="No dumped mails found" />;
    }

    if (selectedIndex !== null && messages[selectedIndex]) {
        return (
            <DetailView
                message={messages[selectedIndex]}
                index={selectedIndex}
                total={messages.length}
                onBack={() => setSelectedIndex(null)}
            />
        );
    }

    return <ListView messages={messages} onSelect={setSelectedIndex} />;
};
