import {CodeHighlight} from '@app-dev-panel/sdk/Component/CodeHighlight';
import {EmptyState} from '@app-dev-panel/sdk/Component/EmptyState';
import {SectionTitle} from '@app-dev-panel/sdk/Component/SectionTitle';
import {formatBytes} from '@app-dev-panel/sdk/Helper/formatBytes';
import {
    attachmentDataUrl,
    extractLinks,
    type MailAttachment,
    rewriteCidReferences,
} from '@app-dev-panel/sdk/Helper/mailPreview';
import {
    Box,
    Button,
    Chip,
    Dialog,
    DialogContent,
    Icon,
    IconButton,
    Link,
    Tab,
    Tabs,
    ToggleButton,
    ToggleButtonGroup,
    Tooltip,
    Typography,
} from '@mui/material';
import {styled} from '@mui/material/styles';
import {type SyntheticEvent, useCallback, useMemo, useState} from 'react';
import {useSearchParams} from 'react-router';

type AddressMap = Record<string, string>;

type MailMessage = {
    from?: AddressMap;
    to?: AddressMap;
    cc?: AddressMap;
    bcc?: AddressMap;
    replyTo?: AddressMap;
    subject?: string;
    textBody?: string | null;
    htmlBody?: string | null;
    raw?: string;
    charset?: string;
    date?: string | null;
    messageId?: string | null;
    headers?: Record<string, string>;
    size?: number;
    attachments?: MailAttachment[];
};

type MailerPanelProps = {data: {messages: MailMessage[]}};

type PreviewTab = 'html' | 'text' | 'source' | 'raw';
type Viewport = 'desktop' | 'tablet' | 'mobile';

const VIEWPORT_WIDTHS: Record<Viewport, number | '100%'> = {desktop: '100%', tablet: 768, mobile: 375};

const serializeAddresses = (addresses: AddressMap | undefined): string =>
    Object.entries(addresses ?? {})
        .map(([email, name]) => (name && name !== email ? `${name} <${email}>` : email))
        .join(', ');

const hasAddresses = (addresses: AddressMap | undefined): boolean => !!addresses && Object.keys(addresses).length > 0;

const formatSummaryRecipients = (addresses: AddressMap | undefined): string =>
    Object.keys(addresses ?? {}).join(', ') || '—';

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

const MessageListThumbnail = ({message}: {message: MailMessage}) => {
    const srcDoc = useMemo(() => {
        if (!message.htmlBody) return '';
        return rewriteCidReferences(message.htmlBody, message.attachments ?? []);
    }, [message.htmlBody, message.attachments]);

    if (message.htmlBody) {
        return (
            <ThumbFrame>
                <iframe title="preview" sandbox="" srcDoc={srcDoc} />
            </ThumbFrame>
        );
    }
    if (message.textBody) {
        return <TextThumb>{message.textBody.slice(0, 240)}</TextThumb>;
    }
    return null;
};

