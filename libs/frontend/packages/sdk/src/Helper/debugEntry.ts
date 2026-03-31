import {DebugEntry} from '@app-dev-panel/sdk/API/Debug/Debug';

export function isDebugEntryAboutConsole(entry: DebugEntry): boolean {
    return entry != null && entry.command != null;
}

export function isDebugEntryAboutWeb(entry: DebugEntry): boolean {
    return entry != null && entry.request != null && entry.request.method != null;
}
