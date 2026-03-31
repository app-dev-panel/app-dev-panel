import {DebugEntry} from '@app-dev-panel/sdk/API/Debug/Debug';

export function isDebugEntryAboutConsole(entry: DebugEntry): boolean {
    return entry != null && entry.command != null;
}

export function isDebugEntryAboutWeb(entry: DebugEntry): boolean {
    return entry != null && entry.request != null && !!entry.request.method;
}

/**
 * Returns a searchable text representation of a debug entry.
 * Used by both EntrySelector (fuzzy) and DebugEntryList (includes) filters.
 */
export function getEntrySearchText(entry: DebugEntry): string {
    if (isDebugEntryAboutWeb(entry)) {
        return `${entry.request.method} ${entry.request.path} ${entry.response?.statusCode ?? ''}`;
    }
    if (isDebugEntryAboutConsole(entry)) {
        const cmd = entry.command?.input || entry.command?.name || '';
        const exit = entry.command?.exitCode != null ? `exit ${entry.command.exitCode}` : '';
        return `${cmd} ${exit}`;
    }
    return entry.id;
}
