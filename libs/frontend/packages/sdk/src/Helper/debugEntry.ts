import {DebugEntry} from '@app-dev-panel/sdk/API/Debug/Debug';

export function isDebugEntryAboutConsole(entry: DebugEntry): boolean {
    return entry && 'command' in entry;
}

export function isDebugEntryAboutWeb(entry: DebugEntry): boolean {
    return entry && 'request' in entry;
}
