/**
 * Copy `text` to the clipboard without ever throwing.
 *
 * `navigator.clipboard.writeText` rejects (or is `undefined`) on insecure
 * origins (`http://` hosts other than localhost — typical for ADP running
 * inside a dev VM), in iframes without the `clipboard-write` permission, and
 * when the document is not focused. All of those used to surface as
 * unhandled promise rejections. This helper tries the async API first and
 * falls back to the legacy `document.execCommand('copy')` path; the boolean
 * tells the caller whether anything was copied so it can adjust its UI.
 */
export const copyText = async (text: string): Promise<boolean> => {
    try {
        if (typeof navigator !== 'undefined' && navigator.clipboard?.writeText) {
            await navigator.clipboard.writeText(text);
            return true;
        }
    } catch {
        // Fall through to the legacy path.
    }
    return copyTextLegacy(text);
};

const copyTextLegacy = (text: string): boolean => {
    if (typeof document === 'undefined' || typeof document.execCommand !== 'function') {
        return false;
    }
    const textarea = document.createElement('textarea');
    textarea.value = text;
    textarea.setAttribute('readonly', '');
    textarea.style.position = 'fixed';
    textarea.style.top = '0';
    textarea.style.left = '0';
    textarea.style.opacity = '0';
    document.body.appendChild(textarea);
    try {
        textarea.select();
        textarea.setSelectionRange(0, text.length);
        return document.execCommand('copy');
    } catch {
        return false;
    } finally {
        textarea.remove();
    }
};
