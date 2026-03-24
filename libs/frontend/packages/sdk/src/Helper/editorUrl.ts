export type EditorPreset = 'none' | 'phpstorm' | 'vscode' | 'vscode-insiders' | 'cursor' | 'sublime' | 'zed' | 'custom';

export type EditorConfig = {editor: EditorPreset; customUrlTemplate: string; pathMapping: Record<string, string>};

export const defaultEditorConfig: EditorConfig = {editor: 'none', customUrlTemplate: '', pathMapping: {}};

const editorTemplates: Record<Exclude<EditorPreset, 'none' | 'custom'>, string> = {
    phpstorm: 'phpstorm://open?file={file}&line={line}',
    vscode: 'vscode://file/{file}:{line}',
    'vscode-insiders': 'vscode-insiders://file/{file}:{line}',
    cursor: 'cursor://file/{file}:{line}',
    sublime: 'subl://open?url=file://{file}&line={line}',
    zed: 'zed://file/{file}:{line}',
};

export const editorPresetLabels: Record<EditorPreset, string> = {
    none: 'None (File Explorer only)',
    phpstorm: 'PhpStorm',
    vscode: 'VS Code',
    'vscode-insiders': 'VS Code Insiders',
    cursor: 'Cursor',
    sublime: 'Sublime Text',
    zed: 'Zed',
    custom: 'Custom URL template',
};

function applyPathMapping(filePath: string, mapping: Record<string, string>): string {
    for (const [remote, local] of Object.entries(mapping)) {
        if (filePath.startsWith(remote)) {
            return local + filePath.slice(remote.length);
        }
    }
    return filePath;
}

export function buildEditorUrl(config: EditorConfig, filePath: string, line?: number): string | null {
    if (config.editor === 'none') return null;

    const template = config.editor === 'custom' ? config.customUrlTemplate : editorTemplates[config.editor];
    if (!template) return null;

    const mappedPath = applyPathMapping(filePath, config.pathMapping);
    return template.replace('{file}', encodeURIComponent(mappedPath)).replace('{line}', String(line ?? 1));
}
