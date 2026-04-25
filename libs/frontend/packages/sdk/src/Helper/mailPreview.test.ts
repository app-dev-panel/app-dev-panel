import {describe, expect, it} from 'vitest';
import {attachmentDataUrl, countImages, extractLinks, type MailAttachment, rewriteCidReferences} from './mailPreview';

const makeAttachment = (overrides: Partial<MailAttachment> = {}): MailAttachment => ({
    filename: 'logo.png',
    contentType: 'image/png',
    size: 100,
    contentId: 'logo-id',
    inline: true,
    contentBase64: 'aGVsbG8=',
    ...overrides,
});

describe('rewriteCidReferences', () => {
    it('replaces cid: references in <img src> with data URLs', () => {
        const html = '<img src="cid:logo-id">';
        const out = rewriteCidReferences(html, [makeAttachment()]);
        expect(out).toContain('data:image/png;base64,aGVsbG8=');
        expect(out).not.toContain('cid:logo-id');
    });

    it('handles angle-bracketed content ids', () => {
        const html = '<img src="cid:logo-id">';
        const out = rewriteCidReferences(html, [makeAttachment({contentId: '<logo-id>'})]);
        expect(out).toContain('data:image/png;base64,');
    });

    it('returns HTML unchanged when no matching content id', () => {
        const html = '<img src="cid:nope">';
        const out = rewriteCidReferences(html, [makeAttachment()]);
        expect(out).toBe(html);
    });

    it('skips attachments without contentId or payload', () => {
        const html = '<img src="cid:logo-id">';
        const out = rewriteCidReferences(html, [makeAttachment({contentId: null})]);
        expect(out).toBe(html);
    });

    it('returns the input for empty html', () => {
        expect(rewriteCidReferences('', [makeAttachment()])).toBe('');
    });
});

describe('extractLinks', () => {
    it('returns the unique href list from <a> tags', () => {
        const html = '<a href="https://a.test">A</a><a href="https://b.test">B</a><a href="https://a.test">A again</a>';
        expect(extractLinks(html)).toEqual(['https://a.test', 'https://b.test']);
    });

    it('ignores javascript: and data: hrefs', () => {
        const html =
            '<a href="javascript:alert(1)">bad</a><a href="data:text/plain,hi">also bad</a><a href="https://ok.test">ok</a>';
        expect(extractLinks(html)).toEqual(['https://ok.test']);
    });

    it('returns [] for empty html', () => {
        expect(extractLinks('')).toEqual([]);
    });
});

describe('countImages', () => {
    it('counts <img> tags', () => {
        expect(countImages('<img src="a"><img src="b"><p>no</p>')).toBe(2);
    });

    it('returns 0 for empty html', () => {
        expect(countImages('')).toBe(0);
    });
});

describe('attachmentDataUrl', () => {
    it('builds a data URL with the content type and payload', () => {
        const url = attachmentDataUrl({contentType: 'text/plain', contentBase64: 'aGk='});
        expect(url).toBe('data:text/plain;base64,aGk=');
    });

    it('defaults to application/octet-stream when contentType is empty', () => {
        const url = attachmentDataUrl({contentType: '', contentBase64: 'aGk='});
        expect(url).toBe('data:application/octet-stream;base64,aGk=');
    });
});
