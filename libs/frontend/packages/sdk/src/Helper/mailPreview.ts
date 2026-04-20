/**
 * Helpers for rendering collected email messages in the Mailer debug panel.
 *
 * - `rewriteCidReferences` replaces `cid:<id>` occurrences in an HTML body with
 *   data URLs, using the inline attachments' contentId → base64 payload map.
 * - `extractLinks` returns the unique `<a href>` set from an HTML body.
 * - `attachmentDataUrl` builds a data: URL for downloading an attachment.
 */

export type MailAttachment = {
    filename: string;
    contentType: string;
    size: number;
    contentId: string | null;
    inline: boolean;
    contentBase64: string;
};

const stripAngles = (id: string): string => id.replace(/^<|>$/g, '');

export const attachmentDataUrl = (attachment: Pick<MailAttachment, 'contentType' | 'contentBase64'>): string =>
    `data:${attachment.contentType || 'application/octet-stream'};base64,${attachment.contentBase64}`;

/**
 * Replace every `cid:<id>` reference in the HTML with a `data:` URL.
 * Matches against both the raw `contentId` and its angle-stripped form.
 */
export const rewriteCidReferences = (html: string, attachments: readonly MailAttachment[]): string => {
    if (!html) return html;
    const cidMap = new Map<string, string>();
    for (const attachment of attachments) {
        if (!attachment.contentId || !attachment.contentBase64) continue;
        const url = attachmentDataUrl(attachment);
        cidMap.set(attachment.contentId, url);
        cidMap.set(stripAngles(attachment.contentId), url);
    }
    if (cidMap.size === 0) return html;

    return html.replace(/cid:([^"'\s)>]+)/gi, (match, rawId: string) => {
        const id = stripAngles(rawId);
        return cidMap.get(id) ?? cidMap.get(rawId) ?? match;
    });
};

/**
 * Extract the unique list of href values from `<a>` tags in the HTML body.
 * Safe for SSR-free browsers (uses DOMParser). Returns [] on parse failure.
 */
export const extractLinks = (html: string): string[] => {
    if (!html || typeof DOMParser === 'undefined') return [];
    try {
        const doc = new DOMParser().parseFromString(html, 'text/html');
        const seen = new Set<string>();
        const result: string[] = [];
        doc.querySelectorAll('a[href]').forEach((node) => {
            const href = node.getAttribute('href');
            if (!href) return;
            if (href.startsWith('javascript:') || href.startsWith('data:')) return;
            if (seen.has(href)) return;
            seen.add(href);
            result.push(href);
        });
        return result;
    } catch {
        return [];
    }
};

/**
 * Count `<img>` tags in the HTML body (same-document, not fetched).
 */
export const countImages = (html: string): number => {
    if (!html || typeof DOMParser === 'undefined') return 0;
    try {
        const doc = new DOMParser().parseFromString(html, 'text/html');
        return doc.querySelectorAll('img').length;
    } catch {
        return 0;
    }
};