const ListView = ({messages, onSelect}: {messages: MailMessage[]; onSelect: (index: number) => void}) => (
    <Box>
        <Box sx={{display: 'flex', alignItems: 'center', gap: 2, mb: 2}}>
            <SectionTitle>{`${messages.length} message${messages.length !== 1 ? 's' : ''}`}</SectionTitle>
        </Box>
        {messages.map((message, index) => {
            const attachments = (message.attachments ?? []).filter((a) => !a.inline);
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
                        {hasAddresses(message.to) && (
                            <Typography sx={{fontSize: '11px', color: 'text.disabled'}}>
                                To: {formatSummaryRecipients(message.to)}
                            </Typography>
                        )}
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
                            {typeof message.size === 'number' && message.size > 0 && (
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
                        {message.date && (
                            <Typography sx={{fontSize: '11px', color: 'text.disabled'}}>{message.date}</Typography>
                        )}
                        <Icon sx={{fontSize: 16, color: 'text.disabled', mt: 'auto'}}>chevron_right</Icon>
                    </Box>
                </ListRow>
            );
        })}
    </Box>
);

type DetailProps = {message: MailMessage; index: number; total: number; onBack: () => void};

// ---------------------------------------------------------------------------
// Meta sections — displayed ABOVE the full-width preview.
// Each section is shown only when it has content.
// ---------------------------------------------------------------------------

const AddressLine = ({label, addresses}: {label: string; addresses: AddressMap | undefined}) => {
    if (!hasAddresses(addresses)) return null;
    return (
        <Box sx={{display: 'flex', gap: 1, fontSize: '12px', lineHeight: 1.5}}>
            <Box
                sx={{
                    width: 64,
                    flexShrink: 0,
                    color: 'text.disabled',
                    fontWeight: 600,
                    textTransform: 'uppercase',
                    letterSpacing: '0.04em',
                    fontSize: '11px',
                }}
            >
                {label}
            </Box>
            <Box sx={{flex: 1, wordBreak: 'break-word'}}>{serializeAddresses(addresses)}</Box>
        </Box>
    );
};

const AttachmentChip = ({attachment}: {attachment: MailAttachment}) => (
    <Box
        component="a"
        href={attachmentDataUrl(attachment)}
        download={attachment.filename}
        aria-label={`Download ${attachment.filename}`}
        sx={(theme) => ({
            display: 'inline-flex',
            alignItems: 'center',
            gap: 1,
            padding: theme.spacing(0.5, 1),
            border: `1px solid ${theme.palette.divider}`,
            borderRadius: 1,
            textDecoration: 'none',
            color: theme.palette.text.primary,
            fontSize: '12px',
            maxWidth: 280,
            transition: 'background-color 0.1s ease',
            '&:hover': {backgroundColor: theme.palette.action.hover},
        })}
    >
        <Icon sx={{fontSize: 16, color: 'text.disabled', flexShrink: 0}}>
            {attachmentIconName(attachment.contentType)}
        </Icon>
        <Box sx={{minWidth: 0, display: 'flex', flexDirection: 'column'}}>
            <Box sx={{fontWeight: 500, whiteSpace: 'nowrap', overflow: 'hidden', textOverflow: 'ellipsis'}}>
                {attachment.filename}
            </Box>
            <Box sx={{fontSize: '10px', color: 'text.disabled'}}>
                {formatBytes(attachment.size)} · {attachment.contentType}
            </Box>
        </Box>
        <Icon sx={{fontSize: 16, color: 'text.disabled', ml: 'auto', flexShrink: 0}}>download</Icon>
    </Box>
);

const MetaHeader = ({message}: {message: MailMessage}) => {
    const allAttachments = message.attachments ?? [];
    const attachments = allAttachments.filter((a) => !a.inline);
    const inlineAttachments = allAttachments.filter((a) => a.inline);
    const links = useMemo(() => extractLinks(message.htmlBody ?? ''), [message.htmlBody]);
    const headerEntries = Object.entries(message.headers ?? {});
    const [headersOpen, setHeadersOpen] = useState(false);

    return (
        <Box sx={{display: 'flex', flexDirection: 'column', gap: 1.25, mb: 2}}>
            {/* Recipients block */}
            <Box sx={{display: 'flex', flexDirection: 'column', gap: 0.5}}>
                <AddressLine label="From" addresses={message.from} />
                <AddressLine label="To" addresses={message.to} />
                <AddressLine label="CC" addresses={message.cc} />
                <AddressLine label="BCC" addresses={message.bcc} />
                <AddressLine label="Reply-To" addresses={message.replyTo} />
            </Box>

            {/* Attachments strip — filename chips with download */}
            {attachments.length > 0 && (
                <Box sx={{display: 'flex', flexWrap: 'wrap', gap: 0.75}}>
                    {attachments.map((attachment, idx) => (
                        <AttachmentChip key={`${attachment.filename}-${idx}`} attachment={attachment} />
                    ))}
                </Box>
            )}

            {/* Compact meta row — inline stats, links, inline-images, headers toggle */}
            <Box sx={{display: 'flex', flexWrap: 'wrap', gap: 1.25, alignItems: 'center', rowGap: 0.5}}>
                {message.messageId && (
                    <Tooltip title={message.messageId} placement="top">
                        <Chip
                            size="small"
                            variant="outlined"
                            label={`ID: ${message.messageId.replace(/^<|>$/g, '').slice(0, 32)}${message.messageId.length > 34 ? '…' : ''}`}
                            sx={{fontSize: '11px', height: 22, fontFamily: 'monospace'}}
                        />
                    </Tooltip>
                )}
                {typeof message.size === 'number' && message.size > 0 && (
                    <MetaLabel>Size: {formatBytes(message.size)}</MetaLabel>
                )}
                {message.charset && <MetaLabel>Charset: {message.charset}</MetaLabel>}
                {links.length > 0 && <MetaLabel>Links: {links.length}</MetaLabel>}
                {inlineAttachments.length > 0 && <MetaLabel>Inline images: {inlineAttachments.length}</MetaLabel>}
                {headerEntries.length > 0 && (
                    <Chip
                        size="small"
                        clickable
                        variant={headersOpen ? 'filled' : 'outlined'}
                        label={`Headers · ${headerEntries.length}`}
                        onClick={() => setHeadersOpen((v) => !v)}
                        sx={{fontSize: '11px', height: 22}}
                    />
                )}
            </Box>

            {/* Links list — only when there are links */}
            {links.length > 0 && (
                <Box sx={{display: 'flex', flexWrap: 'wrap', gap: 0.75, rowGap: 0.25}}>
                    {links.map((href) => (
                        <Link
                            key={href}
                            href={href}
                            target="_blank"
                            rel="noopener noreferrer"
                            sx={{fontSize: '11px', wordBreak: 'break-all'}}
                        >
                            {href}
                        </Link>
                    ))}
                </Box>
            )}

            {/* Headers — collapsible */}
            {headersOpen && headerEntries.length > 0 && (
                <Box
                    sx={(theme) => ({
                        border: `1px solid ${theme.palette.divider}`,
                        borderRadius: 1,
                        padding: theme.spacing(1, 1.5),
                        display: 'flex',
                        flexDirection: 'column',
                        gap: 0.25,
                        maxHeight: 220,
                        overflow: 'auto',
                    })}
                >
                    {headerEntries.map(([name, value]) => (
                        <Box key={name} sx={{display: 'flex', gap: 1, fontSize: '11px', fontFamily: 'monospace'}}>
                            <Box sx={{minWidth: 140, color: 'text.disabled'}}>{name}</Box>
                            <Box sx={{flex: 1, wordBreak: 'break-word'}}>{value}</Box>
                        </Box>
                    ))}
                </Box>
            )}
        </Box>
    );
};

// ---------------------------------------------------------------------------
// Preview pane — tabs + viewport toggle. Used both inline and inside the
// fullscreen Dialog.
// ---------------------------------------------------------------------------

type PreviewPaneProps = {message: MailMessage; onFullscreen?: () => void; onExitFullscreen?: () => void};

const PreviewPane = ({message, onFullscreen, onExitFullscreen}: PreviewPaneProps) => {
    const [tab, setTab] = useState<PreviewTab>(message.htmlBody ? 'html' : message.textBody ? 'text' : 'raw');
    const [viewport, setViewport] = useState<Viewport>('desktop');

    const handleTab = useCallback((_: SyntheticEvent, next: PreviewTab) => setTab(next), []);
    const handleViewport = useCallback((_: unknown, next: Viewport | null) => {
        if (next) setViewport(next);
    }, []);

    const htmlSrcDoc = useMemo(
        () => rewriteCidReferences(message.htmlBody ?? '', message.attachments ?? []),
        [message.htmlBody, message.attachments],
    );

    const viewportWidth = VIEWPORT_WIDTHS[viewport];
    const fullscreen = Boolean(onExitFullscreen);

    return (
        <Box
            sx={(theme) => ({
                flex: 1,
                minWidth: 0,
                display: 'flex',
                flexDirection: 'column',
                border: fullscreen ? 'none' : `1px solid ${theme.palette.divider}`,
                borderRadius: fullscreen ? 0 : 1,
                overflow: 'hidden',
                backgroundColor: theme.palette.background.paper,
            })}
        >
            <Box
                sx={{
                    display: 'flex',
                    alignItems: 'center',
                    justifyContent: 'space-between',
                    borderBottom: '1px solid',
                    borderColor: 'divider',
                    gap: 2,
                    px: 1,
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
                    <Tab value="raw" label="Raw" disabled={!message.raw} />
                </Tabs>
                <Box sx={{display: 'flex', alignItems: 'center', gap: 1, mr: 0.5}}>
                    {tab === 'html' && message.htmlBody && (
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
                    {onFullscreen && (
                        <Tooltip title="Open fullscreen" placement="top">
                            <IconButton size="small" onClick={onFullscreen} aria-label="Open preview fullscreen">
                                <Icon sx={{fontSize: 18}}>fullscreen</Icon>
                            </IconButton>
                        </Tooltip>
                    )}
                    {onExitFullscreen && (
                        <Tooltip title="Exit fullscreen" placement="top">
                            <IconButton size="small" onClick={onExitFullscreen} aria-label="Close fullscreen preview">
                                <Icon sx={{fontSize: 18}}>close</Icon>
                            </IconButton>
                        </Tooltip>
                    )}
                </Box>
            </Box>

            <Box
                sx={{
                    p: 2,
                    flex: 1,
                    minHeight: fullscreen ? 0 : 480,
                    overflow: 'auto',
                    backgroundColor: 'background.default',
                }}
            >
                {tab === 'html' && message.htmlBody && (
                    <Box sx={{display: 'flex', justifyContent: 'center'}}>
                        <Box
                            sx={{
                                width: viewportWidth,
                                minHeight: fullscreen ? '80vh' : 560,
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
                                style={{
                                    width: '100%',
                                    height: fullscreen ? 'calc(100vh - 120px)' : 680,
                                    border: 0,
                                    display: 'block',
                                }}
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
                        {message.raw ?? ''}
                    </Box>
                )}
            </Box>
        </Box>
    );
};

const DetailView = ({message, index, total, onBack}: DetailProps) => {
    const attachments = (message.attachments ?? []).filter((a) => !a.inline);
    const [fullscreen, setFullscreen] = useState(false);

    return (
        <Box>
            <Box sx={{display: 'flex', alignItems: 'center', gap: 1.5, mb: 1.5, flexWrap: 'wrap'}}>
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
                {message.htmlBody && message.textBody && (
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
            {message.date && (
                <Typography sx={{fontSize: '12px', color: 'text.disabled', mb: 1.5}}>{message.date}</Typography>
            )}

            <MetaHeader message={message} />

            <PreviewPane message={message} onFullscreen={() => setFullscreen(true)} />

            <Dialog
                fullScreen
                open={fullscreen}
                onClose={() => setFullscreen(false)}
                aria-label="Fullscreen email preview"
            >
                <DialogContent sx={{padding: 0, display: 'flex', flexDirection: 'column', height: '100%'}}>
                    <PreviewPane message={message} onExitFullscreen={() => setFullscreen(false)} />
                </DialogContent>
            </Dialog>
        </Box>
    );
};

const MESSAGE_QUERY_PARAM = 'message';

export const MailerPanel = ({data}: MailerPanelProps) => {
    const messages = data?.messages ?? [];
    const [searchParams, setSearchParams] = useSearchParams();

    const selectedIndex = useMemo(() => {
        const raw = searchParams.get(MESSAGE_QUERY_PARAM);
        if (raw === null) return null;
        const idx = Number.parseInt(raw, 10);
        return Number.isInteger(idx) && idx >= 0 && idx < messages.length ? idx : null;
    }, [searchParams, messages.length]);

    const selectMessage = useCallback(
        (index: number | null) => {
            setSearchParams(
                (prev) => {
                    const next = new URLSearchParams(prev);
                    if (index === null) next.delete(MESSAGE_QUERY_PARAM);
                    else next.set(MESSAGE_QUERY_PARAM, String(index));
                    return next;
                },
                {replace: false},
            );
        },
        [setSearchParams],
    );

    if (messages.length === 0) {
        return <EmptyState icon="mail" title="No dumped mails found" />;
    }

    if (selectedIndex !== null && messages[selectedIndex]) {
        return (
            <DetailView
                message={messages[selectedIndex]}
                index={selectedIndex}
                total={messages.length}
                onBack={() => selectMessage(null)}
            />
        );
    }

    return <ListView messages={messages} onSelect={selectMessage} />;
};
