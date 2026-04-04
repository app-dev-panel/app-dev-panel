import {toBlob, toPng} from 'html-to-image';
import {useCallback, useRef, useState} from 'react';

type CopyAsImageStatus = 'idle' | 'capturing' | 'success' | 'error';

type UseCopyAsImageResult = {
    status: CopyAsImageStatus;
    isCapturing: boolean;
    copyToClipboard: () => Promise<void>;
    downloadAsPng: (filename?: string) => Promise<void>;
    targetRef: React.RefObject<HTMLElement | null>;
};

function copyBlobToClipboard(blob: Blob): Promise<void> {
    if (navigator.clipboard?.write) {
        return navigator.clipboard.write([new ClipboardItem({[blob.type]: blob})]);
    }
    return Promise.reject(new Error('Clipboard API not available'));
}

function downloadDataUrl(dataUrl: string, filename: string): void {
    const link = document.createElement('a');
    link.download = filename;
    link.href = dataUrl;
    link.click();
}

export function useCopyAsImage(): UseCopyAsImageResult {
    const [status, setStatus] = useState<CopyAsImageStatus>('idle');
    const targetRef = useRef<HTMLElement | null>(null);

    const copyToClipboard = useCallback(async () => {
        const el = targetRef.current;
        if (!el) return;

        setStatus('capturing');
        try {
            const blob = await toBlob(el, {cacheBust: true});
            if (blob) {
                await copyBlobToClipboard(blob);
                setStatus('success');
            } else {
                setStatus('error');
            }
        } catch {
            setStatus('error');
        }
    }, []);

    const downloadAsPng = useCallback(async (filename?: string) => {
        const el = targetRef.current;
        if (!el) return;

        setStatus('capturing');
        try {
            const dataUrl = await toPng(el, {cacheBust: true});
            downloadDataUrl(dataUrl, filename ?? 'screenshot.png');
            setStatus('success');
        } catch {
            setStatus('error');
        }
    }, []);

    return {status, isCapturing: status === 'capturing', copyToClipboard, downloadAsPng, targetRef};
}
