import {afterEach, describe, expect, it, vi} from 'vitest';
import {copyText} from './clipboard';

const setClipboard = (clipboard: unknown) => {
    Object.defineProperty(navigator, 'clipboard', {value: clipboard, configurable: true, writable: true});
};

describe('copyText', () => {
    const originalExecCommand = document.execCommand;

    afterEach(() => {
        setClipboard(undefined);
        document.execCommand = originalExecCommand;
    });

    it('uses the async clipboard API when available', async () => {
        const writeText = vi.fn().mockResolvedValue(undefined);
        setClipboard({writeText});

        await expect(copyText('hello')).resolves.toBe(true);
        expect(writeText).toHaveBeenCalledWith('hello');
    });

    it('falls back to execCommand when writeText rejects', async () => {
        const writeText = vi.fn().mockRejectedValue(new DOMException('Denied', 'NotAllowedError'));
        setClipboard({writeText});
        const execCommand = vi.fn().mockReturnValue(true);
        document.execCommand = execCommand;

        await expect(copyText('fallback')).resolves.toBe(true);
        expect(execCommand).toHaveBeenCalledWith('copy');
        expect(document.querySelector('textarea')).toBeNull();
    });

    it('falls back to execCommand when the clipboard API is missing', async () => {
        setClipboard(undefined);
        document.execCommand = vi.fn().mockReturnValue(true);

        await expect(copyText('x')).resolves.toBe(true);
    });

    it('resolves false instead of throwing when every strategy fails', async () => {
        setClipboard({writeText: vi.fn().mockRejectedValue(new Error('nope'))});
        document.execCommand = vi.fn().mockImplementation(() => {
            throw new Error('unsupported');
        });

        await expect(copyText('x')).resolves.toBe(false);
        expect(document.querySelector('textarea')).toBeNull();
    });

    it('resolves false when execCommand is unavailable', async () => {
        setClipboard(undefined);
        document.execCommand = undefined as unknown as typeof document.execCommand;

        await expect(copyText('x')).resolves.toBe(false);
    });
});
