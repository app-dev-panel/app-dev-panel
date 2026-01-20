import {DebugEntry} from '@yiisoft/yii-dev-panel-sdk/API/Debug/Debug';

export function isDebugEntryAboutConsole(entry: DebugEntry): boolean {
    return entry && 'console' in entry;
}

export function isDebugEntryAboutWeb(entry: DebugEntry): boolean {
    return entry && 'web' in entry;
}
